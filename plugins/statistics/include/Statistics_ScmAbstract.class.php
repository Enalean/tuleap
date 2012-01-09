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

/**
 * SCM statistics for SVN or CVS
 */
abstract class Statistics_ScmAbstract extends Statistics_Scm {

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
        $readDar              = $this->dao->totalRead($this->startDate, $this->endDate);
        if ($readDar && !$readDar->isError()) {
            foreach ($readDar as $row) {
                $readIndex[]          = $row['month']." ".$row['year'];
                $readProjectsNumber[] = $row['projects'];
                $readUsersNumber[]    = $row['users'];
                $totalRead[]          = intval($row['count']);
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
        $commitsDar = $this->dao->totalCommits($this->startDate, $this->endDate);
        if ($commitsDar && !$commitsDar->isError()) {
            foreach ($commitsDar as $row) {
                $commitsIndex[]         = $row['month']." ".$row['year'];
                $commitProjectsNumber[] = $row['projects'];
                $commitUsersNumber[]    = $row['users'];
                $totalCommits[]         = intval($row['count']);
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
        $commitsDar = $this->dao->commitsByProject($this->startDate, $this->endDate);
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
        $commitsDar = $this->dao->commitsByUser($this->startDate, $this->endDate);
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
     * Repositories activity evolution
     *
     * @return Array
     */
    function repositoriesEvolutionForPeriod() {
        $dar = $this->dao->repositoriesEvolutionForPeriod($this->startDate, $this->endDate);
        $evolution = array();
        if ($dar && !$dar->isError() && $dar->rowCount()> 0) {
            $evolution[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_repo_evolution');
            foreach ($dar as $row) {
                $evolution[] = $row['repo_count'];
            }
        }
        return $evolution;
    }

    /**
     * Total repositories having commits in the given period
     *
     * @return Array
     */
    function repositoriesWithCommit() {
        $repositories[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_repo_total');
        $count = 0;
        $dar = $this->dao->repositoriesWithCommit($this->startDate, $this->endDate, true);
        if ($dar && !$dar->isError() && $dar->rowCount()> 0) {
            foreach ($dar as $row) {
                $count += intval($row['count']);
            }
        }
        $repositories[] = $count;
        return $repositories;
    }

    /**
     * Add stats for SVN or CVS in CSV format
     *
     * @return String
     */
    function getStats() {
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
            $this->addLine($this->repositoriesEvolutionForPeriod());
            $this->addLine($this->repositoriesWithCommit());
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