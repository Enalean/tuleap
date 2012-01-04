<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
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

require_once 'pre.php';
require_once('www/project/export/project_export_utils.php');

/**
 * SCM statistics
 */
class Statistics_Scm {

    var $separator;
    var $content;
    var $startDate;
    var $endDate;
    var $groupId = null;

    /**
     * Constructor of the class
     *
     * @param String  $startDate Period start date
     * @param String  $endDate   Period end date
     * @param Integer $groupId   Project Id
     *
     * @return void
     */
    function __construct($startDate, $endDate, $groupId = null) {
        $this->separator = get_csv_separator();
        $this->content   = '';
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->groupId   = $groupId;
        $this->addLine(array());
    }

    /**
     * Add a line to the content
     *
     * @param Array $line Array containing the elements of a csv line
     *
     * @return void
     */
    function addLine($line) {
        foreach ($line as $element) {
            $this->content .= tocsv($element).$this->separator;
        }
        $this->content = substr($this->content,0,-1);
        $this->content .= "\n";
    }

    /**
     * Split the given period by months
     *
     * @return Array
     */
    function splitPeriodByMonths() {
        $dates          = array();
        $year           = intval(substr($this->startDate,0,4));
        $referenceYear  = intval(substr($this->endDate,0,4));
        $month          = intval(substr($this->startDate,5,2));
        $referenceMonth = intval(substr($this->endDate,5,2));
        $day            = intval(substr($this->startDate,8,2));
        $referenceDay   = intval(substr($this->endDate,8,2));
        $date           = 10000 * $year + 100 * $month + $day;
        $reference      = 10000 * $referenceYear + 100 * $referenceMonth + $referenceDay;
        $dates[0]       = $year."-".str_pad($month, 2, "0", STR_PAD_LEFT)."-".str_pad($day, 2, "0", STR_PAD_LEFT);
        $last           = $dates[0];
        $first          = true;
        while ($date <= $reference) {
            while ($month <= 12 && $date <= $reference) {
                if (!$first) {
                    $dates[$last] = $year."-".str_pad($month, 2, "0", STR_PAD_LEFT)."-01";
                    $last         = $dates[$last];
                }
                $first = false;
                $month ++;
                $date  = 10000 * $year + 100 * $month + $day;
            }
            $year ++;
            $month = 1;
        }
        $dates[$last] = $referenceYear."-".str_pad($referenceMonth, 2, "0", STR_PAD_LEFT)."-".str_pad($referenceDay, 2, "0", STR_PAD_LEFT);
        return $dates;
    }

    /**
     * Convert dates from the format 'yyyy-mm-dd' to 'yyyymmdd'
     *
     * @param String $date date with format 'yyyy-mm-dd'
     *
     * @return String
     */
    function convertDateForDao($date) {
        return str_replace('-', '', $date);
    }

    /**
     * Obtain statistics in csv format
     *
     * @return String
     */
    function getStats() {
        return $this->content;
    }

}

?>