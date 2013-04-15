<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

require_once('common/dao/UGroupDao.class.php');
require_once('common/project/UGroupManager.class.php');

/**
 * UGroup binding
 */
class UGroupBinding {

    private $ugroupUserDao;
    private $ugroupManager;

    public function __construct(UGroupUserDao $ugroup_user_dao, UGroupManager $ugroup_manager) {
        $this->ugroupUserDao = $ugroup_user_dao;
        $this->ugroupManager = $ugroup_manager;
    }

    /**
     * Obtain UGroupUserDao
     *
     * @return UGroupUserDao
     */
    public function getUGroupUserDao() {
        return $this->ugroupUserDao;
    }

    /**
     * Obtain UGroupManager
     *
     * @return UGroupManager
     */
    public function getUGroupManager() {
        return $this->ugroupManager;
    }

   /**
     * Check if the user group is valid
     *
     * @param Integer $groupId  Id of the project
     * @param Integer $ugroupId Id of the user goup
     *
     * @return Boolean
     */
    public function checkUGroupValidity($groupId, $ugroupId) {
        return $this->getUGroupManager()->checkUGroupValidityByGroupId($groupId, $ugroupId);
    }

    /**
     * Retrieve All Ugroups bound to a given Ugroup
     *
     * @param Integer $ugroupId Id of the source user goup
     *
     * @return Array
     */
    public function getUGroupsByBindingSource($ugroupId) {
        $dar     = $this->getUGroupManager()->searchUGroupByBindingSource($ugroupId);
        $ugroups = array();
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                $cloneId = $row['ugroup_id'];
                $ugroups[$cloneId]['cloneName'] = $row['name'];
                $ugroups[$cloneId]['group_id']  = $row['group_id'];
            }
        }
        return $ugroups;
    }

    /**
     * Remove all Ugroups binding to a given Ugroup
     *
     * @param Integer $ugroupId Id of the source user group
     *
     * @return boolean
     */
    public function removeAllUGroupsBinding($ugroupId) {
        $bindedUgroups  = $this->getUGroupsByBindingSource($ugroupId);
        $bindingRemoved = true;
        if (!empty($bindedUgroups)) {
            foreach ($bindedUgroups as $ugroupKey => $ugroupData) {
                if (!$this->getUGroupManager()->updateUgroupBinding($ugroupKey)) {
                    $bindingRemoved = false;
                }
            }
        }
        return $bindingRemoved;
    }

    /**
     * Remove binding to all ugroups of a given project
     *
     * @param Integer $groupId Id of the project
     *
     * @return boolean
     */
    public function removeProjectUGroupsBinding($groupId) {
        $ugroups        = $this->getUGroupManager()->getExistingUgroups($groupId);
        $bindingRemoved = true;
        foreach ($ugroups as $ugroup) {
            if (!$this->removeAllUGroupsBinding($ugroup['ugroup_id'])) {
                $bindingRemoved = false;
            }
        }
        return $bindingRemoved;
    }

    /**
     * Update the user list of all ugroups  bound to a given user group
     *
     * @param Integer $ugroupId Id of the source user group
     *
     * @return boolean
     */
    public function updateBindedUGroups($ugroupId) {
        $bindedUgroups = $this->getUGroupsByBindingSource($ugroupId);
        if (!empty($bindedUgroups)) {
            foreach ($bindedUgroups as $ugroupKey => $ugroupData) {
                try {
                    $this->reloadUgroupBinding($ugroupKey, $ugroupId);
                } catch (Exception $e) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get title of the link to binding interface
     *
     * @param Integer $ugroupId Id of the user group
     *
     * @return String
     */
    public function getLinkTitle($ugroupId) {
        $ugroup = new UGroup(array('ugroup_id' => $ugroupId));
        if ($ugroup->isBound()) {
            $action = 'update_binding';
        } else {
            $action = 'add_binding';
        }
        return '- '.$GLOBALS['Language']->getText('project_ugroup_binding', $action);
    }

    /**
     * Remove all users from a given user group
     *
     * @param Integer $ugroupId Id of the user group
     *
     * @return void
     */
    public function resetUgroup($ugroupId) {
        if ($this->getUGroupManager()->isUpdateUsersAllowed($ugroupId)) {
            if (!$this->getUGroupUserDao()->resetUgroupUserList($ugroupId)) {
                throw new LogicException($GLOBALS['Language']->getText('project_ugroup_binding', 'reset_error', array($ugroupId)));
            }
        } else {
            throw new RuntimeException($GLOBALS['Language']->getText('project_ugroup_binding', 'update_user_not_allowed', array($ugroupId)));
        }
    }

    /**
     * Clone a given user group
     *
     * @param Integer $sourceId Id of the source user group
     * @param Integer $ugroupId Id of the bound user group
     *
     * @return void
     */
    public function cloneUgroup($sourceId, $ugroupId) {
        if ($this->getUGroupManager()->isUpdateUsersAllowed($ugroupId)) {
            if (!$this->getUGroupUserDao()->cloneUgroup($sourceId, $ugroupId)) {
                throw new LogicException($GLOBALS['Language']->getText('project_ugroup_binding', 'clone_error', array($ugroupId)));
            }
        } else {
            throw new RuntimeException($GLOBALS['Language']->getText('project_ugroup_binding', 'update_user_not_allowed', array($ugroupId)));
        }
    }

    /**
     * Mark a given user group as bound to another one
     *
     * @param Integer $ugroupId Id of the bound user group
     * @param Integer $sourceId Id of the source user group
     *
     * @return void
     */
    public function updateUgroupBinding($ugroupId, $sourceId) {
        if (!$this->getUGroupManager()->updateUgroupBinding($ugroupId, $sourceId)) {
            throw new Exception('Unable to store ugroup binding');
        }
    }

    /**
     * Remove all users from a given user group then make a copy from another one
     *
     * @param Integer $ugroupId Id of the bound user group
     * @param Integer $sourceId Id of the source user group
     *
     * @return void
     */
    public function reloadUgroupBinding($ugroupId, $sourceId) {
        try {
            $this->resetUgroup($ugroupId);
            $this->cloneUgroup($sourceId, $ugroupId);
            $cascadingBindedUgroups = $this->getUGroupsByBindingSource($ugroupId);
            foreach ($cascadingBindedUgroups as $cascadUgroupKey => $ugroupData) {
                $this->reloadUgroupBinding($cascadUgroupKey, $ugroupId);
            }

        } catch (LogicException $e) {
            //re-throw exception
            throw new Exception($e->getMessage());
        } catch (RuntimeException $e) {
            $GLOBALS['Response']->addFeedback('warning', $e->getMessage());
            throw new Exception($GLOBALS['Language']->getText('project_ugroup_binding', 'add_error'));
        }
    }

    /**
     * Bind a given user group to another one
     *
     * @param Integer $ugroupId Id of the bound user group
     * @param Integer $sourceId Id of the source user group
     *
     * @return boolean
     */
    public function addBinding($ugroupId, $sourceId) {
        $source_ugroup = $this->getUGroupManager()->getUgroupBindingSource($ugroupId);
        if ($source_ugroup && $source_ugroup->getId() == $sourceId) {
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('project_ugroup_binding', 'duplicate_binding_warning', array($sourceId)));
            return false;
        }
        try {
            $this->reloadUgroupBinding($ugroupId, $sourceId);
            $this->updateUgroupBinding($ugroupId, $sourceId);
        } catch (Exception $e) {
            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
            return false;
        }
        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_ugroup_binding', 'binding_added'));
        return true;
    }

    /**
     * Remove binding for a given user group
     *
     * @param Integer $ugroupId Id of the user group we want to remove its binding
     *
     * @return boolean
     */
    public function removeBinding($ugroupId) {
        if ($this->getUGroupManager()->updateUgroupBinding($ugroupId)) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_ugroup_binding', 'binding_removed'));
            return true;
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_ugroup_binding', 'remove_error'));
            return false;
        }
    }
}

?>