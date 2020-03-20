<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

use Tuleap\Project\UGroups\Binding\BoundUGroupRefresher;
use Tuleap\Project\UGroups\Binding\RecursiveBoundUGroupsRefresher;

/**
 * ProjectUGroup binding
 */
class UGroupBinding //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{

    private $ugroupUserDao;
    private $ugroupManager;

    public function __construct(UGroupUserDao $ugroup_user_dao, UGroupManager $ugroup_manager)
    {
        $this->ugroupUserDao = $ugroup_user_dao;
        $this->ugroupManager = $ugroup_manager;
    }

    /**
     * Obtain UGroupUserDao
     *
     * @return UGroupUserDao
     */
    public function getUGroupUserDao()
    {
        return $this->ugroupUserDao;
    }

    /**
     * Obtain UGroupManager
     *
     * @return UGroupManager
     */
    public function getUGroupManager()
    {
        return $this->ugroupManager;
    }

    /**
     * Check if the user group is valid
     *
     * @param int $groupId Id of the project
     * @param int $ugroupId Id of the user goup
     *
     * @return bool
     */
    public function checkUGroupValidity($groupId, $ugroupId)
    {
        return $this->getUGroupManager()->checkUGroupValidityByGroupId($groupId, $ugroupId);
    }

    /**
     * Retrieve All Ugroups bound to a given ProjectUGroup
     *
     * @param int $ugroupId Id of the source user goup
     *
     * @return Array
     */
    public function getUGroupsByBindingSource($ugroupId)
    {
        $dar     = $this->getUGroupManager()->searchUGroupByBindingSource($ugroupId);
        $ugroups = array();
        if ($dar && !$dar->isError()) {
            foreach ($dar as $row) {
                $cloneId                        = $row['ugroup_id'];
                $ugroups[$cloneId]['cloneName'] = $row['name'];
                $ugroups[$cloneId]['group_id']  = $row['group_id'];
            }
        }
        return $ugroups;
    }

    /**
     * Remove all Ugroups binding to a given ProjectUGroup
     *
     * @param int $ugroupId Id of the source user group
     *
     * @return bool
     */
    public function removeAllUGroupsBinding($ugroupId)
    {
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
     * @param int $groupId Id of the project
     *
     * @return bool
     */
    public function removeProjectUGroupsBinding($groupId)
    {
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
     * @param int $ugroupId Id of the source user group
     *
     * @return bool
     */
    public function updateBindedUGroups($ugroupId)
    {
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
     * @param int $ugroupId Id of the user group
     *
     * @return String
     */
    public function getLinkTitle($ugroupId)
    {
        $ugroup = new ProjectUGroup(array('ugroup_id' => $ugroupId));
        if ($ugroup->isBound()) {
            $text = $GLOBALS['Language']->getText('project_ugroup_binding', 'update_binding');
        } else {
            $text = $GLOBALS['Language']->getText('project_ugroup_binding', 'add_binding');
        }

        return '- ' . $text;
    }

    /**
     * Mark a given user group as bound to another one
     *
     * @param int $ugroupId Id of the bound user group
     * @param int $sourceId Id of the source user group
     *
     * @return void
     */
    public function updateUgroupBinding($ugroupId, $sourceId)
    {
        if (!$this->getUGroupManager()->updateUgroupBinding($ugroupId, $sourceId)) {
            throw new Exception('Unable to store ugroup binding');
        }
    }

    /**
     * Remove all users from a given user group then make a copy from another one
     *
     * @param int $ugroupId Id of the bound user group
     * @param int $sourceId Id of the source user group
     *
     * @return void
     */
    public function reloadUgroupBinding($ugroupId, $sourceId)
    {
        $refresher = $this->getRecursiveRefresher();
        $source_ugroup = $this->ugroupManager->getById($sourceId);
        $destination_ugroup = $this->ugroupManager->getById($ugroupId);
        $refresher->refreshUGroupAndBoundUGroups($source_ugroup, $destination_ugroup);
    }

    private function getRecursiveRefresher()
    {
        return new RecursiveBoundUGroupsRefresher(
            new BoundUGroupRefresher($this->ugroupManager, $this->ugroupUserDao),
            $this->ugroupManager
        );
    }

    /**
     * Bind a given user group to another one
     *
     * @param int $ugroupId Id of the bound user group
     * @param int $sourceId Id of the source user group
     *
     * @return bool
     */
    public function addBinding($ugroupId, $sourceId)
    {
        $source_ugroup = $this->getUGroupManager()->getUgroupBindingSource($ugroupId);
        if ($source_ugroup && $source_ugroup->getId() == $sourceId) {
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('project_ugroup_binding', 'duplicate_binding_warning', array($sourceId)));
            return false;
        }
        try {
            $this->updateUgroupBinding($ugroupId, $sourceId);
            $this->reloadUgroupBinding($ugroupId, $sourceId);
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
     * @param int $ugroupId Id of the user group we want to remove its binding
     *
     * @return bool
     */
    public function removeBinding($ugroupId)
    {
        if ($this->getUGroupManager()->updateUgroupBinding($ugroupId)) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_ugroup_binding', 'binding_removed'));
            return true;
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_ugroup_binding', 'remove_error'));
            return false;
        }
    }

    public function reloadUgroupBindingInProject(Project $project)
    {
        foreach ($this->ugroupManager->searchBindedUgroupsInProject($project) as $row) {
            $this->reloadUgroupBinding($row['ugroup_id'], $row['source_id']);
        }
    }
}
