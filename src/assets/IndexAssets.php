<?php
namespace fortytwostudio\cookieconsent\assets;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;

class IndexAssets extends AssetBundle
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

		// $this->css = [
		// 	'css/dist/index.min.css',
		// ];

		$this->js = [
			'src/js/index.js',
		];

		parent::init();
	}
}
