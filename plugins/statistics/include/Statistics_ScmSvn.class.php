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
     * Add stats for SVN in CSV format
     *
     * @return String
     */
    function getStats() {
        $dates = $this->splitPeriodByMonths();
        $dao = new Statistics_ScmSvnDao(CodendiDataAccess::instance(), $this->groupId);
        $this->addLine(array('SVN'));
        $csvPeriods[]      = $GLOBALS['Language']->getText('plugin_statistics', 'scm_period');
        $csvTotalRead[]    = $GLOBALS['Language']->getText('plugin_statistics', 'scm_svn_total_read');
        $csvTotalCommits[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_svn_total_commit');
        foreach ($dates as $begin => $end) {
            if ($begin) {
                $csvPeriods[] = $begin."->".$end;
                $readDar      = $dao->totalRead($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($readDar && !$readDar->isError()) {
                    $read = 0;
                    foreach ($readDar as $row) {
                        $read += intval($row['svn_checkouts + svn_access_count + svn_browse']);
                    }
                    $csvTotalRead[] = $read;
                } else {
                    $csvTotalRead[] = 0;
                }
                $commitsDar = $dao->totalCommits($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($commitsDar && !$commitsDar->isError()) {
                    $commits = 0;
                    foreach ($commitsDar as $row) {
                        $commits += intval($row['svn_commits + svn_adds + svn_deletes']);
                    }
                    $csvTotalCommits[] = $commits;
                } else {
                    $csvTotalCommits[] = 0;
                }
            }
        }

        if (!$this->groupId) {
            $csvReadProjectsNumber[]   = $GLOBALS['Language']->getText('plugin_statistics', 'scm_svn_read_project');
            $csvCommitProjectsNumber[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_svn_commit_project');
            $rank = 1;
            while ($rank <= 10) {
                $csvTopCommitByProject[$rank][] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_svn_top_commit_project')." #".$rank;
                $rank ++;
            }
            foreach ($dates as $begin => $end) {
                if ($begin) {
                    $numberOfReadProjects = 0;
                    $readDar              = $dao->readByProject($this->convertDateForDao($begin), $this->convertDateForDao($end));
                    if ($readDar && !$readDar->isError()) {
                        $numberOfReadProjects = $readDar->rowCount();
                    }
                    $csvReadProjectsNumber[] = $numberOfReadProjects;

                    $numberOfCommitProjects = 0;
                    $commitsDar             = $dao->commitsByProject($this->convertDateForDao($begin), $this->convertDateForDao($end));
                    if ($commitsDar && !$commitsDar->isError()) {
                        $rank = 1;
                        while ($rank <= 10) {
                            if ($row = $commitsDar->getRow()) {
                                $csvTopCommitByProject[$rank][] = $row['project'];
                            } else {
                                $csvTopCommitByProject[$rank][] = '';
                            }
                            $rank ++;
                        }
                        $numberOfCommitProjects = $commitsDar->rowCount();
                    }
                    $csvCommitProjectsNumber[] = $numberOfCommitProjects;
                }
            }
        }

        $csvReadUsersNumber[]   = $GLOBALS['Language']->getText('plugin_statistics', 'scm_svn_read_user');
        $csvCommitUsersNumber[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_svn_commit_user');
        $rank = 1;
        while ($rank <= 10) {
            $csvTopCommitByUser[$rank][] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_svn_top_commit_user')." #".$rank;
            $rank ++;
        }
        foreach ($dates as $begin => $end) {
            if ($begin) {
                $numberOfReadUsers = 0;
                $readDar           = $dao->readByUser($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($readDar && !$readDar->isError()) {
                    $numberOfReadUsers = $readDar->rowCount();
                }
                $csvReadUsersNumber[] = $numberOfReadUsers;

                $numberOfCommitUsers = 0;
                $commitsDar          = $dao->commitsByUser($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($commitsDar && !$commitsDar->isError()) {
                    $rank = 1;
                    while ($rank <= 10) {
                        if ($row = $commitsDar->getRow()) {
                            $csvTopCommitByUser[$rank][] = $row['user'];
                        } else {
                            $csvTopCommitByUser[$rank][] = '';
                        }
                        $rank ++;
                    }
                    $numberOfCommitUsers = $commitsDar->rowCount();
                }
                $csvCommitUsersNumber[] = $numberOfCommitUsers;
            }
        }

        $this->addLine($csvPeriods);
        $this->addLine($csvTotalRead);
        if (!$this->groupId) {
            $this->addLine($csvReadProjectsNumber);
        }
        $this->addLine($csvReadUsersNumber);
        $this->addLine($csvTotalCommits);
        if (!$this->groupId) {
            $this->addLine($csvCommitProjectsNumber);
            foreach ($csvTopCommitByProject as $line) {
                $this->addLine($line);
            }
        }
        $this->addLine($csvCommitUsersNumber);
        foreach ($csvTopCommitByUser as $line) {
            $this->addLine($line);
        }

        return $this->content;
    }

}

?>