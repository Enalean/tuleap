<?php
/**
 * Copyright (c) Enalean, 2014 - 2016. All Rights Reserved.
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

require_once 'www/project/admin/permissions.php';

use Tuleap\Git\Permissions\FineGrainedUpdater;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionSaver;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;

/**
 * This class manages permissions for the Git service
 */
class GitPermissionsManager {

    /**
     * @var DefaultFineGrainedPermissionFactory
     */
    private $default_fine_grained_factory;

    /**
     * @var DefaultFineGrainedPermissionSaver
     */
    private $default_fine_grained_saver;

    /**
     * @var FineGrainedUpdater
     */
    private $fine_grained_updater;

    const REQUEST_KEY = 'default_access_rights';

    /**
     * @var Git_SystemEventManager
     */
    private $git_system_event_manager;

    /**
     * @var Git_PermissionsDao
     */
    private $git_permission_dao;

    /**
     * @var PermissionsManager
     */
    private $permissions_manager;

    public function __construct(
        Git_PermissionsDao $git_permission_dao,
        Git_SystemEventManager $git_system_event_manager,
        FineGrainedUpdater $fine_grained_updater,
        DefaultFineGrainedPermissionSaver $default_fine_grained_saver,
        DefaultFineGrainedPermissionFactory $default_fine_grained_factory
    ) {
        $this->permissions_manager          = PermissionsManager::instance();
        $this->git_permission_dao           = $git_permission_dao;
        $this->git_system_event_manager     = $git_system_event_manager;
        $this->fine_grained_updater         = $fine_grained_updater;
        $this->default_fine_grained_saver   = $default_fine_grained_saver;
        $this->default_fine_grained_factory = $default_fine_grained_factory;
    }

    public function userIsGitAdmin(PFUser $user, Project $project) {
        $database_result = $this->getCurrentGitAdminPermissionsForProject($project);

        if (db_numrows($database_result) < 1) {
            $database_result = $this->getDefaultGitAdminPermissions();
        }

        $has_permission = false;
        while (! $has_permission && ($row = db_fetch_array($database_result))) {
            $has_permission = ugroup_user_is_member($user->getId(), $row['ugroup_id'], $project->getID());
        }

        return $has_permission;
    }

    /**
     * @param Project $project
     * Return a DB list of ugroup_ids authorized to access the given object
     */
    private function getCurrentGitAdminPermissionsForProject(Project $project) {
        return permission_db_authorized_ugroups(Git::PERM_ADMIN, $project->getID());
    }

    private function getDefaultGitAdminPermissions() {
        return permission_db_get_defaults(Git::PERM_ADMIN);
    }

    public function getCurrentGitAdminUgroups($project_id) {
        return $this->permissions_manager->getAuthorizedUgroupIds($project_id, Git::PERM_ADMIN);
    }

    public function updateProjectAccess(Project $project, $old_access, $new_access) {
        if ($new_access == Project::ACCESS_PRIVATE) {
            $this->git_permission_dao->disableAnonymousRegisteredAuthenticated($project->getID());
            $this->git_system_event_manager->queueProjectsConfigurationUpdate(array($project->getID()));
        }
        if ($new_access == Project::ACCESS_PUBLIC && $old_access == Project::ACCESS_PUBLIC_UNRESTRICTED) {
            $this->git_permission_dao->disableAuthenticated($project->getID());
            $this->git_system_event_manager->queueProjectsConfigurationUpdate(array($project->getID()));
        }
    }

    public function updateSiteAccess($old_value, $new_value) {
        if ($old_value == ForgeAccess::ANONYMOUS) {
            $project_ids = $this->queueProjectsConfigurationUpdate($this->git_permission_dao->getAllProjectsWithAnonymousRepositories());
            if (count($project_ids)) {
                $this->git_permission_dao->updateAllAnonymousAccessToRegistered();
            }
        }
        if ($old_value == ForgeAccess::RESTRICTED) {
            $project_ids = $this->queueProjectsConfigurationUpdate($this->git_permission_dao->getAllProjectsWithUnrestrictedRepositories());
            if (count($project_ids)) {
                $this->git_permission_dao->updateAllAuthenticatedAccessToRegistered();
            }
        }
    }

