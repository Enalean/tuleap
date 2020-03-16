<?php
/*
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
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

class Docman_FilterDao extends DataAccessObject
{

    public function searchByReportId($id)
    {
        $sql = sprintf(
            'SELECT *' .
                       ' FROM plugin_docman_report_filter' .
                       ' WHERE report_id = %d' .
                       ' ORDER BY label ASC',
            $id
        );
        return $this->retrieve($sql);
    }

    public function createFilterList($reportId, $label, $love)
    {
        $sql = sprintf(
            'INSERT INTO plugin_docman_report_filter' .
                       ' (report_id, label, value_love)' .
                       ' VALUES' .
                       ' (%d, %s, %d)',
            $reportId,
            $this->da->quoteSmart($label),
            $love
        );
        return $this->update($sql);
    }

    public function createFilterText($reportId, $label, $string)
    {
        $sql = sprintf(
            'INSERT INTO plugin_docman_report_filter' .
                       ' (report_id, label, value_string)' .
                       ' VALUES' .
                       ' (%d, %s, %s)',
            $reportId,
            $this->da->quoteSmart($label),
            $this->da->quoteSmart($string)
        );
        return $this->update($sql);
    }

    public function createFilterDate($reportId, $label, $date, $op)
    {
        $sql = sprintf(
            'INSERT INTO plugin_docman_report_filter' .
                       ' (report_id, label, value_date1, value_date_op)' .
                       ' VALUES' .
                       ' (%d, %s, %s, %d)',
            $reportId,
            $this->da->quoteSmart($label),
            $this->da->quoteSmart($date),
            $op
        );
        return $this->update($sql);
    }

    public function createFilterDateAdvanced($reportId, $label, $date1, $date2)
    {
        $sql = sprintf(
            'INSERT INTO plugin_docman_report_filter' .
                       ' (report_id, label, value_date1, value_date2)' .
                       ' VALUES' .
                       ' (%d, %s, %s, %s)',
            $reportId,
            $this->da->quoteSmart($label),
            $this->da->quoteSmart($date1),
            $this->da->quoteSmart($date2)
        );
        return $this->update($sql);
    }

    public function truncateFilters($reportId)
    {
        $sql = sprintf(
            'DELETE FROM plugin_docman_report_filter' .
                       ' WHERE report_id = %d',
            $reportId
        );
        return $this->update($sql);
    }
}
