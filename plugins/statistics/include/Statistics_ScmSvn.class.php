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
        $dates[0] = $year."-".str_pad($month, 2, "0", STR_PAD_LEFT)."-".str_pad($day, 2, "0", STR_PAD_LEFT);
        $last = $dates[0];
        $first = true;
        while ($date <= $reference) {
            while ($month <= 12 && $date <= $reference) {
                if (!$first) {
                    $dates[$last] = $year."-".str_pad($month, 2, "0", STR_PAD_LEFT)."-01";
                    $last = $dates[$last];
                }
                $first = false;
                $month ++;
                $date = 10000 * $year + 100 * $month + $day;
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
     * Add stats for SVN in CSV format
     *
     * @return String
     */
    function getStats() {
        $dates = $this->splitPeriodByMonths();
        $dao = new Statistics_ScmSvnDao();
        $this->addLine(array('SVN'));
        $csvPeriods[]      = "Periods";
        $csvTotalCommits[] = "Total number of SVN commits";
        $csvTotalRead[]    = "Total number of SVN read access";
        foreach ($dates as $begin => $end) {
            if ($begin) {
                $csvPeriods[] = $begin."->".$end;
                $commitsDar = $dao->totalCommits($this->convertDateForDao($begin), $this->convertDateForDao($end));
                $readDar    = $dao->totalRead($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if (db_numrows($commitsDar) > 0) {
                    $commits = 0;
                    while ($row = db_fetch_array($commitsDar)) {
                        $commits += intval($row['svn_commits']) + intval($row['svn_adds']) + intval($row['svn_deletes']);
                    }
                    $csvTotalCommits[] = $commits;
                } else {
                    $csvTotalCommits[] = 0;
                }
            if (db_numrows($readDar) > 0) {
                    $read = 0;
                    while ($row = db_fetch_array($readDar)) {
                        $read += intval($row['svn_checkouts']) + intval($row['svn_access_count']) + intval($row['svn_browse']);
                    }
                    $csvTotalRead[] = $read;
                } else {
                    $csvTotalRead[] = 0;
                }
            }
        }
        $this->addLine($csvPeriods);
        $this->addLine($csvTotalRead);
        $this->addLine($csvTotalCommits);

        $this->addLine(array(''));

        $dar = $dao->commitsByProject($this->convertDateForDao($this->startDate), $this->convertDateForDao($this->endDate));
        if (db_numrows($dar) > 0) {
            $this->addLine(array('Project', 'Number of commits'));
            $nb = 0;
            while ($row = db_fetch_array($dar)) {
                $info = array();
                $info['Project'] = $row['Project'];
                $info['Commits'] = intval($row['commits']) + intval($row['adds']) + intval($row['deletes']);
                $nb              += $info['Commits'];
                $this->addLine($info);
            }
            $this->addLine(array(''));
            $this->addLine(array('Total number projects', db_numrows($dar)));
            $this->addLine(array('Total number of commits', $nb));
        }

        $this->addLine(array(''));

        $dar = $dao->commitsByUser($this->convertDateForDao($this->startDate), $this->convertDateForDao($this->endDate));
        if (db_numrows($dar) > 0) {
            $this->addLine(array('User', 'Number of commits'));
            $nb = 0;
            while ($row = db_fetch_array($dar)) {
                $info = array();
                $info['User']    = $row['User'];
                $info['Commits'] = intval($row['commits']) + intval($row['adds']) + intval($row['deletes']);
                $nb              += $info['Commits'];
                $this->addLine($info);
            }
            $this->addLine(array(''));
            $this->addLine(array('Total number Users', db_numrows($dar)));
            $this->addLine(array('Total number of commits', $nb));
        }
        return $this->content;
    }

}

?>