<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'Column.class.php';
require_once 'ColumnStatusCollection.class.php';
require_once 'ColumnFreestyleCollection.class.php';
require_once TRACKER_BASE_DIR .'/Tracker/Tracker.class.php';

class Cardwall_OnTop_Config_ColumnFactory {

    const DEFAULT_BGCOLOR = 'white';
    const LIGHT_FGCOLOR   = 'white';
    const DARK_FGCOLOR    = 'black';

    /**
     * @var Cardwall_OnTop_ColumnDao
     */
    private $dao;

    public function __construct(Cardwall_OnTop_ColumnDao $dao) {
        $this->dao = $dao;
    }

    public function getColumns(Tracker $tracker) {
        $columns = $this->getColumnsFromDao($tracker);
        if (! count($columns)) {
            $status_columns = $this->getColumnsFromStatusField($tracker);
            if (count($status_columns)) {
                $columns = $status_columns;
            }
        }
        return $columns;
    }

    /**
     * @return array of Cardwall_OnTop_Config_Column
     */
    private function getColumnsFromDao(Tracker $tracker) {
        $columns = new Cardwall_OnTop_Config_ColumnFreestyleCollection();
        foreach ($this->dao->searchColumnsByTrackerId($tracker->getId()) as $row) {
            list($bgcolor, $fgcolor) = $this->getColumnColorsFromRow($row);
            $columns[] = new Cardwall_OnTop_Config_Column($row['id'], $row['label'], $bgcolor, $fgcolor);
        }
        return $columns;
    }

    /**
     * @return array of Cardwall_OnTop_Config_Column
     */
    public function getColumnsFromStatusField(Tracker $tracker) {
        $columns = new Cardwall_OnTop_Config_ColumnStatusCollection();
        $field   = $tracker->getStatusField();
        if ($field) {
            $decorators = $field->getDecorators();
            foreach($field->getVisibleValuesPlusNoneIfAny() as $value) {
                list($bgcolor, $fgcolor) = $this->getColumnColorsFromListValue($value, $decorators);
                $columns[] = new Cardwall_OnTop_Config_Column($value->getId(), $value->getLabel(), $bgcolor, $fgcolor);
            }
        }
        return $columns;
    }

    private function getColumnColorsFromListValue($value, $decorators) {
        $id      = (int)$value->getId();
        $bgcolor = self::DEFAULT_BGCOLOR;
        $fgcolor = self::DARK_FGCOLOR;
        if (isset($decorators[$id])) {
            $bgcolor = $decorators[$id]->css($bgcolor);
            $fgcolor = $decorators[$id]->isDark() ? self::LIGHT_FGCOLOR : self::DARK_FGCOLOR;
        }
        return array($bgcolor, $fgcolor);
    }

    private function getColumnColorsFromRow($row) {
        $bgcolor = self::DEFAULT_BGCOLOR;
        $fgcolor = self::DARK_FGCOLOR;
        $r = $row['bg_red'];
        $g = $row['bg_green'];
        $b = $row['bg_blue'];
        if ($r !== null && $g !== null && $b !== null) {
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
?>
