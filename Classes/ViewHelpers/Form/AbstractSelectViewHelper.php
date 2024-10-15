<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\ViewHelpers\Form;

class AbstractSelectViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('renderOptions', 'bool', 'If true, options will be rendered. Otherwise an "options" variable is created for custom rendering in the template.', false, true);
    }

    /**
     * @return string
     */
    public function render(): string
    {
        if (isset($this->arguments['required']) && $this->arguments['required']) {
            $this->tag->addAttribute('required', 'required');
        }

        $name = $this->getName();
        if (isset($this->arguments['multiple']) && $this->arguments['multiple']) {
            $this->tag->addAttribute('multiple', 'multiple');
            $name .= '[]';
        }
        $this->tag->addAttribute('name', $name);

        $options = $this->getOptions();

        if (isset($this->arguments['renderOptions']) && (bool)$this->arguments['renderOptions'] === false) {
            $this->renderingContext->getVariableProvider()->add('options', $options);
        }

        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();

        $this->addAdditionalIdentityPropertiesIfNeeded();
        $this->setErrorClassAttribute();
        $content = '';

        // Register field name for token generation.
        $this->registerFieldNameForFormTokenGeneration($name);

        // In case it is a multi-select, we need to register the field name
        // as often as there are elements in the box
        if (isset($this->arguments['multiple']) && $this->arguments['multiple']) {
            $content .= $this->renderHiddenFieldForEmptyValue();

            // Register the field name additional times as required by the total number of
            // options. Since we already registered it once above, we start the counter at 1
            // instead of 0.
            $optionsCount = count($options);
            for ($i = 1; $i < $optionsCount; $i++) {
                $this->registerFieldNameForFormTokenGeneration($name);
            }

            // Save the parent field name so that any child f:form.select.option
            // tag will know to call registerFieldNameForFormTokenGeneration
            // this is the reason why "self::class" is used instead of static::class (no LSB)
            $viewHelperVariableContainer->addOrUpdate(
                self::class,
                'registerFieldNameForFormTokenGeneration',
                $name
            );
        }

        $prependContent = $this->renderPrependOptionTag();

        $tagContent = '';
        if (isset($this->arguments['renderOptions']) && (bool)$this->arguments['renderOptions'] === true) {
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
}
