<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

require_once dirname(__FILE__). '/../../constants.php';

class Cardwall_OnTop_Config_ColumnFactory {

    const DEFAULT_HEADER_COLOR = 'rgb(248,248,248)';

    /**
     * @var Cardwall_OnTop_ColumnDao
     */
    private $dao;

    /**
     * @var Cardwall_OnTop_Dao
     */
    private $on_top_dao;

    public function __construct(Cardwall_OnTop_ColumnDao $dao, Cardwall_OnTop_Dao $on_top_dao) {
        $this->dao        = $dao;
        $this->on_top_dao = $on_top_dao;
    }

    /**
     * Get columns for Cardwall_OnTop
     *
     * @return Cardwall_OnTop_Config_ColumnCollection
     */
    public function getDashboardColumns(Tracker $tracker) {
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

    private function fillColumnsFor(
        Cardwall_OnTop_Config_ColumnCollection&$columns,
        Tracker_FormElement_Field_List $field,
        array $filter
    ) {

        if (count($filter) === 0) {
            $field_values = $field->getVisibleValuesPlusNoneIfAny();
        } else {
            foreach ($filter as $value_id) {
                $field_values[] = $field->getBind()->getValue($value_id);
            }
        }

        $decorators = $field->getDecorators();
        foreach ($field_values as $value) {
            $header_color = $this->getColumnHeaderColor($value, $decorators);
            $columns[]    = new Cardwall_Column($value->getId(), $value->getLabel(), $header_color);
        }
    }

    /**
     * @return Cardwall_OnTop_Config_ColumnCollection
     */
    private function getColumnsFromDao(Tracker $tracker) {
        $columns = new Cardwall_OnTop_Config_ColumnFreestyleCollection();
        foreach ($this->dao->searchColumnsByTrackerId($tracker->getId()) as $row) {
            $header_color = $this->getColumnHeaderColorFromRow($row);
            $columns[]    = new Cardwall_Column($row['id'], $row['label'], $header_color);
        }
        return $columns;
    }

    private function getColumnHeaderColor($value, $decorators) {
        $id           = (int)$value->getId();
        $header_color = self::DEFAULT_HEADER_COLOR;

        if (isset($decorators[$id])) {
            $header_color = $decorators[$id]->getCurrentColor();
        }

        return $header_color;
    }

    private function getColumnHeaderColorFromRow(array $row)
    {
        $header_color = self::DEFAULT_HEADER_COLOR;

        $r              = $row['bg_red'];
        $g              = $row['bg_green'];
        $b              = $row['bg_blue'];
        $tlp_color_name = $row['tlp_color_name'];

        if ($tlp_color_name) {
            $header_color = $tlp_color_name;
        } else if ($r !== null && $g !== null && $b !== null) {
            $header_color = "rgb($r, $g, $b)";
        }

        return $header_color;
    }
}
