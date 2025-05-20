<?php

namespace fortytwostudio\cookieconsent\elements\db;

use Craft;
use craft\elements\db\ElementQuery;

class LogQuery extends ElementQuery
{
    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable("forty_cookies_tracked");

        $this->query->select([
            "forty_cookies_tracked.accepted",
            "forty_cookies_tracked.rejected",
        ]);

        return parent::beforePrepare();
    }

}
