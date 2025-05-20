<?php
namespace fortytwostudio\cookieconsent\elements\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseNativeField;

class TypeField extends BaseNativeField
{
	public string $type = 'select';

	public bool $mandatory = true;

	public string $attribute = 'type';

	public bool $required = true;

	protected function defaultLabel(ElementInterface $element = null, bool $static = false): ?string
	{
		return Craft::t('forty-cookieconsent', 'Cookie Type');
	}

	protected function defaultInstructions(ElementInterface $element = null, bool $static = false): ?string
	{
		return Craft::t('forty-cookieconsent', 'Which type of cookie is this?');
	}

	protected function inputHtml(ElementInterface $element = null, bool $static = false): ?string
	{
		$options = [
			[
				'value' => 'necessary',
				'label' => 'Necessary',
			],
			[
				'value' => 'functionality',
				'label' => 'Functionality',
			],
			[
				'value' => 'analytics',
				'label' => 'Analytics',
			],
			[
				'value' => 'performance',
				'label' => 'Performance',
			],
			[
				'value' => 'advertisement',
				'label' => 'Advertisement',
			],
		];

		return Craft::$app->getView()->renderTemplate('_includes/forms/select.twig', [
			'type' => $this->type,
			'describedBy' => $this->describedBy($element, $static),
			'name' => $this->name ?? $this->attribute(),
			'value' => $this->value($element),
			'options' => $options,
		]);
	}
}
