<?php

namespace fortytwostudio\cookieconsent\elements\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextField;

class DurationField extends TextField
{
	public bool $mandatory = true;

	public string $attribute = 'duration';

	public ?int $maxlength = 255;

	public bool $required = true;

	protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
	{
		return Craft::t('forty-cookieconsent', 'Duration');
	}

	/**
	 * Ensure the input gets a `required` HTML attribute
	 */
	public function inputHtml(ElementInterface $element = null, bool $static = false): ?string
	{
		return Craft::$app->getView()->renderTemplate('_includes/forms/text', [
			'id' => $this->attribute(),
			'name' => $this->attribute(),
			'value' => $element?->{$this->attribute} ?? '',
			'required' => true,
			'inputAttributes' => [
				"required" => true,
			],
		]);
	}

	/**
	 * Add actual server-side validation rule
	 */
	public function defineValidationRules(): array
	{
		return [
			[$this->attribute, 'required'],
		];
	}
}
