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

require_once 'Statistics_Scm.class.php';
require_once 'Statistics_ScmCvsDao.class.php';

/**
 * SCM statistics for CVS
 */
class Statistics_ScmCvs extends Statistics_Scm {

    /**
     * Add stats for CVS in CSV format
     *
     * @return String
     */
    function getStats() {
        $dates = $this->splitPeriodByMonths();
        $dao = new Statistics_ScmCvsDao(CodendiDataAccess::instance(), $this->groupId);
        $this->addLine(array('CVS'));
        $csvPeriods[]      = $GLOBALS['Language']->getText('plugin_statistics', 'scm_period');
        $csvTotalRead[]    = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_total_read');
        $csvTotalCommits[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_total_commit');
        foreach ($dates as $begin => $end) {
            if ($begin) {
                $csvPeriods[] = $begin."->".$end;
                $readDar      = $dao->totalRead($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($readDar && !$readDar->isError()) {
                    $read = 0;
                    foreach ($readDar as $row) {
                        $read += intval($row['cvs_checkouts + cvs_browse']);
                    }
                    $csvTotalRead[] = $read;
                } else {
                    $csvTotalRead[] = 0;
                }
                $commitsDar = $dao->totalCommits($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($commitsDar && !$commitsDar->isError()) {
                    $commits = 0;
                    foreach ($commitsDar as $row) {
                        $commits += intval($row['cvs_commits + cvs_adds']);
                    }
                    $csvTotalCommits[] = $commits;
                } else {
                    $csvTotalCommits[] = 0;
                }
            }
        }

        if (!$this->groupId) {
            $csvReadProjectsNumber[]   = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_read_project');
            $csvCommitProjectsNumber[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_commit_project');
            $rank = 1;
            while ($rank <= 10) {
                $csvTopCommitByProject[$rank][] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_top_commit_project')." #".$rank;
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

        $csvReadUsersNumber[]   = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_read_user');
        $csvCommitUsersNumber[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_commit_user');
        $rank = 1;
        while ($rank <= 10) {
            $csvTopCommitByUser[$rank][] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_top_commit_user')." #".$rank;
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