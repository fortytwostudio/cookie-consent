<?php
namespace fortytwostudio\cookieconsent\controllers;

use Craft;
use craft\elements\Entry;
use craft\helpers\UrlHelper;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\web\Controller;
use fortytwostudio\cookieconsent\CookieConsent;
use fortytwostudio\cookieconsent\models\SettingsModel;
use fortytwostudio\cookieconsent\services\SiteContext;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class SettingsController extends Controller
{
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $this->requireCpRequest();
        $this->requireAdmin();
        return true;
    }

    public function actionGeneral(): Response { return $this->renderSection('general'); }
    public function actionExclude(): Response { return $this->renderSection('exclude'); }
    public function actionGoogle(): Response { return $this->renderSection('google'); }
    public function actionColours(): Response { return $this->renderSection('colours'); }

    public function actionSave(): Response
    {
        $this->requirePostRequest();
        $site = SiteContext::forCpRequest();
        $section = $this->request->getRequiredBodyParam('section');
        $fields = $this->sectionFields($section);
        $submitted = $this->request->getBodyParam('settings', []);
        if (!is_array($submitted)) {
            throw new BadRequestHttpException('Invalid cookie consent settings.');
        }
        $data = array_intersect_key($submitted, array_flip($fields));
        foreach ($data as $attribute => &$value) {
            if ($attribute === 'excludeIds') {
                $value = SettingsModel::normalizeExcludeIds($value);
            } elseif (in_array($attribute, ['isEnabled', 'sendGtag'], true)) {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($value === null) {
                    throw new BadRequestHttpException('Invalid switch value.');
                }
            } elseif (!is_string($value)) {
                throw new BadRequestHttpException('Invalid setting value.');
            } else {
                $value = trim($value);
                if ($section === 'colours') {
                    $value = ltrim($value, '#');
                }
            }
        }
        unset($value);

        $plugin = CookieConsent::getInstance();
        $effective = $plugin->getSiteSettings($site->id);
        $effective->setAttributes($data, false);
        if (!$effective->validate()) {
            Craft::$app->getSession()->setError(implode(' ', $effective->getErrorSummary(true)));
            return $this->renderSection($section, $effective);
        }

        // Read the stored values so saving one site/tab does not persist environment
        // overrides or replace another site's settings with the current form values.
        $stored = ProjectConfigHelper::unpackAssociativeArrays(
            Craft::$app->getProjectConfig()->get('plugins.forty-cookieconsent.settings') ?? [],
        );
        $stored = array_replace((new SettingsModel())->getAttributes(), $stored);
        $stored['sites'][$site->uid] = array_replace($stored['sites'][$site->uid] ?? [], $data);
        if (!Craft::$app->getPlugins()->savePluginSettings($plugin, $stored)) {
            Craft::$app->getSession()->setError('Could not save cookie consent settings.');
            return $this->renderSection($section, $effective);
        }

        Craft::$app->getSession()->setNotice('Cookie consent settings saved for ' . $site->name . '.');
        return $this->redirect(UrlHelper::cpUrl('42cookie-consent/settings/' . $section, ['site' => $site->handle]));
    }

    private function renderSection(string $section, ?SettingsModel $settings = null): Response
    {
        $this->sectionFields($section);
        $site = SiteContext::forCpRequest();
        $variables = [
            'cookieSite' => $site,
            'settings' => $settings ?? CookieConsent::getInstance()->getSiteSettings($site->id),
        ];

        if ($section === 'exclude') {
            $sections = [];
            foreach (Craft::$app->getEntries()->getAllSections() as $entrySection) {
                $sections[$entrySection->id] = ['title' => $entrySection->name, 'entries' => []];
            }
            foreach (Entry::find()->siteId($site->id)->uri(':notempty:')->all() as $entry) {
                if (isset($sections[$entry->sectionId])) {
                    $sections[$entry->sectionId]['entries'][] = ['id' => $entry->id, 'title' => $entry->title];
                }
            }
            $variables['sections'] = array_filter($sections, static fn(array $section): bool => $section['entries'] !== []);
        }

        return $this->renderTemplate('forty-cookieconsent/settings/_' . $section, $variables);
    }

    private function sectionFields(mixed $section): array
    {
        return match ($section) {
            'general' => ['isEnabled', 'siteDomain'],
            'exclude' => ['excludeIds'],
            'google' => ['sendGtag'],
            'colours' => array_values(array_diff(SettingsModel::siteAttributes(), [
                'isEnabled', 'sendGtag', 'siteDomain', 'excludeIds', 'triggerPosition',
                'consentEqualWeightButtons', 'consentFlipButtons', 'preferencesEqualWeightButtons', 'preferencesFlipButtons',
            ])),
            default => throw new BadRequestHttpException('Unknown cookie consent settings section.'),
        };
    }
}
