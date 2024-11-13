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

        $arguments = [
            'fieldsOnly' => [
                'type' => 'boolean',
                'defaultValue' => false,
                'description' => 'If true, the select only lists the sorting field options.',
            ],
            'directionsOnly' => [
                'type' => 'boolean',
                'defaultValue' => false,
                'description' => 'If ture, the select only lists the sorting direction options.',
            ],
            'l10n' => [
                'type' => 'string',
                'description' => 'If specified, will call the correct label specified in locallang file.',
            ],
        ];

        $this->registerArguments($arguments);
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function getOptions(): array
    {
        $options = [];

        if (!is_array($this->arguments['options'])
            && !$this->arguments['options'] instanceof \Traversable
        ) {
            foreach (SortingOptions::getConstants() as $sortingValue) {
                $value = $sortingValue;
                $labelKey = str_replace(' ', '.', $sortingValue);

                if ($this->arguments['fieldsOnly'] || $this->arguments['directionsOnly']) {
                    [$sortingField, $sortingDirection] = GeneralUtility::trimExplode(' ', $sortingValue);
                    if ($this->arguments['fieldsOnly']) {
                        $value = $sortingField;
                        $labelKey = 'field.' . $sortingField;
                    } elseif ($this->arguments['directionsOnly']) {
                        $value = $sortingDirection;
                        $labelKey = 'direction.' . $sortingDirection;
                    }
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

    protected function translateLabel(
        string $labelKey,
        ?string $l10nPrefix = 'sorting'
    ): string {
        $key = sprintf(
            'LLL:EXT:academic_programs/Resources/Private/Language/locallang.xlf:%s.%s',
            $l10nPrefix,
            $labelKey
        );

        $extensionName = $this->arguments['extensionName'] === null ? 'academic_programs' : $this->arguments['extensionName'];

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
