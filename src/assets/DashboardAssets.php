<?php
namespace fortytwostudio\cookieconsent\assets;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;

class DashboardAssets extends AssetBundle
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
			'dist/css/dashboard.css',
		];

		$this->js = [
			'dist/js/dashboard.js',
		];

		parent::init();
	}
}
