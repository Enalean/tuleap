<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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

use Tuleap\Mediawiki\ForgeUserGroupPermission\MediawikiAdminAllProjects;

class MediawikiManager
{

    public const READ_ACCESS  = 'PLUGIN_MEDIAWIKI_READ';
    public const WRITE_ACCESS = 'PLUGIN_MEDIAWIKI_WRITE';

    /** @var MediawikiDao */
    private $dao;

    public function __construct(MediawikiDao $dao)
    {
        $this->dao = $dao;
    }

    public function getWgDBname(Project $project)
    {
        return $this->dao->getMediawikiDatabaseName($project, false);
    }

    public function getWgDBprefix(Project $project)
    {
        return $this->dao->getMediawikiTableNamePrefix($project);
    }

    public function getDao()
    {
        return $this->dao;
    }
    public function saveCompatibilityViewOption(Project $project, $compatibility_view_option)
    {
        $project_id                = $project->getID();
        $enable_compatibility_view = $compatibility_view_option ? $compatibility_view_option : 0;

        return $this->dao->updateCompatibilityViewOption($project_id, $enable_compatibility_view);
    }

    /**
     * @return int[]
     */
    public function getReadAccessControl(Project $project)
    {
        $ugroup_ids = $this->getAccessControl($project, self::READ_ACCESS);

        if (! $ugroup_ids) {
            return $this->getDefaultReadAccessControl($project);
        }

        return $ugroup_ids;
    }

    /**
     * @return int[]
     */
    public function getReadAccessControlForProjectContainingGroup(Project $project, ProjectUGroup $ugroup)
    {
        return $this->getAccessControlForProjectContainingUGroup($project, self::READ_ACCESS, $ugroup);
    }

    /**
     * @return int[]
     */
    private function getDefaultReadAccessControl(Project $project)
    {
        if ($project->isPublic()) {
            return array(ProjectUGroup::REGISTERED);
        }

        return array(ProjectUGroup::PROJECT_MEMBERS);
    }

    /**
     * @return int[]
     */
    public function getWriteAccessControl(Project $project)
    {
        $ugroup_ids =  $this->getAccessControl($project, self::WRITE_ACCESS);

        if (! $ugroup_ids) {
            return $this->getDefaultWriteAccessControl();
        }

        return $ugroup_ids;
    }

    /**
     * @return int[]
     */
    public function getWriteAccessControlForProjectContainingUGroup(Project $project, ProjectUGroup $ugroup)
    {
        return $this->getAccessControlForProjectContainingUGroup($project, self::WRITE_ACCESS, $ugroup);
    }

    /**
     * @return int[]
     */
    private function getDefaultWriteAccessControl()
    {
        return array(ProjectUGroup::PROJECT_MEMBERS);
    }

    /**
     * @return array
     */
    private function getAccessControl(Project $project, $access)
    {
        $result     = $this->dao->getAccessControl($project->getID(), $access);
        $ugroup_ids = array();

        foreach ($result as $row) {
            $ugroup_ids[] = $row['ugroup_id'];
        }

        return $ugroup_ids;
    }

    private function getAccessControlForProjectContainingUGroup(Project $project, $access, ProjectUGroup $ugroup)
    {
        $result     = $this->dao->getAccessControlForProjectContainingUGroup($project->getID(), $access, $ugroup->getId());
        $ugroup_ids = array();

        foreach ($result as $row) {
            $ugroup_ids[] = $row['ugroup_id'];
        }

        return $ugroup_ids;
    }

    public function saveReadAccessControl(Project $project, array $ugroup_ids)
    {
        return $this->saveAccessControl($project, self::READ_ACCESS, $ugroup_ids);
    }

    public function saveWriteAccessControl(Project $project, array $ugroup_ids)
    {
        return $this->saveAccessControl($project, self::WRITE_ACCESS, $ugroup_ids);
    }

    private function saveAccessControl(Project $project, $access, array $ugroup_ids)
    {
        return $this->dao->saveAccessControl($project->getID(), $access, $ugroup_ids);
    }

    public function updateAccessControlInProjectChangeContext(Project $project, $old_access, $new_access)
    {
        if ($new_access === Project::ACCESS_PRIVATE || $new_access === Project::ACCESS_PRIVATE_WO_RESTRICTED) {
            $this->dao->disableAnonymousRegisteredAuthenticated($project->getID());
        }
        if ($new_access === Project::ACCESS_PUBLIC && $old_access === Project::ACCESS_PUBLIC_UNRESTRICTED) {
            $this->dao->disableAuthenticated($project->getID());
        }
    }

    public function updateSiteAccess($old_value)
    {
        if ($old_value == ForgeAccess::ANONYMOUS) {
            $this->dao->updateAllAnonymousToRegistered();
        }
        if ($old_value == ForgeAccess::RESTRICTED) {
            $this->dao->updateAllAuthenticatedToRegistered();
        }
    }

    /**
     * @return bool
     */
    public function isCompatibilityViewEnabled(Project $project)
    {
        $plugin_has_view_enabled = (bool) forge_get_config('enable_compatibility_view', 'mediawiki');
        $result                  = $this->dao->getCompatibilityViewUsage($project->getID());

        if (! $result) {
            return false;
        }

        return ($plugin_has_view_enabled && (bool) $result['enable_compatibility_view']);
    }

    public function instanceUsesProjectID(Project $project)
    {
        return is_dir(forge_get_config('projects_path', 'mediawiki') . "/" . $project->getID());
    }

    private function restrictedUserCanRead(PFUser $user, Project $project)
    {
        return in_array(ProjectUGroup::AUTHENTICATED, $this->getReadAccessControl($project)) || in_array(ProjectUGroup::ANONYMOUS, $this->getReadAccessControl($project));
    }

    private function restrictedUserCanWrite(PFUser $user, Project $project)
    {
        return in_array(ProjectUGroup::AUTHENTICATED, $this->getWriteAccessControl($project)) || in_array(ProjectUGroup::ANONYMOUS, $this->getWriteAccessControl($project));
    }

    private function getUpgroupsPermissionsManager()
    {
        return new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );
    }

    private function hasDelegatedAccess(PFUser $user)
    {
        return $this->getUpgroupsPermissionsManager()->doesUserHavePermission(
            $user,
            new MediawikiAdminAllProjects()
        );
    }
    /**
     * @return bool true if user can read
     */
    public function userCanRead(PFUser $user, Project $project)
    {
        if ($this->hasDelegatedAccess($user)) {
            return true;
        }

        if ($this->userIsRestrictedAndNotProjectMember($user, $project)) {
            return $this->restrictedUserCanRead($user, $project);
        }

        $common_ugroups_ids = array_intersect(
            $this->getReadAccessControl($project),
            $user->getUgroups($project->getID(), array())
        );

        return !empty($common_ugroups_ids);
    }

    /**
     * @return bool true if user can write
     */
    public function userCanWrite(PFUser $user, Project $project)
    {
        if ($this->hasDelegatedAccess($user)) {
            return true;
        }

        if ($this->userIsRestrictedAndNotProjectMember($user, $project)) {
            return $this->restrictedUserCanWrite($user, $project);
        }

        $common_ugroups_ids = array_intersect(
            $this->getWriteAccessControl($project),
            $user->getUgroups($project->getID(), array())
        );

        return !empty($common_ugroups_ids);
    }

    private function userIsRestrictedAndNotProjectMember(PFUser $user, Project $project)
    {
        return $project->allowsRestricted() && $user->isRestricted() && ! $user->isMember($project->getID());
    }
}
