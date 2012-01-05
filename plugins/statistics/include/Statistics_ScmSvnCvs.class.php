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
require_once 'Statistics_ScmSvnDao.class.php';
require_once 'Statistics_ScmCvsDao.class.php';

/**
 * SCM statistics for SVN or CVS
 */
class Statistics_ScmSvnCvs extends Statistics_Scm {

    var $scm;
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
     * Constructor of the class
     *
     * @param String  $scm       'svn' or 'cvs'
     * @param String  $startDate Period start date
     * @param String  $endDate   Period end date
     * @param Integer $groupId   Project Id
     *
     * @return void
     */
    function __construct($scm, $startDate, $endDate, $groupId = null) {
        $this->scm = $scm;
        parent::__construct($startDate, $endDate, $groupId);
    }

    /**
     * Calculate total read access and commits
     *
     * @return void
     */
    function calculateTotalAccess() {
        $this->periods[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_period');
        $this->totalRead[]    = $GLOBALS['Language']->getText('plugin_statistics', 'scm_'.$this->scm.'_total_read');
        $this->totalCommits[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_'.$this->scm.'_total_commit');
        foreach ($this->dates as $begin => $end) {
            if ($begin) {
                $this->periods[] = $begin."::".$end;
                $readDar      = $this->dao->totalRead($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($readDar && !$readDar->isError()) {
                    $read = 0;
                    foreach ($readDar as $row) {
                        if ($this->scm == 'svn') {
                            $read += intval($row['svn_checkouts + svn_access_count + svn_browse']);
                        } else {
                            $read += intval($row['cvs_checkouts + cvs_browse']);
                        }
                    }
                    $this->totalRead[] = $read;
                } else {
                    $this->totalRead[] = 0;
                }
                $commitsDar = $this->dao->totalCommits($this->convertDateForDao($begin), $this->convertDateForDao($end));
                if ($commitsDar && !$commitsDar->isError()) {
                    $commits = 0;
                    foreach ($commitsDar as $row) {
                        if ($this->scm == 'svn') {
                            $commits += intval($row['svn_commits + svn_adds + svn_deletes']);
                        } else {
                            $commits += intval($row['cvs_commits + cvs_adds']);
                        }
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
        $this->readProjectsNumber[]   = $GLOBALS['Language']->getText('plugin_statistics', 'scm_'.$this->scm.'_read_project');
        $this->commitProjectsNumber[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_'.$this->scm.'_commit_project');
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
        $this->readUsersNumber[]   = $GLOBALS['Language']->getText('plugin_statistics', 'scm_'.$this->scm.'_read_user');
        $this->commitUsersNumber[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_'.$this->scm.'_commit_user');
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
     * Add stats for SVN or CVS in CSV format
     *
     * @return String
     */
    function getStats() {
        $this->dates = $this->splitPeriodByMonths();
        if ($this->scm == 'svn') {
            $this->dao   = new Statistics_ScmSvnDao(CodendiDataAccess::instance(), $this->groupId);
            $this->addLine(array('SVN'));
        } else {
            $this->dao   = new Statistics_ScmCvsDao(CodendiDataAccess::instance(), $this->groupId);
            $this->addLine(array('CVS'));
        }
        $this->calculateTotalAccess();

        if (!$this->groupId) {
            $this->calculateAccessByProject();
        }

        $this->calculateAccessByUser();

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