<?php
/** Standalone regression harness; does not bootstrap or modify a Craft installation. */
if (!defined('YII_ENABLE_ERROR_HANDLER')) {
    define('YII_ENABLE_ERROR_HANDLER', false);
}

$plugin = rtrim(getenv('COOKIE_PLUGIN_PATH') ?: dirname(__DIR__), '/');
$vendor = rtrim(getenv('CRAFT_VENDOR_PATH') ?: $plugin . '/vendor', '/');
if (!is_file($vendor . '/autoload.php')) {
    throw new RuntimeException('Set CRAFT_VENDOR_PATH to a Craft 5 Composer vendor directory.');
}
$loader = require $vendor . '/autoload.php';
require_once $vendor . '/yiisoft/yii2/Yii.php';
require_once $vendor . '/craftcms/cms/src/Craft.php';
$loader->addPsr4('fortytwostudio\\cookieconsent\\', $plugin . '/src', true);

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
