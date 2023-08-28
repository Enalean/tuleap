<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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

use Tuleap\Statistics\DiskUsage\Subversion\Collector as SVNCollector;
use Tuleap\Statistics\DiskUsage\Subversion\Retriever as SVNRetriever;

/**
 * Management of custom quota by project
 */
class ProjectQuotaManager
{
    /**
     * The Projects dao used to fetch data
     *
     * @var Statistics_ProjectQuotaDao
     */
    protected $dao;

    /**
     * ProjectManager instance
     *
     * @var ProjectManager
     */
    protected $pm;

    /**
     * Statistics_DiskUsageManager instance
     *
     * @var Statistics_DiskUsageManager
     */
    protected $diskUsageManager;

    /**
     * ProjectQuotaManager constructor
     */
    public function __construct()
    {
        $this->dao = $this->getDao();
        $this->pm  = ProjectManager::instance();

        $disk_usage_dao = new Statistics_DiskUsageDao();
        $svn_log_dao    = new SVN_LogDao();
        $svn_retriever  = new SVNRetriever($disk_usage_dao);
        $svn_collector  = new SVNCollector($svn_log_dao, $svn_retriever);

        $this->diskUsageManager = new Statistics_DiskUsageManager(
            $disk_usage_dao,
            $svn_collector,
            EventManager::instance()
        );
    }

    /**
     * Retrieve the authorized disk quota for a project
     *
     * @param int $group_id The ID of the project we are looking for its quota
     *
     * @return int
     */
    public function getProjectAuthorizedQuota($group_id)
    {
        $quota = $this->getProjectCustomQuota($group_id);
        if (empty($quota)) {
            $quota = $this->getDefaultQuota();
        }
        return $this->convertQuotaToGiB($quota);
    }

    /**
     * Convert a given quota size in bi to Gib
     *
     * @param int $size The quota size in bi
     *
     * @return Float
     */
    private function convertQuotaToGiB($size)
    {
        return $size * 1024 * 1024 * 1024;
    }

    /**
     * Check if a given project is overquota given it
     *
     * @param int $current_size The current disk size of the project in bi
     * @param int $allowed_size The allowed disk size of the project in bi
     *
     * @return bool
     */
    private function isProjectOverQuota($current_size, $allowed_size)
    {
        if (! empty($current_size) && ($current_size > $allowed_size)) {
            return true;
        }
        return false;
    }

    /**
     * @return Array
     */
    private function getProjectOverQuotaRow(Project $project, $current_size, $allowed_size)
    {
        $usage_output          = new Statistics_DiskUsageOutput($this->diskUsageManager);
        $over_quota_disk_space = $current_size - $allowed_size;
        $exceed_percent        = round(($over_quota_disk_space / $allowed_size), 2) * 100;
        $projectRow            = [
            'project_unix_name'  => $project->getUnixNameMixedCase(),
            'project_name'       => $project->getPublicName(),
            'project_id'         => $project->getGroupId(),
            'exceed'             => $exceed_percent . '%',
            'disk_quota'         => $usage_output->sizeReadable($allowed_size),
            'current_disk_space' => $usage_output->sizeReadable($current_size),
        ];

        return $projectRow;
    }

    /**
     * @return Array
     */
    public function getProjectsOverQuota()
    {
        $all_groups         = $this->fetchProjects();
        $exceeding_projects = [];
        foreach ($all_groups as $key => $group) {
            $current_size = $this->diskUsageManager->returnTotalProjectSize($group['group_id']);
            $allowed_size = $this->getProjectAuthorizedQuota($group['group_id']);
            if ($this->isProjectOverQuota($current_size, $allowed_size)) {
                $project                  = $this->pm->getProject($group['group_id']);
                $exceeding_projects[$key] = $this->getProjectOverQuotaRow($project, $current_size, $allowed_size);
            }
        }

        return $exceeding_projects;
    }