    private function queueProjectsConfigurationUpdate(DataAccessResult $dar) {
        $projects_ids = array();
        if (count($dar) > 0) {
            foreach ($dar as $row) {
                $projects_ids[] = $row['group_id'];
            }
            $this->git_system_event_manager->queueProjectsConfigurationUpdate($projects_ids);
        }
        return $projects_ids;
    }

    public function updateProjectDefaultPermissions(Codendi_Request $request)
    {
        $project    = $request->getProject();
        $project_id = $project->getID();

        $csrf = new CSRFSynchronizerToken("plugins/git/?group_id=$project_id&action=admin-default-access-rights");
        $csrf->check();

        $read_ugroup_ids   = array();
        $write_ugroup_ids  = array();
        $rewind_ugroup_ids = array();
        $ugroup_ids        = $request->get(self::REQUEST_KEY);

        if ($ugroup_ids) {
            $read_ugroup_ids   = $this->getUgroupIdsForPermission($ugroup_ids, Git::DEFAULT_PERM_READ);
            $write_ugroup_ids  = $this->getUgroupIdsForPermission($ugroup_ids, Git::DEFAULT_PERM_WRITE);
            $rewind_ugroup_ids = $this->getUgroupIdsForPermission($ugroup_ids, Git::DEFAULT_PERM_WPLUS);
        }

        $this->permissions_manager->clearPermission(Git::DEFAULT_PERM_READ, $project_id);
        $this->permissions_manager->clearPermission(Git::DEFAULT_PERM_WRITE, $project_id);
        $this->permissions_manager->clearPermission(Git::DEFAULT_PERM_WPLUS, $project_id);

        $this->saveDefaultPermission(
            $project,
            $read_ugroup_ids,
            Git::DEFAULT_PERM_READ
        );

        $this->saveDefaultPermission(
            $project,
            $write_ugroup_ids,
            Git::DEFAULT_PERM_WRITE
        );

        $this->saveDefaultPermission(
            $project,
            $rewind_ugroup_ids,
            Git::DEFAULT_PERM_WPLUS
        );

        if ($request->get('use-fine-grained-permissions')) {
            $this->fine_grained_updater->enableProject($project);
        } else {
            $this->fine_grained_updater->disableProject($project);
        }

        $added_branches_permissions = $this->default_fine_grained_factory->getBranchesFineGrainedPermissionsFromRequest(
            $request,
            $project
        );

        $added_tags_permissions = $this->default_fine_grained_factory->getTagsFineGrainedPermissionsFromRequest(
            $request,
            $project
        );

        foreach ($added_branches_permissions as $added_branch_permission) {
            $this->default_fine_grained_saver->saveBranchPermission($added_branch_permission);
        }

        foreach ($added_tags_permissions as $added_tag_permission) {
            $this->default_fine_grained_saver->saveTagPermission($added_tag_permission);
        }

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            $GLOBALS['Language']->getText('plugin_git', 'default_access_control_saved')
        );
    }

    /**
     * @return array
     */
    private function getUgroupIdsForPermission(array $ugroup_ids, $permission)
    {
        $ugroup_ids_for_permission = array();

        if (isset($ugroup_ids[$permission]) && is_array($ugroup_ids[$permission])) {
            $ugroup_ids_for_permission = $ugroup_ids[$permission];
        }

        return $ugroup_ids_for_permission;
    }

    private function saveDefaultPermission(Project $project, array $ugroup_ids, $permission)
    {
        $override_collection = $this->permissions_manager->savePermissions(
            $project,
            $project->getID(),
            $permission,
            $ugroup_ids
        );

        $override_collection->emitFeedback($permission);
    }

    /**
     * @return array
     */
    public function getDefaultPermissions(Project $project)
    {
        return array(
            Git::PERM_READ  => $this->getDefaultPermission($project, Git::DEFAULT_PERM_READ),
            Git::PERM_WRITE => $this->getDefaultPermission($project, Git::DEFAULT_PERM_WRITE),
            Git::PERM_WPLUS => $this->getDefaultPermission($project, Git::DEFAULT_PERM_WPLUS),
        );
    }

    private function getDefaultPermission(Project $project, $permission_name)
    {
        return $this->permissions_manager->getAuthorizedUGroupIdsForProject(
            $project,
            $project->getID(),
            $permission_name
        );
    }

}
