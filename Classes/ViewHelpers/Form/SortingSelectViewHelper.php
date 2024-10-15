<?php

declare(strict_types=1);

namespace FGTCLB\EducationalCourse\ViewHelpers\Form;

use FGTCLB\EducationalCourse\Enumeration\SortingOptions;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class SortingSelectViewHelper extends AbstractSelectViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('fieldsOnly', 'boolean', 'If true, the select only lists the sorting field options.', false, false);
        $this->registerArgument('directionsOnly', 'boolean', 'If ture, the select only lists the sorting direction options.', false, false);
        $this->registerArgument('l10n', 'string', 'If specified, will call the correct label specified in locallang file.');
    }

    /**
     * @return array<int, mixed>
     */
    protected function getOptions(): array
    {
        $optionsArgument = [];

        if (!is_array($this->arguments['options'])
            && !$this->arguments['options'] instanceof \Traversable
        ) {
            foreach (SortingOptions::getConstants() as $value) {
                if ($this->arguments['fieldsOnly'] || $this->arguments['directionsOnly']) {
                    [$sortingField, $sortingDirection] = explode(' ', $value);
                    if ($this->arguments['fieldsOnly']) {
                        $value = $sortingField;
                    } elseif ($this->arguments['directionsOnly']) {
                        $value = $sortingDirection;
                    }
                }

                $label = str_replace(' ', '.', $value);
                $key = 'LLL:EXT:academic_projects/Resources/Private/Language/locallang_be.xlf:flexform.sorting.' . $label;
                $translatedLabel = LocalizationUtility::translate($key);
                if ($translatedLabel !== null) {
                    $label = $translatedLabel;
                }
                $optionsArgument[$value] = $label;
            }
        } else {
            $optionsArgument = $this->arguments['options'];
            $extensionName = $this->arguments['extensionName'] === null ? 'academic_projects' : $this->arguments['extensionName'];

            foreach ($optionsArgument as $value => $label) {
                if (isset($this->arguments['l10n']) && $this->arguments['l10n']) {
                    $translatedLabel = LocalizationUtility::translate(
                        $this->arguments['l10n'] . '.' . $label,
                        $extensionName,
                        $this->arguments['arguments']
                    );
                    if ($translatedLabel !== null) {
                        $label = $translatedLabel;
                    }
                }
            }
        }

        $options = [];

        foreach ($optionsArgument as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
                'isSelected' => $this->isSelected($value),
            ];
        }

        if ($this->arguments['sortByOptionLabel'] !== false) {
            usort($options, function ($a, $b) {
                return strcoll($a['label'], $b['label']);
            });
        }

        return $options;
    }

    /**
     * @param array<int, mixed> $options
     * @return string
     */
    protected function renderOptionTags($options)
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
}
