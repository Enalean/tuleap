<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'common/project/ProjectManager.class.php';
require_once 'common/project/UGroupManager.class.php';
require_once 'common/user/UserManager.class.php';

class Git_GitoliteMembershipPgm {
    
    public function getGroups($sshUserName) {
        $groups = array();
        $user = $this->getUserManager()->getUserByUserName($sshUserName);
        if ($user && ($user->isActive() || $user->isRestricted())) {
            // Special groups depending of status
            switch ($user->getStatus()) {
                case User::STATUS_RESTRICTED:
                    $groups[] = 'site_restricted';
                    break;
                case User::STATUS_ACTIVE:
                    $groups[] = 'site_active';
                    break;
            }

            // Dynamic groups
            $pm = $this->getProjectManager();
            foreach ($user->getUserGroupData() as $groupId => $row) {
                $project = $pm->getProject($groupId);
                if ($project) {
                    $groups[] = $project->getUnixName().'_project_members';
                    if ($user->isMember($groupId, 'A')) {
                        $groups[] = $project->getUnixName().'_project_admin';
                    }
                }
            }

            // Static groups
            $ugm = $this->getUGroupManager();
            foreach ($ugm->getByUserId($user) as $row) {
                $groups[] = 'ug_'.$row['ugroup_id'];
            }
        }
        return $groups;
    }

    /**
     * Wrapper for UserManager
     *
     * @return UserManager
     */
    protected function getUserManager() {
        return UserManager::instance();
    }

    /**
     * Wrapper for ProjectManager
     *
     * @return ProjectManager
     */
    protected function getProjectManager() {
        return ProjectManager::instance();
    }

    /**
     * Wrapper for ProjectManager
     *
     * @return UGroupManager
     */
    protected function getUGroupManager() {
        return new UGroupManager();
    }
}
?>