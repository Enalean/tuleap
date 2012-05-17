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
    public $_dao;

    /**
     * ProjectManager instance
     */
    protected $pm;

    /**
     * A private constructor; prevents direct creation of object
     */
    private function __construct() {
        $this->_dao = $this->_getDao();
        $this->pm   = ProjectManager::instance();
    }
    /**
     * Hold an instance of the class
     */
    private static $_instance;

    /**
     * ProjectManager is a singleton
     *
     * @return ProjectManager
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
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
        return $this->_dao->getAllCustomQuota($list, $offset, $count, $sort, $sortSens);
    }

    /**
     * Retrieve custom quota for a given project
     *
     * @param int $groupId ID of the project we want to retrieve its custom quota
     *
     * @return DataAccessResult
     */
    public function getProjectCustomQuota($groupId) {
        return $this->_dao->getProjectCustomQuota($groupId);
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
        if (empty($project)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'invalid_project'));
        } elseif (empty($quota)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'invalid_quota'));
        } elseif (strlen($motivation) > 512) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'invalid_motivation'));
        } else {
            $project = $this->pm->getProjectFromAutocompleter($project);
            if ($project) {
                $userId = null;
                $um     = UserManager::instance();
                $user   = $um->findUser($requester);
                $userId = 100;
                if ($user) {
                    $userId = $user->getId();
                }
                $dum      = new Statistics_DiskUsageManager();
                $maxQuota = $dum->getProperty('maximum_quota');
                if (!$maxQuota) {
                    $maxQuota = 50;
                }
                if ($quota > $maxQuota) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'invalid_quota'));
                } else {
                    if ($this->_dao->addException($project->getGroupID(), $userId, $quota, $motivation)) {
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
            if ($this->_dao->deleteCustomQuota($list)) {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_statistics', 'quota_deleted', array(join(', ', $names))));
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_statistics', 'delete_error'));
            }
        }
    }

    /**
     * @return Dao
     */
    public function _getDao() {
        if (!isset($this->_dao)) {
            $this->_dao = new Statistics_ProjectQuotaDao(CodendiDataAccess::instance());
        }
        return $this->_dao;
    }
}

?>
