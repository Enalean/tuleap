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

class Tracker_Report_Criteria_Date_ValueDao extends Tracker_Report_Criteria_ValueDao
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_report_criteria_date_value';
    }

    public function save($id, $value)
    {
        $id        = $this->da->escapeInt($id);

        if (isset($value['op'])) {
            $op = $this->da->quoteSmart($value['op']);
        } else {
            $op = '';
        }

        if (isset($value['from_date'])) {
            $from_date = $this->da->escapeInt($value['from_date']);
        } else {
            $from_date = '';
        }

        if (isset($value['to_date'])) {
            $to_date = $this->da->escapeInt($value['to_date']);
        } else {
            $to_date = '';
        }

        if ($to_date != '') {
            $sql = "REPLACE INTO $this->table_name(criteria_id, from_date, to_date, op) VALUES ($id, $from_date, $to_date, $op)";
        } else {
            $sql = "DELETE FROM $this->table_name WHERE criteria_id = $id";
        }
        return $this->update($sql);
    }
}
