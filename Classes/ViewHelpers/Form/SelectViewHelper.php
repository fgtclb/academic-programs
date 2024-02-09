<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\ViewHelpers\Form;

use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

class SelectViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper
{
    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('l10n', 'string', 'If specified, will call the correct label specified in locallang file.');
        $this->registerArgument('groupByParent', 'bool', 'If true, options will be grouped by parents.', false, false);
        $this->registerArgument('groupLevelClassPrefix', 'string', 'Prefix of the level indicator class for grouped options.', false, 'level-');
    }

    /**
     * Render the option tags.
     *
     * @return array an associative array of options, key will be the value of the option tag
     */
    protected function getOptions()
    {
        if (!is_array($this->arguments['options']) && !$this->arguments['options'] instanceof \Traversable) {
            return [];
        }

        $options = [];
        $optionsArgument = $this->arguments['options'];

        if (isset($this->arguments['l10n']) && $this->arguments['l10n']) {
            $request = $this->getRequest();
            $extensionName = $this->arguments['extensionName'] === null ? $request->getControllerExtensionName() : $this->arguments['extensionName'];
        }

        foreach ($optionsArgument as $key => $option) {
            if (is_object($option) || is_array($option)) {
                if ($this->hasArgument('optionValueField')) {
                    $value = ObjectAccess::getPropertyPath($option, $this->arguments['optionValueField']);
                    if (is_object($value)) {
                        if (method_exists($value, '__toString')) {
                            $value = (string)$value;
                        } else {
                            throw new Exception('Identifying value for object of class "' . (is_object($value) ? get_class($value) : gettype($value)) . '" was an object.', 1247827428);
                        }
                    }
                } elseif ($this->persistenceManager->getIdentifierByObject($option) !== null) {
                    // @todo use $this->persistenceManager->isNewObject() once it is implemented
                    $value = $this->persistenceManager->getIdentifierByObject($option);
                } elseif (is_object($option) && method_exists($option, '__toString')) {
                    $value = (string)$option;
                } elseif (is_object($option)) {
                    throw new Exception('No identifying value for object of class "' . get_class($option) . '" found.', 1247826696);
                }

                if ($this->hasArgument('optionLabelField')) {
                    $label = ObjectAccess::getPropertyPath($option, $this->arguments['optionLabelField']);
                    if (is_object($label)) {
                        if (method_exists($label, '__toString')) {
                            $label = (string)$label;
                        } else {
                            throw new Exception('Label value for object of class "' . get_class($label) . '" was an object without a __toString() method.', 1247827553);
                        }
                    }
                } elseif (is_object($option) && method_exists($option, '__toString')) {
                    $label = (string)$option;
                } elseif ($this->persistenceManager->getIdentifierByObject($option) !== null) {
                    // @todo use $this->persistenceManager->isNewObject() once it is implemented
                    $label = $this->persistenceManager->getIdentifierByObject($option);
                }
            } else {
                $value = $key;
                $label = $option;
            }

            if (isset($this->arguments['l10n']) && $this->arguments['l10n']) {
                $translatedValue = LocalizationUtility::translate(
                    $this->arguments['l10n'] . '.' . $label,
                    $extensionName,
                    $this->arguments['arguments']
                );
                if ($translatedValue !== null) {
                    $label = $translatedValue;
                }
            }

            $isDisabled = false;
            if (is_object($option) && method_exists($option, 'isDisabled')) {
                $isDisabled = $option->isDisabled();
            }

            $options[$value] = [
                'label' => $label,
                'uid' => $option->getUid(),
                'parentId' => $option->getParentId(),
                'isRoot' => $option->isRoot(),
                'type' => (string)$option->getType(),
                'isSelected' => $this->isSelected($value),
                'isDisabled' => $isDisabled,
                'level' => 0,
                'children' => [],
            ];
        }

        if ($this->arguments['sortByOptionLabel'] !== false) {
            usort($options, function($a, $b) {
                return strcoll($a['label'], $b['label']);
            });
        }

        if ($this->arguments['groupByParent'] !== false) {
            // Build options tree
            $optionsTree = [];
            foreach ($options as $key => $option) {
                if ($option['isRoot'] === true) {
                    $option['children'] = $this->createOptionsTree($options, $option);
                    $optionsTree[$key] = $option;
                }
            }

            $options = [];
            $options = $this->linearizeOptionsTree($options, $optionsTree);
        }

        // Remove children from options
        foreach ($options as $key => $option) {
            unset($options[$key]['children']);
        }

        return $options;
    }

    /**
     * Create the options tree
     *
     * @param array<string, mixed> $options
     * @param array<string, mixed> $optionsTree
     * @return array<string, mixed>
     */
    private function createOptionsTree(&$options, $parent): array
    {
        $tree = [];
        foreach ($options as $key => $option) {
            if ($options[$key]['parentId'] == $parent['uid']) {
                $child = $options[$key];
                $child['level'] = $parent['level'] + 1;
                array_push($tree, $child);

                $subTree = $this->createOptionsTree($options, $child);
                if ($subTree !== []) {
                    foreach ($subTree as $option) {
                        array_push($tree, $option);
                    }
                }
            }
        }
        return $tree;
    }

    /**
     * Linearize the options tree
     *
     * @param array<string, mixed> $options
     * @param array<string, mixed> $optionsTree
     * @return array<string, mixed>
     */
    private function linearizeOptionsTree(array &$options, array $optionsTree): array
    {
        foreach ($optionsTree as $key => $option) {
            array_push($options, $option);
            if ($option['children'] !== []) {
                $this->linearizeOptionsTree($options, $option['children']);
            }
        }
        return $options;
    }

    /**
     * Render the option tags.
     *
     * @param array<string|mixed> $options the options for the form.
     * @return string rendered tags.
     */
    protected function renderOptionTags($options)
    {
        $output = '';
        foreach ($options as $value => $option) {
            $output .= '<option value="' . $option['uid'] . '"';
            $output .= ' class="' . $this->arguments['groupLevelClassPrefix'] . $option['level'] . '"';
            if ($option['isSelected']) {
                $output .= ' selected="selected"';
            }
            if ($option['isDisabled']) {
                $output .= ' disabled="disabled"';
            }
            $output .= '>' . htmlspecialchars((string)$option['label']) . '</option>' . LF;
        }
        return $output;
    }
}
