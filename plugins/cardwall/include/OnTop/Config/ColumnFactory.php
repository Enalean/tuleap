<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Cardwall\OnTop\Config;

use Cardwall_Column;
use Tracker_FormElement_Field_List_Bind_StaticValue_None;
use Tracker_FormElement_InvalidFieldValueException;
use Tuleap\Cardwall\Column\ColumnColorRetriever;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Tracker;

class ColumnFactory
{
    public const DEFAULT_HEADER_COLOR = 'rgb(248,248,248)';

    public function __construct(private \Cardwall_OnTop_ColumnDao $dao)
    {
    }

    /**
     * Get columns for Cardwall_OnTop
     */
    public function getDashboardColumns(Tracker $tracker): ColumnCollection
    {
        return $this->getColumnsFromDao($tracker);
    }

    /**
     * Get Columns from the values of a $field
     */
    public function getRendererColumns(ListField $field): ColumnCollection
    {
        $columns = new ColumnCollection();
        $this->fillColumnsFor($columns, $field, []);
        return $columns;
    }

    public function getFilteredRendererColumns(ListField $field, array $filter): ColumnCollection
    {
        $columns = new ColumnCollection();
        $this->fillColumnsFor($columns, $field, $filter);
        return $columns;
    }

    public function getColumnById(int $column_id): ?Cardwall_Column
    {
        $dar = $this->dao->searchByColumnId($column_id);
        if (! $dar) {
            return null;
        }
        $row          = $dar->getRow();
        $header_color = ColumnColorRetriever::getHeaderColorNameOrRGB($row);
        return new Cardwall_Column($column_id, $row['label'], $header_color);
    }

    private function fillColumnsFor(
        ColumnCollection $columns,
        ListField $field,
        array $filter,
    ): void {
        $decorators = $field->getDecorators();
        foreach ($this->getFieldBindValues($field, $filter) as $value) {
            $header_color = $this->getColumnHeaderColor($value, $decorators);
            $columns[]    = new Cardwall_Column($value->getId(), $value->getLabel(), $header_color);
        }
    }

    private function getFieldBindValues(
        ListField $field,
        array $filter,
    ): array {
        if (count($filter) === 0) {
            return $field->getVisibleValuesPlusNoneIfAny();
        }

        $field_values = [];

        foreach ($filter as $value_id) {
            if ($field->isNone($value_id)) {
                $field_values[] = new Tracker_FormElement_Field_List_Bind_StaticValue_None();
            } else {
                try {
                    $field_values[] = $field->getBind()->getValue($value_id);
                } catch (Tracker_FormElement_InvalidFieldValueException $exception) {
                }
            }
        }
        return $field_values;
    }

    private function getColumnsFromDao(Tracker $tracker): ColumnCollection
    {
        $columns = new ColumnCollection();
        foreach ($this->dao->searchColumnsByTrackerId($tracker->getId()) as $row) {
            $header_color = ColumnColorRetriever::getHeaderColorNameOrRGB($row);
            $columns[]    = new Cardwall_Column($row['id'], $row['label'], $header_color);
        }
        return $columns;
    }

    /**
     * @param array<\Tracker_FormElement_Field_List_BindDecorator> $decorators
     */
    private function getColumnHeaderColor(\Tracker_FormElement_Field_List_BindValue $value, array $decorators): string
    {
        $id           = (int) $value->getId();
        $header_color = self::DEFAULT_HEADER_COLOR;

        if (isset($decorators[$id])) {
            $header_color = $decorators[$id]->getCurrentColor();
        }

        return $header_color;
    }
}
