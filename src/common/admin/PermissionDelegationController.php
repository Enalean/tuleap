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

use Tuleap\Admin\PermissionDelegation\ForgeUserGroupDeletedEvent;
use Tuleap\admin\PermissionDelegation\PermissionDelegationsAddedToForgeUserGroupEvent;
use Tuleap\User\GroupCannotRemoveLastAdministrationPermission;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Admin\PermissionDelegation\PermissionDelegationsRemovedForForgeUserGroupEvent;
use Tuleap\Admin\PermissionDelegation\PermissionPresenterBuilder;
use Tuleap\Admin\PermissionDelegation\UserAddedToForgeUserGroupEvent;
use Tuleap\Admin\PermissionDelegation\UsersRemovedFromForgeUserGroupEvent;
use Tuleap\User\ForgeUserGroupPermission\SiteAdministratorPermission;
use Tuleap\User\ForgeUserGroupPermission\SiteAdministratorPermissionChecker;
use Tuleap\User\ForgeUserGroupPermission\UserForgeUGroupPresenter;
use Tuleap\User\User_ForgeUserGroupPermissionsFactory;
use Tuleap\User\UserCannotRemoveLastAdministrationPermission;

class Admin_PermissionDelegationController
{
    public const REDIRECT_URL = '/admin/permission_delegation.php';

    /**
     * @var HTTPRequest
     */
    private $request;

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

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
     * @var User_ForgeUserGroupManager
     */
    private $user_group_manager;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var SiteAdministratorPermissionChecker
     */
    private $site_admin_permission_checker;
    /**
     * @var PermissionPresenterBuilder
     */
    private $permission_builder;
    /**
     * @var User_ForgeUserGroupPermissionsDao
     */
    private $dao;


    public function __construct(
        HTTPRequest $request,
        CSRFSynchronizerToken $csrf_token,
        User_ForgeUserGroupPermissionsFactory $user_group_permissions_factory,
        User_ForgeUserGroupPermissionsManager $user_group_permissions_manager,
        User_ForgeUserGroupFactory $user_group_factory,
        User_ForgeUserGroupManager $user_group_manager,
        User_ForgeUserGroupUsersFactory $user_group_users_factory,
        User_ForgeUserGroupUsersManager $user_group_users_manager,
        UserManager $user_manager,
        SiteAdministratorPermissionChecker $site_admin_permission_checker,
        PermissionPresenterBuilder $permission_builder,
        User_ForgeUserGroupPermissionsDao $dao,
        private \Psr\EventDispatcher\EventDispatcherInterface $event_dispatcher,
    ) {
        $this->request    = $request;
        $this->csrf_token = $csrf_token;
        $this->renderer   = TemplateRendererFactory::build()->getRenderer($this->getTemplatesDir());

        $this->user_group_permissions_factory = $user_group_permissions_factory;
        $this->user_group_permissions_manager = $user_group_permissions_manager;
        $this->user_group_factory             = $user_group_factory;
        $this->user_group_manager             = $user_group_manager;
        $this->user_group_users_factory       = $user_group_users_factory;
        $this->user_group_users_manager       = $user_group_users_manager;
        $this->user_manager                   = $user_manager;
        $this->permission_builder             = $permission_builder;
        $this->site_admin_permission_checker  = $site_admin_permission_checker;
        $this->dao                            = $dao;
    }

    private function redirect($id = null)
    {
        if ($id) {
            $redirect = http_build_query(['id' => $id]);
            $GLOBALS['Response']->redirect(self::REDIRECT_URL . '?' . $redirect);
        }

        $GLOBALS['Response']->redirect(self::REDIRECT_URL);
    }

    public function process()
    {
        if ($this->request->isPost()) {
            $this->csrf_token->check();

            switch ($this->request->get('action')) {
                case 'update-group':
                    $this->updateGroup();
                    break;

                case 'delete-group':
                    $this->deleteGroup();
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
            }
        }
        switch ($this->request->get('action')) {
            case 'show-add-permissions':
                $this->showAddPermissions($this->request->get('id'));
                break;
            case 'index':
            default:
                $this->index();
                break;
        }
    }

