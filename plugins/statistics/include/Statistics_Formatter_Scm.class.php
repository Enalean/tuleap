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

require_once __DIR__ . '/../../../src/www/project/export/project_export_utils.php';

/**
 * SCM statistics for SVN or CVS
 */
class Statistics_Formatter_Scm extends Statistics_Formatter
{

    private $scm;
    protected $dao;

    /**
     * Constructor of the class
     *
     * @param String  $scm       'svn' or 'cvs'
     * @param String  $startDate Period start date
     * @param String  $endDate   Period end date
     * @param int $groupId Project Id
     *
     * @return void
     */
    public function __construct($scm, $startDate, $endDate, $groupId = null)
    {
        $this->scm = $scm;
        parent::__construct($startDate, $endDate, get_csv_separator(), $groupId);
    }

    /**
     * Calculate statistics for read access
     *
     * @return Array
     */
    public function calculateReadStats()
    {
        $read_user_label    = $GLOBALS['Language']->getText('plugin_statistics', 'scm_svn_read_user');
        $total_read_label   = $GLOBALS['Language']->getText('plugin_statistics', 'scm_svn_total_read');
        $read_project_label = $GLOBALS['Language']->getText('plugin_statistics', 'scm_svn_read_project');
        if ($this->scm === 'cvs') {
            $read_user_label    = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_read_user');
            $total_read_label   = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_total_read');
            $read_project_label = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_read_project');
        }

        $readIndex[]          = $GLOBALS['Language']->getText('plugin_statistics', 'scm_month');
        $totalRead[]          = $total_read_label;
        $readProjectsNumber[] = $read_project_label;
        $readUsersNumber[]    = $read_user_label;
        $readDar              = $this->dao->totalRead($this->startDate, $this->endDate);
        if ($readDar && !$readDar->isError()) {
            foreach ($readDar as $row) {
                $readIndex[]          = $row['month'] . " " . $row['year'];
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
    public function calculateCommitsStats()
    {
        $commit_user_label    = $GLOBALS['Language']->getText('plugin_statistics', 'scm_svn_commit_user');
        $total_commit_label   = $GLOBALS['Language']->getText('plugin_statistics', 'scm_svn_total_commit');
        $commit_project_label = $GLOBALS['Language']->getText('plugin_statistics', 'scm_svn_commit_project');
        if ($this->scm === 'cvs') {
            $commit_user_label    = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_commit_user');
            $total_commit_label   = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_total_commit');
            $commit_project_label = $GLOBALS['Language']->getText('plugin_statistics', 'scm_cvs_commit_project');
        }

        $commitsIndex[]         = $GLOBALS['Language']->getText('plugin_statistics', 'scm_month');
        $totalCommits[]         = $total_commit_label;
        $commitProjectsNumber[] = $commit_project_label;
        $commitUsersNumber[]    = $commit_user_label;
        $commitsDar             = $this->dao->totalCommits($this->startDate, $this->endDate);
        if ($commitsDar && !$commitsDar->isError()) {
            foreach ($commitsDar as $row) {
                $commitsIndex[]         = $row['month'] . " " . $row['year'];
                $commitProjectsNumber[] = $row['projects'];
                $commitUsersNumber[]    = $row['users'];
                $totalCommits[]         = intval($row['count']);
            }
        }
        $result = array('commits_index'          => $commitsIndex,
                        'total_commits'          => $totalCommits,
                        'commit_projects_number' => $commitProjectsNumber,
                        'commit_users_number'    => $commitUsersNumber);
        return $result;
    }

    /**
     * Calculate top commits by project
     *
     * @return Array
     */
    public function topCommitByProject()
    {
        $result['project'][] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_top_commit_project');
        $result['commits'][] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_top_commit_project_commits');
        $commitsDar = $this->dao->commitsByProject($this->startDate, $this->endDate);
        if ($commitsDar && !$commitsDar->isError()) {
            foreach ($commitsDar as $row) {
                if ($row) {
                    $result['project'][] = $row['project'];
                    $result['commits'][] = $row['count'];
                }
            }
        }
        return $result;
    }

    /**
     * Calculate top commits by user
     *
     * @return Array
     */
    public function topCommitByUser()
    {
        $result['user'][]    = $GLOBALS['Language']->getText('plugin_statistics', 'scm_top_commit_user');
        $result['commits'][] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_top_commit_user_commits');
        $commitsDar = $this->dao->commitsByUser($this->startDate, $this->endDate);
        if ($commitsDar && !$commitsDar->isError()) {
            foreach ($commitsDar as $row) {
                if ($row) {
                    $result['user'][]    = $row['user'];
                    $result['commits'][] = $row['count'];
                }
            }
        }
        return $result;
    }

    /**
     * Total repositories having commits in the given period
     *
     * @return Array
     */
    public function repositoriesWithCommit()
    {
        $repositories[] = $GLOBALS['Language']->getText('plugin_statistics', 'scm_repo_total');
        $count = 0;
        $dar = $this->dao->repositoriesWithCommit($this->startDate, $this->endDate);
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            $row = $dar->getRow();
            if ($row) {
                $count = $row['count'];
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
    public function getStats()
    {
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
            $this->addLine($this->repositoriesWithCommit());
            $projectStats = $this->topCommitByProject();
            $this->addLine($projectStats['project']);
            $this->addLine($projectStats['commits']);
        }
        $userStats = $this->topCommitByUser();
        $this->addLine($userStats['user']);
        $this->addLine($userStats['commits']);

        return $this->getCsvContent();
    }
}
