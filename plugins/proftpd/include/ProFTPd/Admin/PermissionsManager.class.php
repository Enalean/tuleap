<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\ProFTPd\Admin;

use Project;
use UGroupManager;
use ProjectUGroup;
use PFUser;

class PermissionsManager
{
    public const PERM_READ  = 'PLUGIN_PROFTPD_READ';
    public const PERM_WRITE = 'PLUGIN_PROFTPD_WRITE';

    /** @var \PermissionsManager */
    private $permissions_manager;

    /** @var UGroupManager */
    private $ugroup_manager;

    public function __construct(\PermissionsManager $permissions_manager, UGroupManager $ugroup_manager)
    {
        $this->permissions_manager = $permissions_manager;
        $this->ugroup_manager      = $ugroup_manager;
    }

    public function getSelectUGroupFor(Project $project, $permissions)
    {
        $ugroups = $this->permissions_manager->getAuthorizedUgroupIds($project->getID(), $permissions);
        if (count($ugroups) == 1) {
            return array_shift($ugroups);
        }
        return ProjectUGroup::NONE;
    }

    public function getUGroupSystemNameFor(Project $project, $permissions)
    {
        $ugroup_id = $this->getSelectUGroupFor($project, $permissions);
        if ($ugroup_id != ProjectUGroup::NONE) {
            $ugroup = $this->ugroup_manager->getById($ugroup_id);
            return $project->getUnixName() . '-' . $ugroup->getName();
        }
        return '';
    }

    public function duplicatePermissions(Project $project_template, Project $new_project, array $ugroup_mapping)
    {
        $this->duplicateReaders($project_template, $new_project, $ugroup_mapping);
        $this->duplicateWriters($project_template, $new_project, $ugroup_mapping);

        return true;
    }

    private function duplicateReaders(Project $project_template, Project $new_project, array $ugroup_mapping)
    {
        $ugroup_read      = $this->getSelectUGroupFor($project_template, self::PERM_READ);

        if ($ugroup_read ===  ProjectUGroup::NONE) {
            return;
        }

        $new_ugroup_read  = $ugroup_mapping[$ugroup_read];

        $this->savePermission(
            $new_project,
            self::PERM_READ,
            array($new_ugroup_read)
        );
    }

    private function duplicateWriters(Project $project_template, Project $new_project, array $ugroup_mapping)
    {
        $ugroup_write     = $this->getSelectUGroupFor($project_template, self::PERM_WRITE);

        if ($ugroup_write ===  ProjectUGroup::NONE) {
            return;
        }

        $new_ugroup_write = $ugroup_mapping[$ugroup_write];

        $this->savePermission(
            $new_project,
            self::PERM_WRITE,
            array($new_ugroup_write)
        );
    }

    public function savePermission(Project $project, $permission, array $ugroups)
    {
        include_once __DIR__ . '/../../../../../src/www/project/admin/permissions.php';

        permission_process_selection_form(
            $project->getGroupId(),
            $permission,
            $project->getGroupId(),
            $ugroups
        );
    }

    public function getUGroups(Project $project)
    {
        return $this->ugroup_manager->getStaticUGroups($project);
    }

    public function userCanBrowseSFTP(PFUser $user, Project $project)
    {
        return $this->userCanReadSFTP($user, $project) || $this->userCanWriteSFTP($user, $project) || $this->userIsAdmin($user, $project);
    }

    private function userCanReadSFTP(PFUser $user, Project $project)
    {
        $ugroup_id = $this->getSelectUGroupFor($project, self::PERM_READ);

        return $user->isMemberOfUGroup($ugroup_id, $project->getGroupId());
    }

    private function userCanWriteSFTP(PFUser $user, Project $project)
    {
        $ugroup_id = $this->getSelectUGroupFor($project, self::PERM_WRITE);

        return $user->isMemberOfUGroup($ugroup_id, $project->getGroupId());
    }

    private function userIsAdmin(PFUser $user, Project $project)
    {
        return $user->isAdmin($project->getID()) || $user->isSuperUser();
    }
}
