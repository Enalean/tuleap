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
        $this->content = substr($this->content, 0, -1);
        $this->content .= "\n";
    }

    /**
     * Reset the content
     *
     * @return void
     */
    function clearContent() {
        $this->content = '';
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