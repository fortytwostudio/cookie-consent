<?php

namespace fortytwostudio\cookieconsent\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use craft\records\EntryType;
use yii\db\ActiveQueryInterface;

class LogRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%forty_cookies_tracked}}';
    }

    /**
     * Returns the content template's element.
     *
     * @return ActiveQueryInterface
     */
    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
