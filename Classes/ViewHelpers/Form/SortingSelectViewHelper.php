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
            'type' => [
                'type' => 'string',
                'defaultValue' => 'combined',
                'description' => 'Allowed values are combined, fields or directions.',
            ],
            'l10n' => [
                'type' => 'string',
                'description' => 'If specified, will call the correct label specified in locallang file.',
            ],
            'extensionName' => [
                'type' => 'string',
                'defaultValue' => 'academic_programs',
                'description' => 'If set, the translation function will use the language labels from the given extension.',
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
            || empty($this->arguments['options'])
        ) {
            foreach (SortingOptions::getConstants() as $sortingValue) {
                $value = $sortingValue;
                $labelKey = str_replace(' ', '.', $sortingValue);

                if ($this->arguments['type'] !== 'combined') {
                    [$sortingField, $sortingDirection] = GeneralUtility::trimExplode(' ', $sortingValue);
                    if ($this->arguments['type'] === 'fields') {
                        $value = $sortingField;
                        $labelKey = 'field.' . $sortingField;
                    } elseif ($this->arguments['type'] === 'directions') {
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
            usort($options, fn($a, $b) => strcoll((string)$a['label'], (string)$b['label']));
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

        $translatedLabel = LocalizationUtility::translate(
            $key,
            $this->arguments['extensionName']
        );

        if ($translatedLabel === null) {
            return $labelKey;
        }

        return $translatedLabel;
    }
}
