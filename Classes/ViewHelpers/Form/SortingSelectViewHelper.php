<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\ViewHelpers\Form;

use FGTCLB\AcademicPrograms\Enumeration\SortingOptions;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
        $options = [];

        if (!is_array($this->arguments['options'])
            && !$this->arguments['options'] instanceof \Traversable
        ) {
            foreach (SortingOptions::getConstants() as $sortingValue) {
                if ($this->arguments['fieldsOnly'] || $this->arguments['directionsOnly']) {
                    [$sortingField, $sortingDirection] = GeneralUtility::trimExplode(' ', $sortingValue);
                    if ($this->arguments['fieldsOnly']) {
                        $value = $sortingField;
                        $labelKey = 'field.' . $sortingField;
                    } elseif ($this->arguments['directionsOnly']) {
                        $value = $sortingDirection;
                        $labelKey = 'direction.' . $sortingDirection;
                    }
                } else {
                    $value = $sortingValue;
                    $labelKey = str_replace(' ', '.', $sortingValue);
                }

                $options[$value] = [
                    'value' => $value,
                    'label' => $this->translateLabel($labelKey),
                    'isSelected' => $this->isSelected($value),
                ];
            }
        } else {
            foreach ($this->arguments['options'] as $value => $label) {
                if (isset($this->arguments['l10n']) && $this->arguments['l10n']) {
                    $label = $this->translateLabel($label, $this->arguments['l10n']);
                }

                $options[$value] = [
                    'value' => $value,
                    'label' => $label,
                    'isSelected' => $this->isSelected($value),
                ];
            }
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

    protected function translateLabel(
        string $labelKey,
        ?string $l10nPrefix = 'sorting'
    ): string {
        $key = sprintf(
            'LLL:EXT:educational_course/Resources/Private/Language/locallang.xlf:%s.%s',
            $l10nPrefix,
            $labelKey
        );

        $extensionName = $this->arguments['extensionName'] === null ? 'educational_course' : $this->arguments['extensionName'];

        $translatedLabel = LocalizationUtility::translate(
            $key,
            $extensionName
        );

        if ($translatedLabel === null) {
            return $labelKey;
        }

        return $translatedLabel;
    }
}
