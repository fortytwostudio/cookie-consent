<?php

namespace fortytwostudio\cookieconsent\elements\db;

use Craft;
use craft\elements\Entry;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\db\Table;
use craft\helpers\Db;

class CookieQuery extends ElementQuery
{
    /**
     * @var int[]|int|null The type of cookie this is.
     */
    public string|null $type = null;

    /**
     * Filters the query results based on the type.
     *
     * @param int[]|int|null $value The entry type ID(s).
     * @return self
     */
    public function type(string|null $value): self
    {
        $this->type = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function fieldLayouts(): array
    {
        $layouts = Craft::$app->getFields()->getLayoutsByType(Entry::class);
        return $layouts;
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable("forty_cookies_enabled");

        $this->query->select([
            "forty_cookies_enabled.type",
            "forty_cookies_enabled.cookieId",
            "forty_cookies_enabled.domain",
            "forty_cookies_enabled.duration",
			"forty_cookies_enabled.description",
        ]);

		if ($this->type) {
			$this->subQuery->andWhere(['forty_cookies_enabled.type' => $this->type]);
		}

        return parent::beforePrepare();
    }

}
