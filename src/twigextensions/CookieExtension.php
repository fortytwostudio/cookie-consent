<?php
namespace fortytwostudio\cookieconsent\twigextensions;

use fortytwostudio\cookieconsent\CookieConsent;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class CookieExtension extends AbstractExtension implements GlobalsInterface
{
	/**
	 * Returns the globals to add.
	 */
	public function getGlobals(): array
	{
		return [
			"fortycookie" => CookieConsent::$cookieVariable,
		];
	}
}
