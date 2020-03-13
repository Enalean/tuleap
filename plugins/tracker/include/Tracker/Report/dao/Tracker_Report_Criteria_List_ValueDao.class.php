<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class Tracker_Report_Criteria_List_ValueDao extends Tracker_Report_Criteria_ValueDao
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_report_criteria_list_value';
    }

    public function save($id, $values)
    {
        if (is_array($values)) {
            $id = $this->da->escapeInt($id);
            //First clear the list
            $sql = "DELETE FROM $this->table_name WHERE criteria_id = $id";
            $this->update($sql);

            //Then fill it with new values
            $new_values = array();
            if (is_array($values)) {
                foreach ($values as $val) {
                    if ($v = $this->da->escapeInt($val)) {
                        $new_values[] = "($id, $v)";
                    }
                }
            }
            $sql = '';
            if (count($new_values)) {
                $sql = "INSERT INTO $this->table_name(criteria_id, value) VALUES " . implode(',', $new_values);
            }
            $r = null;
            if ($sql != '') {
                $r = $this->update($sql);
            }
            return $r;
        }
        return false;
    }
}