    private function updateGroup()
    {
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
        } catch (User_UserGroupNotFoundException $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'ugroup_not_found'));
        }

        $this->redirect($id);
    }

    private function deleteGroup()
    {
        $id = $this->request->get('id');

        if ($id) {
            try {
                $user_group = $this->user_group_factory->getForgeUserGroupById($id);
                $this->user_group_manager->deleteForgeUserGroup($user_group);
                $this->event_dispatcher->dispatch(new ForgeUserGroupDeletedEvent($user_group));
            } catch (User_UserGroupNotFoundException $e) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    $GLOBALS['Language']->getText('admin_permission_delegation', 'ugroup_not_found')
                );
            } catch (GroupCannotRemoveLastAdministrationPermission $e) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    _("You can't remove the last group containing site administrator permissions")
                );
            }
        }

        $this->redirect();
    }

    private function index()
    {
        $groups     = $this->user_group_factory->getAllForgeUserGroups();
        $current_id = $this->request->get('id');

        $formatted_groups        = $this->getFormattedGroups($groups, $current_id);
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

        $include_assets = new \Tuleap\Layout\IncludeAssets(__DIR__ . '/../../scripts/site-admin/frontend-assets', '/assets/core/site-admin');
        $GLOBALS['Response']->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($include_assets, 'site-admin-permission-delegation.js'));

        $presenter = new Admin_PermissionDelegationIndexPresenter(
            $this->csrf_token,
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

    private function getCurrentGroupPresenter(array $formatted_groups)
    {
        foreach ($formatted_groups as $formatted_group) {
            try {
                if ($formatted_group['is_current']) {
                    $user_group  = $this->user_group_factory->getForgeUserGroupById($formatted_group['id']);
                    $permissions = $this->permission_builder->build(
                        $this->user_group_permissions_factory->getPermissionsForForgeUserGroup($user_group)
                    );
                    $users       = $this->user_group_users_factory->getAllUsersFromForgeUserGroup($user_group);

                    $can_be_removed       = ! $this->site_admin_permission_checker->checkUGroupIsNotTheOnlyOneWithPlatformAdministrationPermission(
                        $user_group
                    );
                    $user_group_presenter = new UserForgeUGroupPresenter($user_group, $can_be_removed);

                    return new Admin_PermissionDelegationGroupPresenter($user_group_presenter, $permissions, $users);
                }
            } catch (User_ForgeUserGroupPermission_NotFoundException $e) {
                return null;
            }
        }

        return null;
    }

    private function getFormattedGroups(array $groups, $current_id)
    {
        $formatted_groups = [];

        foreach ($groups as $group) {
            $formatted_groups[] = [
                'id'         => $group->getId(),
                'name'       => $group->getName(),
                'is_current' => $group->getId() == $current_id,
            ];
        }

        if (! $current_id && $formatted_groups) {
            $formatted_groups[0]['is_current'] = true;
        }

        return $formatted_groups;
    }

    private function showAddPermissions($group_id)
    {
        $group              = $this->user_group_factory->getForgeUserGroupById($group_id);
        $unused_permissions = $this->user_group_permissions_factory->getAllUnusedForgePermissionsForForgeUserGroup($group);

        $presenter = new Admin_PermissionDelegationPermissionsModalPresenter($group, $unused_permissions);
        $this->renderer->renderToPage('permissions_modal', $presenter);
    }

    private function addPermissions()
    {
        $id             = $this->request->get('id');
        $permission_ids = $this->request->get('permissions');

        if ($id) {
            try {
                $user_group = $this->user_group_factory->getForgeUserGroupById($id);

                $added_permissions = [];
                foreach ($permission_ids as $permission_id) {
                    $permission = $this->user_group_permissions_factory->getForgePermissionById($permission_id);
                    $this->user_group_permissions_manager->addPermission($user_group, $permission);
                    $added_permissions[] = $permission;
                }
                $this->event_dispatcher->dispatch(new PermissionDelegationsAddedToForgeUserGroupEvent($user_group, $added_permissions));
            } catch (User_UserGroupNotFoundException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'ugroup_not_found'));
            } catch (User_ForgeUserGroupPermission_NotFoundException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'permission_not_found'));
            }
        }

        $this->redirect($id);
    }

    private function deletePermissions()
    {
        $id             = $this->request->get('id');
        $permission_ids = $this->request->get('permissions');

        if ($id) {
            try {
                $user_group = $this->user_group_factory->getForgeUserGroupById($id);

                $deleted_permissions = [];
                foreach ($permission_ids as $permission_id) {
                    $permission = $this->user_group_permissions_factory->getForgePermissionById($permission_id);

                    $this->dao->startTransaction();
                    $this->checkPermissionCanBeRemoved($permission);
                    $this->user_group_permissions_manager->deletePermission($user_group, $permission);
                    $this->dao->commit();
                    $deleted_permissions[] = $permission;
                }
                $this->event_dispatcher->dispatch(new PermissionDelegationsRemovedForForgeUserGroupEvent($user_group, $deleted_permissions));
            } catch (User_UserGroupNotFoundException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'ugroup_not_found'));
            } catch (User_ForgeUserGroupPermission_NotFoundException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'permission_not_found'));
            } catch (UserCannotRemoveLastAdministrationPermission $e) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    _("You can't remove the last platform administration permission.")
                );
            }
        }

        $this->redirect($id);
    }

    private function getTemplatesDir()
    {
        return ForgeConfig::get('codendi_dir') . '/src/templates/admin/permission_delegation/';
    }

    private function manageUsers()
    {
        if ($this->request->get('remove-users')) {
            $this->removeUsersFromGroup();
        } elseif ($this->request->get('add-user')) {
            $this->addUserToGroup();
        }

        $this->redirect();
    }

    private function addUserToGroup()
    {
        $group_id = $this->request->get('id');
        $user     = $this->request->get('user');

        if ($user) {
            $user = $this->user_manager->findUser($user);
        }

        if ($group_id && $user) {
            try {
                $user_group = $this->user_group_factory->getForgeUserGroupById($group_id);
                $this->user_group_users_manager->addUserToForgeUserGroup($user, $user_group);
                $this->event_dispatcher->dispatch(new UserAddedToForgeUserGroupEvent($user));
            } catch (User_UserGroupNotFoundException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'ugroup_not_found'));
            }
        }

        $this->redirect($group_id);
    }

    private function removeUsersFromGroup()
    {
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

    private function removeUsers($user_group, $user_ids)
    {
        $users = [];
        foreach ($user_ids as $user_id) {
            $user = $this->user_manager->getUserById($user_id);

            if ($user) {
                $users[] = $user;
                $this->user_group_users_manager->removeUserFromForgeUserGroup($user, $user_group);
            }
        }

        $this->event_dispatcher->dispatch(new UsersRemovedFromForgeUserGroupEvent($users));
    }

    /**
     * @param $permission
     *
     * @throws UserCannotRemoveLastAdministrationPermission
     */
    private function checkPermissionCanBeRemoved(User_ForgeUserGroupPermission $permission)
    {
        if ($permission->getId() === SiteAdministratorPermission::ID) {
            if (! $this->site_admin_permission_checker->checkPlatformHasMoreThanOneSiteAdministrationPermission()) {
                throw new UserCannotRemoveLastAdministrationPermission();
            }
        }
    }
}
