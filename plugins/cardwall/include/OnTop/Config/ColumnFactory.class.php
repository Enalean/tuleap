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

    const DEFAULT_BGCOLOR = 'rgb(248,248,248)';
    const LIGHT_FGCOLOR   = 'rgb(255,255,255)';
    const DARK_FGCOLOR    = 'rgb(0,0,0)';

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
    public function getRendererColumns(Tracker_FormElement_Field_List $field) {
        // TODO use cache of $columns
        $columns = new Cardwall_OnTop_Config_ColumnCollection();
        $this->fillColumnsFor($columns, $field);
        return $columns;
    }

    private function fillColumnsFor(&$columns, $field) {
        $decorators = $field->getDecorators();
        foreach($field->getVisibleValuesPlusNoneIfAny() as $value) {
            list($bgcolor, $fgcolor) = $this->getCardwallColumnColors($value, $decorators);
            $columns[] = new Cardwall_Column($value->getId(), $value->getLabel(), $bgcolor, $fgcolor);
        }
    }

    /**
     * @return Cardwall_OnTop_Config_ColumnCollection
     */
    private function getColumnsFromStatusField(Tracker $tracker) {
        $columns = new Cardwall_OnTop_Config_ColumnStatusCollection();
        $field   = $tracker->getStatusField();
        if ($field) {
            $this->fillColumnsFor($columns, $field);
        }
        return $columns;
    }


    /**
     * @return Cardwall_OnTop_Config_ColumnCollection
     */
    private function getColumnsFromDao(Tracker $tracker) {
        $columns = new Cardwall_OnTop_Config_ColumnFreestyleCollection();
        foreach ($this->dao->searchColumnsByTrackerId($tracker->getId()) as $row) {
            list($bgcolor, $fgcolor) = $this->getColumnColorsFromRow($row);
            $columns[] = new Cardwall_Column($row['id'], $row['label'], $bgcolor, $fgcolor);
        }
        return $columns;
    }

    private function getCardwallColumnColors($value, $decorators) {
        $id      = (int)$value->getId();
        $bgcolor = self::DEFAULT_BGCOLOR;
        $fgcolor = self::DARK_FGCOLOR;
        if (isset($decorators[$id])) {
            $bgcolor = $decorators[$id]->getCurrentColor();
            //choose a text color to have right contrast (black on dark colors is quite useless)
            $fgcolor = $decorators[$id]->isDark($fgcolor) ? self::LIGHT_FGCOLOR : self::DARK_FGCOLOR;
        }
        return array($bgcolor, $fgcolor);
    }

    private function getColumnColorsFromRow(array $row)
    {
        $bgcolor = self::DEFAULT_BGCOLOR;
        $fgcolor = self::DARK_FGCOLOR;

        $r              = $row['bg_red'];
        $g              = $row['bg_green'];
        $b              = $row['bg_blue'];
        $tlp_color_name = $row['tlp_color_name'];

        if ($tlp_color_name) {
            $bgcolor = $tlp_color_name;
        } else if ($r !== null && $g !== null && $b !== null) {
            $bgcolor = "rgb($r, $g, $b)";
            $fgcolor = $this->isDark($r, $g, $b) ? self::LIGHT_FGCOLOR : self::DARK_FGCOLOR;
        }

        return array($bgcolor, $fgcolor);
    }

    /**
     * @todo: DRY (@see Decorator class)
     * @return bool
     */
    public function isDark($r, $g, $b) {
        return (0.3 * $r + 0.59 * $g + 0.11 * $b) < 128;
    }
}
