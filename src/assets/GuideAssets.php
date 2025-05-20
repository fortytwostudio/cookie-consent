<?php
namespace fortytwostudio\cookieconsent\assets;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;

class GuideAssets extends AssetBundle
{
	// Public Methods
	// =========================================================================

	public function init()
	{
		$this->sourcePath = "@fortytwostudio/cookieconsent/resources";

		// define the dependencies
		$this->depends = [
			CpAsset::class,
		];

		$this->css = [
			'dist/css/guides.css',
		];

		$this->js = [
			'dist/js/highlight.js',
		];

		parent::init();
	}
}
