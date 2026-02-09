<?php
namespace fortytwostudio\cookieconsent\assets;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class CookieAssets extends AssetBundle
{
	// Public Methods
	// =========================================================================

	public function init()
	{
		$this->sourcePath = "@fortytwostudio/cookieconsent/resources";

		$this->css = [
			'dist/css/main.css',
		];

		$this->cssOptions = [
            'media' => 'print',
            'onload' => "this.media='all'",
        ];

		parent::init();
	}
}