    private function fetchProjects()
    {
        return $this->diskUsageManager->_getDao()->searchAllGroups();
    }

    /**
     * Retrieve custom quota for a given project
     *
     * @param int $groupId ID of the project we want to retrieve its custom quota
     *
     * @return int|null
     */
    public function getProjectCustomQuota($groupId)
    {
        $allowedQuota = null;
        $res          = $this->dao->getProjectCustomQuota($groupId);
        if ($res && ! $res->isError() && $res->rowCount() == 1) {
            $row          = $res->getRow();
            $allowedQuota = $row[Statistics_ProjectQuotaDao::REQUEST_SIZE];
        }
        return $allowedQuota;
    }

    /**
     * Add custom quota for a project
     *
     * @param String  $project    Project for which quota will be customized
     * @param String  $requester  User that asked for the custom quota
     * @param int $quota Quota to be set for the project
     * @param String  $motivation Why the custom quota was requested
     *
     * @return Void
     */
    public function addQuota($project, $requester, $quota, $motivation)
    {
        $maxQuota = $this->getMaximumQuota();
        if (empty($project)) {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-statistics', 'Invalid project'));
        } elseif (empty($quota)) {
            $GLOBALS['Response']->addFeedback('error', sprintf(dgettext('tuleap-statistics', 'Quota must be between 1 and %1$s Gb'), $maxQuota));
        } else {
            $project = $this->pm->getProjectFromAutocompleter($project);
            if ($project) {
                $userId = null;
                $um     = UserManager::instance();
                $user   = $um->findUser($requester);
                if ($user) {
                    $userId = $user->getId();
                } else {
                    $user   = $um->getCurrentUser();
                    $userId = $user->getId();
                }
                if ($quota > $maxQuota) {
                    $GLOBALS['Response']->addFeedback('error', sprintf(dgettext('tuleap-statistics', 'Quota must be between 1 and %1$s Gb'), $maxQuota));
                } else {
                    if ($this->dao->addException($project->getGroupID(), $userId, $quota, $motivation)) {
                        $historyDao = new ProjectHistoryDao();
                        $historyDao->groupAddHistory("add_custom_quota", $quota, $project->getGroupID());
                        $GLOBALS['Response']->addFeedback('info', sprintf(dgettext('tuleap-statistics', 'Quota for project "%1$s" is now %2$s GB'), $project->getPublicName(), $quota));
                    } else {
                        $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-statistics', 'An error occurred when adding the entry'));
                    }
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-statistics', 'Project not found'));
            }
        }
    }

    /**
     * Get the default quota defined for the platform
     *
     * @return int
     */
    public function getDefaultQuota()
    {
        $quota = intval($this->diskUsageManager->getProperty('allowed_quota'));
        if (! $quota) {
            $quota = 5;
        }
        return $quota;
    }

    /**
     * Get the maximum quota defined for the platform
     *
     * @return int
     */
    public function getMaximumQuota()
    {
        $maxQuota = intval($this->diskUsageManager->getProperty('maximum_quota'));
        if (! $maxQuota) {
            $maxQuota = 50;
        }
        return $maxQuota;
    }

    public function deleteCustomQuota(Project $project)
    {
        $defaultQuota = $this->diskUsageManager->getProperty('allowed_quota');
        $historyDao   = new ProjectHistoryDao();
        if ($this->dao->deleteCustomQuota($project->getId())) {
            $historyDao->groupAddHistory("restore_default_quota", intval($defaultQuota), $project->getId());
            $GLOBALS['Response']->addFeedback('info', sprintf(dgettext('tuleap-statistics', 'Quota deleted for %1$s'), $project->getPublicName()));
        } else {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-statistics', 'An error occurred when deleting entries'));
        }
    }

    /**
     * @return Statistics_ProjectQuotaDao
     */
    public function getDao()
    {
        if (! isset($this->dao)) {
            $this->dao = new Statistics_ProjectQuotaDao();
        }
        return $this->dao;
    }
}
