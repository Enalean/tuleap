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

use Tuleap\Cardwall\Column\ColumnColorRetriever;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Cardwall_OnTop_Config_ColumnFactory
{

    public const DEFAULT_HEADER_COLOR = 'rgb(248,248,248)';

    /**
     * @var Cardwall_OnTop_ColumnDao
     */
    private $dao;

    public function __construct(Cardwall_OnTop_ColumnDao $dao)
    {
        $this->dao        = $dao;
    }

    /**
     * Get columns for Cardwall_OnTop
     *
     * @return Cardwall_OnTop_Config_ColumnCollection
     */
    public function getDashboardColumns(Tracker $tracker)
    {
        return $this->getColumnsFromDao($tracker);
    }

    /**
     * Get Columns from the values of a $field
     * @return Cardwall_OnTop_Config_ColumnCollection
     */
    public function getRendererColumns(Tracker_FormElement_Field_List $field)
    {
        $columns = new Cardwall_OnTop_Config_ColumnCollection();
        $this->fillColumnsFor($columns, $field, []);
        return $columns;
    }

    public function getFilteredRendererColumns(Tracker_FormElement_Field_List $field, array $filter)
    {
        $columns = new Cardwall_OnTop_Config_ColumnCollection();
        $this->fillColumnsFor($columns, $field, $filter);
        return $columns;
    }

    public function getColumnById(int $column_id): ?Cardwall_Column
    {
        $dar = $this->dao->searchByColumnId($column_id);
        if (! $dar) {
            return null;
        }
        $row = $dar->getRow();
        $header_color = ColumnColorRetriever::getHeaderColorNameOrRGB($row);
        return new Cardwall_Column($column_id, $row['label'], $header_color);
    }

    private function fillColumnsFor(
        Cardwall_OnTop_Config_ColumnCollection &$columns,
        Tracker_FormElement_Field_List $field,
        array $filter
    ) {
        $decorators = $field->getDecorators();
        foreach ($this->getFieldBindValues($field, $filter) as $value) {
            $header_color = $this->getColumnHeaderColor($value, $decorators);
            $columns[]    = new Cardwall_Column($value->getId(), $value->getLabel(), $header_color);
        }
    }

    private function getFieldBindValues(
        Tracker_FormElement_Field_List $field,
        array $filter
    ) {
        if (count($filter) === 0) {
            return  $field->getVisibleValuesPlusNoneIfAny();
        }

        $field_values = array();

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

    /**
     * @return Cardwall_OnTop_Config_ColumnCollection
     */
    private function getColumnsFromDao(Tracker $tracker)
    {
        $columns = new Cardwall_OnTop_Config_ColumnFreestyleCollection();
        foreach ($this->dao->searchColumnsByTrackerId($tracker->getId()) as $row) {
            $header_color = ColumnColorRetriever::getHeaderColorNameOrRGB($row);
            $columns[]    = new Cardwall_Column($row['id'], $row['label'], $header_color);
        }
        return $columns;
    }

    private function getColumnHeaderColor($value, $decorators)
    {
        $id           = (int) $value->getId();
        $header_color = self::DEFAULT_HEADER_COLOR;

        if (isset($decorators[$id])) {
            $header_color = $decorators[$id]->getCurrentColor();
        }

        return $header_color;
    }
}
