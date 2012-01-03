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
                $readDar      = $dao->totalRead($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if (db_numrows($readDar) > 0) {
                    $read = 0;
                    while ($row = db_fetch_array($readDar)) {
                        $read += intval($row['svn_checkouts']) + intval($row['svn_access_count']) + intval($row['svn_browse']);
                    }
                    $csvTotalRead[] = $read;
                } else {
                    $csvTotalRead[] = 0;
                }
                $commitsDar = $dao->totalCommits($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if (db_numrows($commitsDar) > 0) {
                    $commits = 0;
                    while ($row = db_fetch_array($commitsDar)) {
                        $commits += intval($row['svn_commits']) + intval($row['svn_adds']) + intval($row['svn_deletes']);
                    }
                    $csvTotalCommits[] = $commits;
                } else {
                    $csvTotalCommits[] = 0;
                }
            }
        }

        $csvReadProjectsNumber[]   = "Total number of projects with SVN read access";
        $csvTopReadByProject[]     = "Top project (number of read access)";
        $csvTopProjectRead[]       = "Top number of read access by project";
        $csvCommitProjectsNumber[] = "Total number of projects with SVN commits";
        $csvTopCommitByProject[]   = "Top project (number of commits)";
        $csvTopProjectCommits[]    = "Top number of commits by project";
        foreach ($dates as $begin => $end) {
            if ($begin) {
                $numberOfReadProjects = 0;
                $topRead              = 0;
                $topReadProject       = '';
                $readDar              = $dao->readByProject($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if (db_numrows($readDar) > 0) {
                    while ($row = db_fetch_array($readDar)) {
                        $nb = intval($row['checkouts']) + intval($row['access']) + intval($row['browses']);
                        if ($nb > $topRead) {
                            $topRead        = $nb;
                            $topReadProject = $row['Project'];
                        }
                        $numberOfReadProjects ++;
                    }
                }
                $csvReadProjectsNumber[] = $numberOfReadProjects;
                $csvTopReadByProject[]   = $topReadProject;
                $csvTopProjectRead[]     = $topRead;

                $numberOfCommitProjects = 0;
                $topCommits             = 0;
                $topCommitProject       = '';
                $commitsDar             = $dao->commitsByProject($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if (db_numrows($commitsDar) > 0) {
                    while ($row = db_fetch_array($commitsDar)) {
                        $nb = intval($row['commits']) + intval($row['adds']) + intval($row['deletes']);
                        if ($nb > $topCommits) {
                            $topCommits       = $nb;
                            $topCommitProject = $row['Project'];
                        }
                        $numberOfCommitProjects ++;
                    }
                }
                $csvCommitProjectsNumber[] = $numberOfCommitProjects;
                $csvTopCommitByProject[]   = $topCommitProject;
                $csvTopProjectCommits[]    = $topCommits;
            }
        }

        $csvReadUsersNumber[]   = "Total number of userss with SVN read access";
        $csvTopReadByUser[]     = "Top user (number of read access)";
        $csvTopUserRead[]       = "Top number of read access by user";
        $csvCommitUsersNumber[] = "Total number of users with SVN commits";
        $csvTopCommitByUser[]   = "Top user (number of commits)";
        $csvTopUserCommits[]    = "Top number of commits by user";
        foreach ($dates as $begin => $end) {
            if ($begin) {
                $numberOfReadUsers = 0;
                $topRead           = 0;
                $topReadUser       = '';
                $readDar           = $dao->readByUser($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if (db_numrows($readDar) > 0) {
                    while ($row = db_fetch_array($readDar)) {
                        $nb = intval($row['checkouts']) + intval($row['access']) + intval($row['browses']);
                        if ($nb > $topRead) {
                            $topRead     = $nb;
                            $topReadUser = $row['User'];
                        }
                        $numberOfReadUsers ++;
                    }
                }
                $csvReadUsersNumber[] = $numberOfReadUsers;
                $csvTopReadByUser[]   = $topReadUser;
                $csvTopUserRead[]     = $topRead;

                $numberOfCommitUsers = 0;
                $topCommits          = 0;
                $topCommitUser       = '';
                $commitsDar          = $dao->commitsByUser($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if (db_numrows($commitsDar) > 0) {
                    while ($row = db_fetch_array($commitsDar)) {
                        $nb = intval($row['commits']) + intval($row['adds']) + intval($row['deletes']);
                        if ($nb > $topCommits) {
                            $topCommits       = $nb;
                            $topCommitUser    = $row['User'];
                        }
                        $numberOfCommitUsers ++;
                    }
                }
                $csvCommitUsersNumber[] = $numberOfCommitUsers;
                $csvTopCommitByUser[]     = $topCommitUser;
                $csvTopUserCommits[]    = $topCommits;
            }
        }

        $this->addLine($csvPeriods);
        $this->addLine($csvTotalRead);
        $this->addLine($csvReadProjectsNumber);
        $this->addLine($csvTopReadByProject);
        $this->addLine($csvTopProjectRead);
        $this->addLine($csvReadUsersNumber);
        $this->addLine($csvTopReadByUser);
        $this->addLine($csvTopUserRead);
        $this->addLine($csvTotalCommits);
        $this->addLine($csvCommitProjectsNumber);
        $this->addLine($csvTopCommitByProject);
        $this->addLine($csvTopProjectCommits);
        $this->addLine($csvCommitUsersNumber);
        $this->addLine($csvTopCommitByUser);
        $this->addLine($csvTopUserCommits);

        return $this->content;
    }

}

?>