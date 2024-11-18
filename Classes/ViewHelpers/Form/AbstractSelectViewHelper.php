<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\ViewHelpers\Form;

use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;

class AbstractSelectViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'select';

    /**
     * @var string
     */
    protected $selectedValue;

    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $arguments = [
            'options' => [
                'type' => 'array',
                'defaultValue' => [],
                'description' => 'Associative array with internal IDs as key, and the values are displayed in the select box. Can be combined with or replaced by child f:form.select.* nodes.',
            ],
            'optionsAfterContent' => [
                'type' => 'boolean',
                'defaultValue' => false,
                'description' => 'If true, places auto-generated option tags after those rendered in the tag content. If false, automatic options come first.',
            ],
            'sortByOptionLabel' => [
                'type' => 'boolean',
                'defaultValue' => false,
                'description' => 'If true, List will be sorted by label.',
            ],
            'errorClass' => [
                'type' => 'string',
                'defaultValue' => 'f3-form-error',
                'description' => 'CSS class to set if there are errors for this ViewHelper',
            ],
            'prependOptionLabel' => [
                'type' => 'string',
                'description' => 'If specified, will provide an option at first position with the specified label.',
            ],
            'prependOptionValue' => [
                'type' => 'string',
                'description' => 'If specified, will provide an option at first position with the specified value.',
            ],
            'required' => [
                'type' => 'boolean',
                'defaultValue' => false,
                'description' => 'If set no empty value is allowed.',
            ],
            'renderOptions' => [
                'type' => 'bool',
                'defaultValue' => true,
                'description' => 'If true, options will be rendered. Otherwise an "options" variable is created for custom rendering in the template.',
            ],
        ];

        $this->registerArguments($arguments);
    }

    public function render(): string
    {
        if (isset($this->arguments['required']) && $this->arguments['required']) {
            $this->tag->addAttribute('required', 'required');
        }

        $name = $this->getName();
        $this->tag->addAttribute('name', $name);

        $options = $this->getOptions();

        if (isset($this->arguments['renderOptions'])
            && (bool)$this->arguments['renderOptions'] === false
        ) {
            $this->renderingContext->getVariableProvider()->add('options', $options);
        }

        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();

        $this->addAdditionalIdentityPropertiesIfNeeded();
        $this->setErrorClassAttribute();
        $content = '';

        // Register field name for token generation.
        $this->registerFieldNameForFormTokenGeneration($name);

        $prependContent = $this->renderPrependOptionTag();

        $tagContent = '';
        if (isset($this->arguments['renderOptions'])
            && (bool)$this->arguments['renderOptions'] === true
        ) {
            $tagContent = $this->renderOptionTags($options);
        }

        $viewHelperVariableContainer->addOrUpdate(self::class, 'selectedValue', $this->getSelectedValue());

        $childContent = $this->renderChildren();

        $viewHelperVariableContainer->remove(self::class, 'selectedValue');
        $viewHelperVariableContainer->remove(self::class, 'registerFieldNameForFormTokenGeneration');
        if (isset($this->arguments['renderOptions']) && (bool)$this->arguments['renderOptions'] === false) {
            $this->renderingContext->getVariableProvider()->remove('options');
        }

        if (isset($this->arguments['optionsAfterContent']) && $this->arguments['optionsAfterContent']) {
            $tagContent = $childContent . $tagContent;
        } else {
            $tagContent .= $childContent;
        }
        $tagContent = $prependContent . $tagContent;

        $this->tag->forceClosingTag(true);
        $this->tag->setContent($tagContent);
        $content .= $this->tag->render();

        return $content;
    }

    protected function renderPrependOptionTag(): string
    {
        $output = '';
        if ($this->hasArgument('prependOptionLabel')) {
            $value = $this->hasArgument('prependOptionValue') ? $this->arguments['prependOptionValue'] : '';
            $label = $this->arguments['prependOptionLabel'];
            $output .= $this->renderOptionTag((string)$value, (string)$label, false) . LF;
        }
        return $output;
    }

    /**
     * @param array<string, mixed> $arguments
     */
    protected function registerArguments(array $arguments): void
    {
        foreach ($arguments as $argumentName => $argumentConfiguration) {
            $this->registerArgument(
                $argumentName,
                $argumentConfiguration['type'],
                $argumentConfiguration['description'],
                $argumentConfiguration['required'] ?? false,
                $argumentConfiguration['defaultValue'] ?? null,
                $argumentConfiguration['escape'] ?? null
            );
        }
    }

    protected function getSelectedValue(): string
    {
        $this->setRespectSubmittedDataValue(true);
        $selectedValue =(string)$this->getValueAttribute();
        return $selectedValue;
    }

    protected function isSelected(string $value): bool
    {
        $selectedValue = $this->getSelectedValue();

        if ($value === $selectedValue || (string)$value === $selectedValue) {
            return true;
        }

        return false;
    }

    /**
     * @param array<int, mixed> $options
     */
    protected function renderOptionTags($options): string
    {
        $output = '';
        foreach ($options as $option) {
            $output .= '<option value="' . $option['value'] . '"';
            if ($option['isSelected']) {
                $output .= ' selected="selected"';
            }
            $output .= '>' . htmlspecialchars((string)$option['label']) . '</option>' . LF;
        }
        return $output;
    }

    protected function renderOptionTag(string $value, string $label, bool $isSelected): string
    {
        $output = '<option value="' . htmlspecialchars((string)$value) . '"';
        if ($isSelected) {
            $output .= ' selected="selected"';
        }
        $output .= '>' . htmlspecialchars((string)$label) . '</option>';
        return $output;
    }

    /**
     * @return array<int, mixed>
     */
    protected function getOptions()
    {
        return [];
    }
}
