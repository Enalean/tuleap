<?php
/**
 *  Copyright (c) Enalean, 2016. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

/**
* System Event classes
*
* UGROUP_MODIFY = one static ugroup of the project has been modified
*/
class SystemEvent_UGROUP_MODIFY extends SystemEvent
{
    /**
     * Verbalize the parameters so they are readable and much user friendly in
     * notifications
     *
     * @param bool $with_link true if you want links to entities. The returned
     * string will be html instead of plain/text
     *
     * @return string
     */
    public function verbalizeParameters($with_link)
    {
        $txt             = '';
        $ugroup_name     = '';
        $ugroup_old_name = '';
        if (count($this->getParametersAsArray()) == 4) {
            list($group_id, $ugroup_id, $ugroup_name, $ugroup_old_name) = $this->getParametersAsArray();
        } else {
            list($group_id, $ugroup_id) = $this->getParametersAsArray();
        }
        $txt .= 'project: '. $this->verbalizeProjectId($group_id, $with_link) .', ugroup: #'. $ugroup_id;

        if ($ugroup_name) {
            $txt .= ', rename: '. $ugroup_old_name .' => '. $ugroup_name;
        }

        return $txt;
    }

    /**
     * Process stored event
     *
     * @return bool
     */
    function process()
    {
        $ugroup_name = null;
        $ugroup_old_name = null;
        // Check parameters
        if (count($this->getParametersAsArray()) == 4) {
            list($group_id, $ugroup_id, $ugroup_name, $ugroup_old_name) = $this->getParametersAsArray();
        } else {
            list($group_id, $ugroup_id) = $this->getParametersAsArray();
        }
        // Remove ugroup binding to this user group
        if (!$this->processUgroupBinding($ugroup_id, $group_id)) {
            $this->error("Could not process binding to this user group ($ugroup_id)");
            return false;
        }
        $is_svn_access_successfuly_updated = $this->processSVNAccessFile($group_id, $ugroup_name, $ugroup_old_name);
        if (! $is_svn_access_successfuly_updated) {
            $this->error("Could not update SVN access file ($group_id)");
            return false;
        }

        EventManager::instance()->processEvent(
            Event::UGROUP_MODIFY,
            array(
                'project'         => $this->getProject($group_id),
                'new_ugroup_name' => $ugroup_name,
                'old_ugroup_name' => $ugroup_old_name
            )
        );

        $this->done();
        return true;
    }

    /**
     * @return bool
     */
    private function processSVNAccessFile($project_id, $ugroup_name, $ugroup_old_name)
    {
        if ($project = $this->getProject($project_id)) {
            if ($project->usesSVN()) {
                $backendSVN = $this->getBackend('SVN');
                if (! $backendSVN->updateSVNAccess($project_id, $project->getSVNRootPath(), $ugroup_name, $ugroup_old_name)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Remove all user group bound to a deleted given ugroup
     *
     * @protected for testing purpose
     *
     * @param int $ugroup_id Id of the deleted user group
     * @param int $group_id Id of the project
     *
     * @return bool
     */
    protected function processUgroupBinding($ugroup_id, $group_id)
    {
        $ugroup_binding               = $this->getUgroupBinding();
        $ugroups_successfully_updated = true;
        if (!$ugroup_binding->checkUGroupValidity($group_id, $ugroup_id)) {
            //The user group is removed, we remove all its binding traces
            $ugroups_successfully_updated = $ugroup_binding->removeAllUGroupsBinding($ugroup_id);
        } else {
            if (count($this->getParametersAsArray()) == 2) {
                //The user group has been updated (user added / user removed), we update all its bound user groups
                $ugroups_successfully_updated = $ugroup_binding->updateBindedUGroups($ugroup_id);
            }
        }

        $binded_ugroups = $ugroup_binding->getUGroupsByBindingSource($ugroup_id);
        foreach ($binded_ugroups as $binded_ugroup) {
            $bound_target_project_id = $binded_ugroup['group_id'];
            $ugroups_successfully_updated = $this->processSVNAccessFile($bound_target_project_id, null, null) &&
                $ugroups_successfully_updated;

            EventManager::instance()->processEvent(
                Event::UGROUP_MODIFY,
                array(
                    'project'         => $this->getProject($bound_target_project_id),
                    'new_ugroup_name' => null,
                    'old_ugroup_name' => null
                )
            );
        }

        return $ugroups_successfully_updated;
    }

    /**
     * Obtain instance of UGroupBinding
     *
     * @protected for testing purpose
     *
     * @return UGroupBinding
     */
    protected function getUgroupBinding()
    {
        $ugroupUserDao = new UGroupUserDao();
        $ugroupManager = new UGroupManager(new UGroupDao());
        $uGroupBinding = new UGroupBinding($ugroupUserDao, $ugroupManager);
        return $uGroupBinding;
    }
}
