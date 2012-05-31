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

require_once 'Statistics_ProjectQuotaDao.class.php';

/**
 * Management of custom quota by project
 */
class ProjectQuotaManager {

    /**
     * The Projects dao used to fetch data
     */
    protected $dao;

    /**
     * ProjectManager instance
     */
    protected $pm;

    /**
     * ProjectQuotaManager constructor
     */
    public function __construct() {
        $this->dao = $this->getDao();
        $this->pm   = ProjectManager::instance();
    }

    /**
     * List all projects having custom quota
     *
     * @param Array  $list     List of projects Id corresponding to a filter
     * @param int    $offset   From where the result will be displayed.
     * @param int    $count    How many results are returned.
     * @param String $sort     Order result set according to this parameter
     * @param String $sortSens Specifiy if the result set sort is ascending or descending
     *
     * @return DataAccessResult
     */
    public function getAllCustomQuota($list = array(), $offset = null, $count = null, $sort = null, $sortSens = null) {
        return $this->dao->getAllCustomQuota($list, $offset, $count, $sort, $sortSens);
    }

    /**
     * Retrieve custom quota for a given project
     *
     * @param int $groupId ID of the project we want to retrieve its custom quota
     *
     * @return Integer
     */
    public function getProjectCustomQuota($groupId) {
        $allowedQuota = null;
        $res = $this->dao->getProjectCustomQuota($groupId);
        if ($res && !$res->isError() && $res->rowCount() == 1) {
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
     * @param Integer $quota      Quota to be set for the project
     * @param String  $motivation Why the custom quota was requested
     *
     * @return Void
     */
    public function addQuota($project, $requester, $quota, $motivation) {
        $maxQuota = $this->getMaximumQuota();
        if (empty($project)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'invalid_project'));
        } elseif (empty($quota)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'invalid_quota', $maxQuota));
        } elseif (strlen($motivation) > 512) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'invalid_motivation'));
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
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'invalid_quota', $maxQuota));
                } else {
                    if ($this->dao->addException($project->getGroupID(), $userId, $quota, $motivation)) {
                        $historyDao = new ProjectHistoryDao(CodendiDataAccess::instance());
                        $historyDao->groupAddHistory("add_custom_quota", $quota, $project->getGroupID());
                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_statistics', 'quota_added', array($project->getPublicName(), $quota)));
                    } else {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'add_error'));
                    }
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'no_project'));
            }
        }
    }
    
    /**
     * Get the maximum quota defined for the plateform
     *
     * @return int
     */
    public function getMaximumQuota() {
        $dum      = new Statistics_DiskUsageManager();
        $maxQuota = intval($dum->getProperty('maximum_quota'));
        if (!isset($maxQuota)) {
            $maxQuota = 50;
        }
        return $maxQuota;
    }

    /**
     * Delete custom quota for a project
     *
     * @param Array $projects List of projects for which custom quota will be deleted
     *
     * @return Void
     */
    public function deleteCustomQuota($projects) {
        if (empty($projects)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'nothing_to_delete'));
        } else {
            $list         = array();
            $names        = array();
            $dum          = new Statistics_DiskUsageManager();
            $defaultQuota = $dum->getProperty('allowed_quota');
            $historyDao   = new ProjectHistoryDao(CodendiDataAccess::instance());
            foreach ($projects as $projectId => $name) {
                $list[]  = $projectId;
                $names[] = $name;
                $historyDao->groupAddHistory("restore_default_quota", intval($defaultQuota), $projectId);
            }
            if ($this->dao->deleteCustomQuota($list)) {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_statistics', 'quota_deleted', array(join(', ', $names))));
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'delete_error'));
            }
        }
    }

    /**
     * @return Dao
     */
    public function getDao() {
        if (!isset($this->dao)) {
            $this->dao = new Statistics_ProjectQuotaDao();
        }
        return $this->dao;
    }
}

?>