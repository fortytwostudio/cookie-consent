<?php

namespace fortytwostudio\cookieconsent\services;

use Craft;
use craft\db\Query;
use DateTimeInterface;
use fortytwostudio\cookieconsent\elements\CookieElement;
use RuntimeException;
use yii\db\Expression;

/** Assign legacy data to its primary-site owner, including earlier 3.13 upgrades. */
final class PrimarySiteUpgrade
{
    public static function run(): void
    {
        $db = Craft::$app->getDb();
        $pdo = $db->getMasterPdo();
        // MySQL DDL can commit the physical transaction while Yii still tracks
        // an active outer migration transaction. Reopen it for this DML phase
        // so consuming legacy counter rows and adding their totals stays atomic.
        $ownsPhysicalTransaction = $db->getTransaction() !== null && !$pdo->inTransaction();
        if ($ownsPhysicalTransaction) {
            $pdo->beginTransaction();
        }

        try {
            // Always retain Yii's transaction/savepoint bookkeeping, including
            // the nested element transactions used when moving copies to trash.
            $db->transaction(static function (): void {
                $primary = Craft::$app->getSites()->getPrimarySite();
                self::assignContent($primary->id);
                self::assignCounts($primary->id);
                self::removeUnchangedCopies($primary->id);
            });
            if ($ownsPhysicalTransaction && $pdo->inTransaction()) {
                $pdo->commit();
            }
        } catch (\Throwable $exception) {
            if ($ownsPhysicalTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    private static function assignContent(int $primarySiteId): void
    {
        $db = Craft::$app->getDb();
        $legacy = (new Query())->from(SiteData::CONTENT_TABLE)
            ->where(['siteId' => null])->orderBy(['id' => SORT_ASC])->one();
        if (!$legacy) {
            return;
        }

        $primaryExists = (new Query())->from(SiteData::CONTENT_TABLE)
            ->where(['siteId' => $primarySiteId])->exists();
        if (!$primaryExists) {
            // Adopt the original row before default initialization. An existing
            // primary row may have later edits, so it must never be overwritten.
            $db->createCommand()->update(
                SiteData::CONTENT_TABLE,
                ['siteId' => $primarySiteId] + self::legacyContentForSite($legacy, $primarySiteId),
                ['id' => $legacy['id'], 'siteId' => null],
            )->execute();
        }

        // 3.13.0/3.13.1 copied the snapshot to other sites. Reset only rows whose
        // complete editable content still equals that generated copy.
        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            if ($site->id === $primarySiteId) {
                continue;
            }
            $row = (new Query())->from(SiteData::CONTENT_TABLE)->where(['siteId' => $site->id])->one();
            $expected = self::legacyContentForSite($legacy, $site->id);
            if (!$row || !self::sameValues($row, $expected)) {
                continue;
            }
            // Include the read values in the condition to protect a concurrent edit.
            $db->createCommand()->update(
                SiteData::CONTENT_TABLE,
                SiteData::defaults($site->id),
                ['id' => $row['id'], 'siteId' => $site->id] + $expected,
            )->execute();
        }
    }

    private static function legacyContentForSite(array $legacy, int $siteId): array
    {
        $values = array_intersect_key($legacy, SiteData::defaults());
        if (($values['popupFooter'] ?? null) === SiteData::defaults()['popupFooter']) {
            $values['popupFooter'] = SiteData::defaults($siteId)['popupFooter'];
        }
        return $values;
    }

    private static function assignCounts(int $primarySiteId): void
    {
        $db = Craft::$app->getDb();
        $rows = (new Query())->from(SiteData::TRACKED_TABLE)->where(['siteId' => null])->all();
        if (!$rows) {
            return;
        }

        $db->createCommand()->upsert(SiteData::TRACKED_TABLE, [
            'siteId' => $primarySiteId, 'accepted' => 0, 'rejected' => 0,
        ], false)->execute();

        foreach ($rows as $row) {
            // Claim each old row once. A retry sees no consumed source; a failed
            // increment rolls the deletion back with the enclosing transaction.
            $claimed = $db->createCommand()->delete(SiteData::TRACKED_TABLE, [
                'id' => $row['id'], 'siteId' => null,
            ])->execute();
            if (!$claimed) {
                continue;
            }
            $db->createCommand()->update(SiteData::TRACKED_TABLE, [
                'accepted' => new Expression('[[accepted]] + :legacyAccepted', [':legacyAccepted' => (int)$row['accepted']]),
                'rejected' => new Expression('[[rejected]] + :legacyRejected', [':legacyRejected' => (int)$row['rejected']]),
            ], ['siteId' => $primarySiteId])->execute();
        }
    }

    private static function removeUnchangedCopies(int $primarySiteId): void
    {
        $sources = CookieElement::find()->siteId($primarySiteId)->status(null)->all();
        $primaryDomain = KnownCookies::siteDomain($primarySiteId);
        $primaryConsentName = SiteContext::cookieName($primarySiteId);

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            if ($site->id === $primarySiteId) {
                continue;
            }
            $copies = [];
            foreach (CookieElement::find()->siteId($site->id)->status(null)->all() as $cookie) {
                $copies[$cookie->uid] = $cookie;
            }

            foreach ($sources as $source) {
                $copy = $copies[self::copyUid($source->uid, $site->uid)] ?? null;
                if (!$copy || !self::hasUnchangedTimestamps($source, $copy)) {
                    continue;
                }

                $expected = ['cookieSiteId' => $site->id, 'enabled' => $source->enabled];
                foreach (['type', 'cookieId', 'domain', 'duration', 'description'] as $attribute) {
                    $expected[$attribute] = $source->{$attribute};
                }
                if (in_array($expected['cookieId'], ['cc_cookie', $primaryConsentName], true)) {
                    $expected['cookieId'] = SiteContext::cookieName($site->id);
                }
                if ($expected['domain'] === $primaryDomain || $expected['domain'] === '.' . ltrim($primaryDomain, '.')) {
                    $expected['domain'] = KnownCookies::siteDomain($site->id);
                }
                if (!self::sameValues($copy->getAttributes(array_keys($expected)), $expected)) {
                    continue;
                }

                // Use Craft's trash lifecycle. Native fields are retained for
                // soft-deleted cookies; the element FK handles eventual hard deletes.
                if (!Craft::$app->getElements()->deleteElement($copy, false)) {
                    throw new RuntimeException('Could not archive an unchanged migrated cookie for ' . $site->name . '.');
                }
            }
        }
    }

    private static function hasUnchangedTimestamps(CookieElement $source, CookieElement $copy): bool
    {
        return $source->dateUpdated instanceof DateTimeInterface
            && $copy->dateCreated instanceof DateTimeInterface
            && $copy->dateUpdated instanceof DateTimeInterface
            && $copy->dateCreated == $copy->dateUpdated
            && $source->dateUpdated <= $copy->dateCreated;
    }

    private static function sameValues(array $actual, array $expected): bool
    {
        foreach ($expected as $key => $value) {
            if (!array_key_exists($key, $actual)) {
                return false;
            }
            if ($value === null || $actual[$key] === null) {
                if ($actual[$key] !== $value) {
                    return false;
                }
            } elseif ((string)$actual[$key] !== (string)$value) {
                return false;
            }
        }
        return true;
    }

    /** The exact UUID v5 provenance used by the earlier multisite migration. */
    private static function copyUid(string $sourceUid, string $siteUid): string
    {
        $namespace = hex2bin('6ba7b8119dad11d180b400c04fd430c8');
        $bytes = substr(sha1($namespace . 'fortytwo-cookieconsent/multisite/' . $sourceUid . '/' . $siteUid, true), 0, 16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x50);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }
}
