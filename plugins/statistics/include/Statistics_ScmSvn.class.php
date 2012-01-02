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
require_once 'Statistics_ScmSvnDao.class.php';

/**
 * SCM statistics for SVN
 */
class Statistics_ScmSvn {

    var $separator;
    var $content;
    var $startDate;
    var $endDate;

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
        $this->separator      = get_csv_separator();
        $this->content        = '';
        $this->startDate      = $startDate;
        $this->endDate        = $endDate;
    }

    /**
     * Return the header of the CSV file
     *
     * @return void
     */
    function getHeader() {
        $this->addLine(array('Period'));
        $this->addLine(array('', 'From', 'To'));
        $this->addLine(array('', $this->startDate, $this->endDate));
    }

    /**
     * Add a line to the content
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
     * Add stats for SVN in CSV format
     *
     * @return String
     */
    function getStats() {
        $dao = new Statistics_ScmSvnDao();
        $this->addLine(array(''));
        $this->addLine(array('SVN'));
        $dar = $dao->returnStatsFromDB($this->startDate, $this->endDate);
        if (db_numrows($dar) > 0) {
            $this->addLine(array('', 'Total number of commits'));
            while ($row = db_fetch_array($dar)) {
                $this->addLine(array('', $row['commits']));
            }
        }

        $this->addLine(array(''));

        $dar = $dao->returnStatsFromDBByProject($this->startDate, $this->endDate);
        if (db_numrows($dar) > 0) {
            $this->addLine(array('', 'Project', 'Number of commits'));
            $nb = 0;
            while ($row = db_fetch_array($dar)) {
                $info            = array();
                $info['blank']   = '';
                $project = $row['Project'];
                $info['Project'] = $project;
                $info['Commits'] = $row['Commits'];
                $nb              += intval($row['Commits']);
                $this->addLine($info);
            }
            $this->addLine(array(''));
            $this->addLine(array('', 'Total number projects', db_numrows($dar)));
            $this->addLine(array('', 'Total number of commits', $nb));
            $this->addLine(array('', 'Average number of commits by project', round($nb/db_numrows($dar))));
        }

        $this->addLine(array(''));

        $dar = $dao->returnStatsFromDBByUser($this->startDate, $this->endDate);
        if (db_numrows($dar) > 0) {
            $this->addLine(array('', 'User', 'Number of commits'));
            $nb = 0;
            while ($row = db_fetch_array($dar)) {
                $info            = array();
                $info['blank']   = '';
                $info['User']    = $row['User'];
                $info['Commits'] = $row['Commits'];
                $nb              += intval($row['Commits']);
                $this->addLine($info);
            }
            $this->addLine(array(''));
            $this->addLine(array('', 'Total number Users', db_numrows($dar)));
            $this->addLine(array('', 'Total number of commits', $nb));
            $this->addLine(array('', 'Average number of commits by user', round($nb/db_numrows($dar))));
        }
        return $this->content;
    }

}

?>