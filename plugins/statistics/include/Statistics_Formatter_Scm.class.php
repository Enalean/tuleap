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
 * SCM statistics for SVN
 */
class Statistics_Formatter_Scm extends Statistics_Formatter
{
    protected $dao;

    /**
     * Constructor of the class
     *
     * @param String  $startDate Period start date
     * @param String  $endDate   Period end date
     * @param int $groupId Project Id
     *
     * @return void
     */
    public function __construct($startDate, $endDate, $groupId = null)
    {
        parent::__construct($startDate, $endDate, get_csv_separator(), $groupId);
    }

    /**
     * Calculate statistics for read access
     *
     * @return Array
     */
    public function calculateReadStats()
    {
        $read_user_label    = dgettext('tuleap-statistics', 'Total number of users with SVN read access');
        $total_read_label   = dgettext('tuleap-statistics', 'Total number of SVN read access');
        $read_project_label = dgettext('tuleap-statistics', 'Total number of projects with SVN read access');

        $readIndex[]          = dgettext('tuleap-statistics', 'Month');
        $totalRead[]          = $total_read_label;
        $readProjectsNumber[] = $read_project_label;
        $readUsersNumber[]    = $read_user_label;
        $readDar              = $this->dao->totalRead($this->startDate, $this->endDate);
        if ($readDar && ! $readDar->isError()) {
            foreach ($readDar as $row) {
                $readIndex[]          = $row['month'] . " " . $row['year'];
                $readProjectsNumber[] = $row['projects'];
                $readUsersNumber[]    = $row['users'];
                $totalRead[]          = intval($row['count']);
            }
        }
        $result = ['read_index'           => $readIndex,
            'total_read'           => $totalRead,
            'read_projects_number' => $readProjectsNumber,
            'read_users_number'    => $readUsersNumber,
        ];
        return $result;
    }

    /**
     * Calculate statistics for commits
     *
     * @return Array
     */
    public function calculateCommitsStats()
    {
        $commit_user_label    = dgettext('tuleap-statistics', 'Total number of users with SVN commits');
        $total_commit_label   = dgettext('tuleap-statistics', 'Total number of SVN commits');
        $commit_project_label = dgettext('tuleap-statistics', 'Total number of projects with SVN commits');

        $commitsIndex[]         = dgettext('tuleap-statistics', 'Month');
        $totalCommits[]         = $total_commit_label;
        $commitProjectsNumber[] = $commit_project_label;
        $commitUsersNumber[]    = $commit_user_label;
        $commitsDar             = $this->dao->totalCommits($this->startDate, $this->endDate);
        if ($commitsDar && ! $commitsDar->isError()) {
            foreach ($commitsDar as $row) {
                $commitsIndex[]         = $row['month'] . " " . $row['year'];
                $commitProjectsNumber[] = $row['projects'];
                $commitUsersNumber[]    = $row['users'];
                $totalCommits[]         = intval($row['count']);
            }
        }
        $result = ['commits_index'          => $commitsIndex,
            'total_commits'          => $totalCommits,
            'commit_projects_number' => $commitProjectsNumber,
            'commit_users_number'    => $commitUsersNumber,
        ];
        return $result;
    }

    /**
     * Calculate top commits by project
     *
     * @return Array
     */
    public function topCommitByProject()
    {
        $result['project'][] = dgettext('tuleap-statistics', 'Top projects');
        $result['commits'][] = dgettext('tuleap-statistics', 'Top projects (number of commits)');
        $commitsDar          = $this->dao->commitsByProject($this->startDate, $this->endDate);
        if ($commitsDar && ! $commitsDar->isError()) {
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
        $result['user'][]    = dgettext('tuleap-statistics', 'Top users');
        $result['commits'][] = dgettext('tuleap-statistics', 'Top users (number of commits)');
        $commitsDar          = $this->dao->commitsByUser($this->startDate, $this->endDate);
        if ($commitsDar && ! $commitsDar->isError()) {
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
        $repositories[] = dgettext('tuleap-statistics', 'Total number of repositories containing commits');
        $count          = 0;
        $dar            = $this->dao->repositoriesWithCommit($this->startDate, $this->endDate);
        if ($dar && ! $dar->isError() && $dar->rowCount() > 0) {
            $row = $dar->getRow();
            if ($row) {
                $count = $row['count'];
            }
        }
        $repositories[] = $count;
        return $repositories;
    }

    /**
     * Add stats for SVN in CSV format
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

        if (! $this->groupId) {
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
