<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\ViewHelpers\Form;

class FilterSelectViewHelper extends AbstractSelectViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('groupByParent', 'bool', 'If true, options will be grouped by parents.', false, false);
        $this->registerArgument('groupLevelClassPrefix', 'string', 'Prefix of the level indicator class for grouped options.', false, 'level-');
    }

    /**
     * @return array<int, mixed>
     */
    protected function getOptions(): array
    {
        if (!is_array($this->arguments['options'])
            && !$this->arguments['options'] instanceof \Traversable
        ) {
            return [];
        }

        $options = [];
        $optionsFromArgument = $this->arguments['options'];

        foreach ($optionsFromArgument as $option) {
            $options[] = [
                'label' => $option->getTitle(),
                'uid' => $option->getUid(),
                'parentId' => $option->getParentId(),
                'isRoot' => $option->isRoot(),
                'type' => (string)$option->getType(),
                'isSelected' => $this->isSelected($option->getUid()),
                'isDisabled' => $option->isDisabled(),
                'level' => 0,
                'children' => [],
            ];
        }

        if ($this->arguments['sortByOptionLabel'] !== false) {
            usort($options, function ($a, $b) {
                return strcoll($a['label'], $b['label']);
            });
        }

        if ($this->arguments['groupByParent'] !== false) {
            $optionsTree = [];
            foreach ($options as $option) {
                if ($option['isRoot'] === true) {
                    $option['children'] = $this->createOptionsTree($options, $option);
                    $optionsTree[] = $option;
                }
            }

            $options = [];
            $options = $this->linearizeOptionsTree($options, $optionsTree);
        }

        return $options;
    }

    /**
     * Create the options tree
     *
     * @param array<int, mixed> $options
     * @param array<string, mixed> $parent
     * @return array<int, mixed>
     */
    private function createOptionsTree(&$options, $parent): array
    {
        $tree = [];
        foreach ($options as $option) {
            if ($option['parentId'] == $parent['uid']) {
                $child = $option;
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
     * @param array<int, mixed> $options
     * @param array<int, mixed> $optionsTree
     * @return array<int, mixed>
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
     * @param array<int, mixed> $options
     * @return string
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
