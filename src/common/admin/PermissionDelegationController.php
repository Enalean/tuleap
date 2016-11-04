<?php
/**
 * Copyright (c) Enalean, 2014 - 2016. All rights reserved
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

use Tuleap\Admin\AdminPageRenderer;

class Admin_PermissionDelegationController {

    const REDIRECT_URL = '/admin/permission_delegation.php';

    /**
     * @var Codendi_Request
     */
    private $request;

    /**
     * @var TemplateRenderer
     */
    private $renderer;

    /**
     * @var User_ForgeUserGroupPermissionsFactory
     */
    private $user_group_permissions_factory;

    /**
     * @var User_ForgeUserGroupPermissionsManager
     */
    private $user_group_permissions_manager;

    /**
     * @var User_ForgeUserGroupFactory
     */
    private $user_group_factory;

    /**
     * @var User_ForgeUserGroupUsersFactory
     */
    private $user_group_users_factory;

    /**
     * @var User_ForgeUserGroupUsersManager
     */
    private $user_group_users_manager;

    /**
     *
     * @var User_ForgeUserGroupManager
     */
    private $user_group_manager;


    public function __construct(Codendi_Request $request) {
        $this->request  = $request;
        $this->renderer = TemplateRendererFactory::build()->getRenderer($this->getTemplatesDir());

        $permissions_dao                      = new User_ForgeUserGroupPermissionsDao();
        $this->user_group_permissions_factory = new User_ForgeUserGroupPermissionsFactory($permissions_dao);
        $this->user_group_permissions_manager = new User_ForgeUserGroupPermissionsManager($permissions_dao);

        $user_group_dao                       = new UserGroupDao();
        $this->user_group_factory             = new User_ForgeUserGroupFactory($user_group_dao);
        $this->user_group_manager             = new User_ForgeUserGroupManager($user_group_dao);

        $user_group_users_dao                 = new User_ForgeUserGroupUsersDao();
        $this->user_group_users_factory       = new User_ForgeUserGroupUsersFactory($user_group_users_dao);
        $this->user_group_users_manager       = new User_ForgeUserGroupUsersManager($user_group_users_dao);
    }

    private function redirect($id = null) {
        if ($id) {
            $redirect = http_build_query(array('id' => $id));
            $GLOBALS['Response']->redirect(self::REDIRECT_URL.'?'.$redirect);
        }

        $GLOBALS['Response']->redirect(self::REDIRECT_URL);
    }

    public function process() {
        switch ($this->request->get('action')) {
            case 'update-group':
                $this->updateGroup();
                break;

            case 'delete-group':
                $this->deleteGroup();
                break;

            case 'show-add-permissions':
                $this->showAddPermissions($this->request->get('id'));
                break;

            case 'add-permissions':
                $this->addPermissions();
                break;

            case 'delete-permissions':
                $this->deletePermissions();
                break;
            case 'manage-users':
                $this->manageUsers();
                break;

            case 'index':
            default     :
                $this->index();
                break;
        }
    }

    private function updateGroup() {
        $id          = $this->request->get('id');
        $name        = $this->request->get('name');
        $description = $this->request->get('description');

        try {
            if ($id) {
                $user_group = new User_ForgeUGroup($id, $name, $description);
                $this->user_group_manager->updateUserGroup($user_group);
            } else {
                $user_group = $this->user_group_factory->createForgeUGroup($name, $description);
                $this->request->set('id', $user_group->getId());
            }

        } catch (User_UserGroupNameInvalidException $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'user_group_already_exists'));

        } catch(User_UserGroupNotFoundException $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'ugroup_not_found'));
        }

        $this->redirect($id);
    }

    private function deleteGroup() {
        $id = $this->request->get('id');

        if ($id) {
            try {
                $user_group = $this->user_group_factory->getForgeUserGroupById($id);
                $this->user_group_manager->deleteForgeUserGroup($user_group);

            } catch(User_UserGroupNotFoundException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'ugroup_not_found'));
            }
        }

        $this->redirect();
    }

    private function index() {
        $groups     = $this->user_group_factory->getAllForgeUserGroups();
        $current_id = $this->request->get('id');

        $formatted_groups        = $this->getFormattedGroups($groups,$current_id);
        $current_group_presenter = $this->getCurrentGroupPresenter($formatted_groups);

        $edit_group_presenter   = null;
        $delete_group_presenter = null;
        $add_perm_presenter     = null;
        if ($current_group_presenter) {
            $current_group = $current_group_presenter->getGroup();

            $delete_group_presenter = new Admin_PermissionDelegationDeleteGroupModalPresenter($current_group);
            $edit_group_presenter   = new Admin_PermissionDelegationGroupModalPresenter($current_group);

            $unused_permissions = $this->user_group_permissions_factory->getAllUnusedForgePermissionsForForgeUserGroup(
                $current_group
            );
            $add_perm_presenter = new Admin_PermissionDelegationPermissionsModalPresenter($current_group, $unused_permissions);
        }

        $presenter = new Admin_PermissionDelegationIndexPresenter(
            $formatted_groups,
            new Admin_PermissionDelegationGroupModalPresenter(),
            $delete_group_presenter,
            $edit_group_presenter,
            $add_perm_presenter,
            $current_group_presenter
        );

        $renderer = new AdminPageRenderer();
        $renderer->renderANoFramedPresenter(
            $GLOBALS['Language']->getText('admin_permission_delegation', 'page_title'),
            $this->getTemplatesDir(),
            'index',
            $presenter
        );
    }

    private function getCurrentGroupPresenter(array $formatted_groups) {
        foreach ($formatted_groups as $formatted_group) {
            try {
                if ($formatted_group['is_current']) {
                    $user_group  = $this->user_group_factory->getForgeUserGroupById($formatted_group['id']);
                    $permissions = $this->user_group_permissions_factory->getPermissionsForForgeUserGroup($user_group);
                    $users       = $this->user_group_users_factory->getAllUsersFromForgeUserGroup($user_group);
                    return new Admin_PermissionDelegationGroupPresenter($user_group, $permissions, $users);
                }
            } catch (User_ForgeUserGroupPermission_NotFoundException $e) {
                return null;
            }
        }

        return null;
    }

    private function getFormattedGroups(array $groups, $current_id) {
        $formatted_groups = array();

        foreach ($groups as $group) {
            $formatted_groups[] = array(
                'id'         => $group->getId(),
                'name'       => $group->getName(),
                'is_current' => $group->getId() == $current_id,
            );
        }

        if (! $current_id && $formatted_groups) {
            $formatted_groups[0]['is_current'] = true;
        }

        return $formatted_groups;
    }

    private function showAddPermissions($group_id) {
        $group              = $this->user_group_factory->getForgeUserGroupById($group_id);
        $unused_permissions = $this->user_group_permissions_factory->getAllUnusedForgePermissionsForForgeUserGroup($group);

        $presenter = new Admin_PermissionDelegationPermissionsModalPresenter($group, $unused_permissions);
        $this->renderer->renderToPage('permissions_modal', $presenter);
    }

    private function addPermissions() {
        $id             = $this->request->get('id');
        $permission_ids = $this->request->get('permissions');

        if ($id) {
            try {
                $user_group  = $this->user_group_factory->getForgeUserGroupById($id);

                foreach ($permission_ids as $permission_id) {
                    $permission = $this->user_group_permissions_factory->getForgePermissionById($permission_id);
                    $this->user_group_permissions_manager->addPermission($user_group, $permission);
                }

            } catch(User_UserGroupNotFoundException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'ugroup_not_found'));

            } catch(User_ForgeUserGroupPermission_NotFoundException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'permission_not_found'));
            }
        }

        $this->redirect($id);
    }

    private function deletePermissions() {
        $id             = $this->request->get('id');
        $permission_ids = $this->request->get('permissions');

        if ($id) {
            try {
                $user_group  = $this->user_group_factory->getForgeUserGroupById($id);

                foreach ($permission_ids as $permission_id) {
                    $permission = $this->user_group_permissions_factory->getForgePermissionById($permission_id);
                    $this->user_group_permissions_manager->deletePermission($user_group, $permission);
                }

            } catch(User_UserGroupNotFoundException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'ugroup_not_found'));
            } catch(User_ForgeUserGroupPermission_NotFoundException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'permission_not_found'));
            }
        }

        $this->redirect($id);
    }

    private function getTemplatesDir() {
        return ForgeConfig::get('codendi_dir') .'/src/templates/admin/permission_delegation/';
    }

    private function getUserManager() {
        return UserManager::instance();
    }

    private function manageUsers() {
        if ($this->request->get('remove-users')) {
            $this->removeUsersFromGroup();
        } elseif ($this->request->get('add-user')) {
            $this->addUserToGroup();
        }

        $this->redirect();
    }

    private function addUserToGroup() {
        $group_id = $this->request->get('id');
        $user     = $this->request->get('user');

        if ($user) {
            $user = $this->getUserManager()->findUser($user);
        }

        if ($group_id && $user) {
            try {
                $user_group = $this->user_group_factory->getForgeUserGroupById($group_id);
                $this->user_group_users_manager->addUserToForgeUserGroup($user, $user_group);
            } catch (User_UserGroupNotFoundException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'ugroup_not_found'));
            }
        }

        $this->redirect($group_id);
    }

    private function removeUsersFromGroup() {
        $group_id = $this->request->get('id');
        $user_ids = $this->request->get('user-ids');

        if ($group_id) {
            try {
                $user_group = $this->user_group_factory->getForgeUserGroupById($group_id);
                $this->removeUsers($user_group, $user_ids);

            } catch (User_UserGroupNotFoundException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'ugroup_not_found'));
            }
        }

        $this->redirect($group_id);
    }

    private function removeUsers($user_group, $user_ids) {
        foreach ($user_ids as $user_id) {
            $user = $this->getUserManager()->getUserById($user_id);

            if ($user) {
                $this->user_group_users_manager->removeUserFromForgeUserGroup($user, $user_group);
            }
        }
    }
}
?>
