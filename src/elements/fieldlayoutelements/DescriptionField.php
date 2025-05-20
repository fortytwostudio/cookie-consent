<?php

namespace fortytwostudio\cookieconsent\elements\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\TextareaField;

class DescriptionField extends TextareaField
{
	public bool $mandatory = true;

	public string $attribute = 'description';

	public ?int $maxlength = 255;

	public bool $required = true;

	public ?int $rows = 5;

	protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
	{
		return Craft::t('forty-cookieconsent', 'Description');
	}

	/**
	 * Ensure the input gets a `required` HTML attribute
	 */
	public function inputHtml(ElementInterface $element = null, bool $static = false): ?string
	{
		return Craft::$app->getView()->renderTemplate('_includes/forms/textarea', [
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
