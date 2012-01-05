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

    var $dao;
    var $dates;
    var $periods;
    var $totalRead;
    var $totalCommits;
    var $readProjectsNumber;
    var $commitProjectsNumber;
    var $topCommitByProject;
    var $readUsersNumber;
    var $commitUsersNumber;
    var $topCommitByUser;

    /**
     * Calculate total read access and commits
     *
     * @return void
     */
    function calculateTotalAccess() {
        $this->periods[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_period');
        $this->totalRead[]    = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_total_read');
        $this->totalCommits[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_total_commit');
        foreach ($this->dates as $begin => $end) {
            if ($begin) {
                $this->periods[] = $begin." -> ".$end;
                $readDar      = $this->dao->totalRead($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($readDar && !$readDar->isError()) {
                    $read = 0;
                    foreach ($readDar as $row) {
                        $read += intval($row['cvs_checkouts + cvs_browse']);
                    }
                    $this->totalRead[] = $read;
                } else {
                    $this->totalRead[] = 0;
                }
                $commitsDar = $this->dao->totalCommits($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($commitsDar && !$commitsDar->isError()) {
                    $commits = 0;
                    foreach ($commitsDar as $row) {
                        $commits += intval($row['cvs_commits + cvs_adds']);
                    }
                    $this->totalCommits[] = $commits;
                } else {
                    $this->totalCommits[] = 0;
                }
            }
        }
    }

    /**
     * Calculate read access and commits by project
     *
     * @return void
     */
    function calculateAccessByProject() {
        $this->readProjectsNumber[]   = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_read_project');
        $this->commitProjectsNumber[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_commit_project');
        $rank = 1;
        while ($rank <= 10) {
            $this->topCommitByProject[$rank][] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_top_commit_project')." #".$rank;
            $rank ++;
        }
        foreach ($this->dates as $begin => $end) {
            if ($begin) {
                $numberOfReadProjects = 0;
                $readDar              = $this->dao->readByProject($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($readDar && !$readDar->isError()) {
                    $numberOfReadProjects = $readDar->rowCount();
                }
                $this->readProjectsNumber[] = $numberOfReadProjects;

                $numberOfCommitProjects = 0;
                $commitsDar             = $this->dao->commitsByProject($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($commitsDar && !$commitsDar->isError()) {
                    $rank = 1;
                    while ($rank <= 10) {
                        if ($row = $commitsDar->getRow()) {
                            $this->topCommitByProject[$rank][] = $row['project'];
                        } else {
                            $this->topCommitByProject[$rank][] = '';
                        }
                        $rank ++;
                    }
                    $numberOfCommitProjects = $commitsDar->rowCount();
                }
                $this->commitProjectsNumber[] = $numberOfCommitProjects;
            }
        }
    }

    /**
     * Calculate read access and commits by user
     *
     * @return void
     */
    function calculateAccessByUser() {
        $this->readUsersNumber[]   = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_read_user');
        $this->commitUsersNumber[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_commit_user');
        $rank = 1;
        while ($rank <= 10) {
            $this->topCommitByUser[$rank][] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_top_commit_user')." #".$rank;
            $rank ++;
        }
        foreach ($this->dates as $begin => $end) {
            if ($begin) {
                $numberOfReadUsers = 0;
                $readDar           = $this->dao->readByUser($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($readDar && !$readDar->isError()) {
                    $numberOfReadUsers = $readDar->rowCount();
                }
                $this->readUsersNumber[] = $numberOfReadUsers;

                $numberOfCommitUsers = 0;
                $commitsDar          = $this->dao->commitsByUser($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($commitsDar && !$commitsDar->isError()) {
                    $rank = 1;
                    while ($rank <= 10) {
                        if ($row = $commitsDar->getRow()) {
                            $this->topCommitByUser[$rank][] = $row['user'];
                        } else {
                            $this->topCommitByUser[$rank][] = '';
                        }
                        $rank ++;
                    }
                    $numberOfCommitUsers = $commitsDar->rowCount();
                }
                $this->commitUsersNumber[] = $numberOfCommitUsers;
            }
        }
    }

    /**
     * Add stats for CVS in CSV format
     *
     * @return String
     */
    function getStats() {
        $this->dates = $this->splitPeriodByMonths();
        $this->dao   = new Statistics_ScmCvsDao(CodendiDataAccess::instance(), $this->groupId);
        $this->calculateTotalAccess();

        if (!$this->groupId) {
            $this->calculateAccessByProject();
        }

        $this->calculateAccessByUser();

        $this->addLine(array('CVS'));
        $this->addLine($this->periods);
        $this->addLine($this->totalRead);
        if (!$this->groupId) {
            $this->addLine($this->readProjectsNumber);
        }
        $this->addLine($this->readUsersNumber);
        $this->addLine($this->totalCommits);
        if (!$this->groupId) {
            $this->addLine($this->commitProjectsNumber);
            foreach ($this->topCommitByProject as $line) {
                $this->addLine($line);
            }
        }
        $this->addLine($this->commitUsersNumber);
        foreach ($this->topCommitByUser as $line) {
            $this->addLine($line);
        }

        return $this->content;
    }

}

?>