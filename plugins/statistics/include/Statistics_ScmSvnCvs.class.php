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
     * Calculate statistics for read access
     *
     * @return Array
     */
    function calculateReadStats() {
        $readIndex[]          = $GLOBALS['Language']->getText('plugin_statistics', 'scm_month');
        $totalRead[]          = $GLOBALS['Language']->getText('plugin_statistics', 'scm_'.$this->scm.'_total_read');
        $readProjectsNumber[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_'.$this->scm.'_read_project');
        $readUsersNumber[]    = $GLOBALS['Language']->getText('plugin_statistics', 'scm_'.$this->scm.'_read_user');
        $readDar              = $this->dao->totalRead($this->convertDateForDao($this->startDate), $this->convertDateForDao($this->endDate));
        if ($readDar && !$readDar->isError()) {
            foreach ($readDar as $row) {
                $readIndex[]          = $row['year']."-".$row['month'];
                $readProjectsNumber[] = $row['projects'];
                $readUsersNumber[]    = $row['users'];
                if ($this->scm == 'svn') {
                    $totalRead[] = intval($row['svn_checkouts + svn_access_count + svn_browse']);
                } else {
                    $totalRead[] = intval($row['cvs_checkouts + cvs_browse']);
                }
            }
        }
        $result = array('read_index'           => $readIndex,
                        'total_read'           => $totalRead,
                        'read_projects_number' => $readProjectsNumber,
                        'read_users_number'    => $readUsersNumber);
        return $result;
    }

    /**
     * Calculate statistics for commits
     *
     * @return Array
     */
    function calculateCommitsStats() {
        $commitsIndex[]         = $GLOBALS['Language']->getText('plugin_statistics', 'scm_month');
        $totalCommits[]         = $GLOBALS['Language']->getText('plugin_statistics', 'scm_'.$this->scm.'_total_commit');
        $commitProjectsNumber[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_'.$this->scm.'_commit_project');
        $commitUsersNumber[]    = $GLOBALS['Language']->getText('plugin_statistics', 'scm_'.$this->scm.'_commit_user');
        if ($this->scm == 'svn') {
            $commitsDar = $this->dao->totalCommits(strtotime($this->startDate), strtotime($this->endDate));
        } else {
            $commitsDar = $this->dao->totalCommits($this->convertDateForDao($this->startDate), $this->convertDateForDao($this->endDate));
        }
        if ($commitsDar && !$commitsDar->isError()) {
            foreach ($commitsDar as $row) {
                $commitsIndex[]         = $row['year']."-".$row['month'];
                $commitProjectsNumber[] = $row['projects'];
                $commitUsersNumber[]    = $row['users'];
                if ($this->scm == 'svn') {
                    $totalCommits[] = intval($row['count']);
                } else {
                    $totalCommits[] = intval($row['cvs_commits + cvs_adds']);
                }
            }
        }
        $result = array('commits_index'           => $commitsIndex,
                        'total_commits'           => $totalCommits,
                        'commit_projects_number' => $commitProjectsNumber,
                        'commit_users_number'    => $commitUsersNumber);
        return $result;
    }

    /**
     * Calculate top commits by project
     *
     * @return Array
     */
    function topCommitByProject() {
        $rank = 1;
        while ($rank <= 10) {
            $result[$rank][] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_top_commit_project')." #".$rank;
            $rank ++;
        }
        if ($this->scm == 'svn') {
            $commitsDar = $this->dao->commitsByProject(strtotime($this->startDate), strtotime($this->endDate));
        } else {
            $commitsDar = $this->dao->commitsByProject($this->convertDateForDao($this->startDate), $this->convertDateForDao($this->endDate));
        }
        if ($commitsDar && !$commitsDar->isError()) {
            $rank = 1;
            while ($rank <= 10) {
                if ($row = $commitsDar->getRow()) {
                    $result[$rank][] = $row['project'];
                } else {
                    $result[$rank][] = '';
                }
                $rank ++;
            }
        }
        return $result;
    }

    /**
     * Calculate top commits by user
     *
     * @return Array
     */
    function topCommitByUser() {
        $rank = 1;
        while ($rank <= 10) {
            $result[$rank][] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_top_commit_user')." #".$rank;
            $rank ++;
        }
        if ($this->scm == 'svn') {
            $commitsDar = $this->dao->commitsByUser(strtotime($this->startDate), strtotime($this->endDate));
        } else {
            $commitsDar = $this->dao->commitsByUser($this->convertDateForDao($this->startDate), $this->convertDateForDao($this->endDate));
        }
        if ($commitsDar && !$commitsDar->isError()) {
            $rank = 1;
            while ($rank <= 10) {
                if ($row = $commitsDar->getRow()) {
                    $result[$rank][] = $row['user'];
                } else {
                    $result[$rank][] = '';
                }
                $rank ++;
            }
        }
        return $result;
    }

    /**
     * Add stats for SVN or CVS in CSV format
     *
     * @return String
     */
    function getStats() {
        if ($this->scm == 'svn') {
            $this->dao = new Statistics_ScmSvnDao(CodendiDataAccess::instance(), $this->groupId);
            $this->addLine(array('SVN'));
        } else {
            $this->dao = new Statistics_ScmCvsDao(CodendiDataAccess::instance(), $this->groupId);
            $this->addLine(array('CVS'));
        }
        $readStats = $this->calculateReadStats();
        $this->addLine($readStats['read_index']);
        $this->addLine($readStats['total_read']);
        $this->addLine($readStats['read_projects_number']);
        $this->addLine($readStats['read_users_number']);
        $commitStats = $this->calculateCommitsStats();
        $this->addLine($commitStats['commits_index']);
        $this->addLine($commitStats['total_commits']);
        $this->addLine($commitStats['commit_projects_number']);
        $this->addLine($commitStats['commit_users_number']);
        if (!$this->groupId) {
            foreach ($this->topCommitByProject() as $line) {
                $this->addLine($line);
            }
        }
        foreach ($this->topCommitByUser() as $line) {
            $this->addLine($line);
        }

        return $this->content;
    }

}

?>