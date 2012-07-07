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
require_once TRACKER_BASE_DIR .'/Tracker/Tracker.class.php';

class Cardwall_OnTop_Config_ColumnFactory {

    /**
     * @var Cardwall_OnTop_ColumnDao
     */
    private $dao;

    public function __construct(Cardwall_OnTop_ColumnDao $dao) {
        $this->dao = $dao;
    }

    public function getColumns(Tracker $tracker) {
        $columns = $this->getColumnsFromDao($tracker);
        if (! $columns) {
            $columns = $this->getColumnsFromStatusField($tracker);
        }
        return $columns;
    }

    /**
     * @return array of Cardwall_OnTop_Config_Column
     */
    private function getColumnsFromDao(Tracker $tracker) {
        $columns = array();
        foreach ($this->dao->searchColumnsByTrackerId($tracker->getId()) as $row) {
            $columns[] = new Cardwall_OnTop_Config_Column($row['id'], $row['label']);
        }
        return $columns;
    }

    /**
     * @return array of Cardwall_OnTop_Config_Column
     */
    public function getColumnsFromStatusField(Tracker $tracker) {
        $columns = array();
        $field   = $tracker->getStatusField();
        if ($field) {
            foreach($field->getVisibleValuesPlusNoneIfAny() as $value) {
                $columns[] = new Cardwall_OnTop_Config_Column($value->getId(), $value->getLabel());
            }
        }
        return $columns;
    }
}
?>
