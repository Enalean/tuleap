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

class Admin_PermissionDelegationController {

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
     *
     * @var User_ForgeUserGroupFactory
     */
    private $user_group_factory;

    /**
     *
     * @var User_ForgeUserGroupManager
     */
    private $user_group_manager;

    public function __construct(Codendi_Request $request) {
        $this->request  = $request;
        $this->renderer = TemplateRendererFactory::build()->getRenderer($this->getTemplatesDir());

        $this->user_group_permissions_factory = new User_ForgeUserGroupPermissionsFactory(
            new User_ForgeUserGroupPermissionsDao()
        );
        $this->user_group_permissions_manager = new User_ForgeUserGroupPermissionsManager(
            new User_ForgeUserGroupPermissionsDao()
        );

        $this->user_group_factory = new User_ForgeUserGroupFactory(
            new UserGroupDao()
        );
        $this->user_group_manager = new User_ForgeUserGroupManager(
            new UserGroupDao()
        );
    }

    public function process() {
        switch ($this->request->get('action')) {
            case 'show-add-group':
                $this->showAddGroup();
                break;

            case 'show-edit-group':
                $this->showEditGroup($this->request->get('group-id'));
                break;

            case 'update-group':
                $this->updateGroup();
                break;

            case 'show-delete-group':
                $this->showDeleteGroup($this->request->get('group-id'));
                break;

            case 'delete-group':
                $this->deleteGroup();
                break;

            case 'show-add-permissions':
                $this->showAddPermissions($this->request->get('group-id'));
                break;

            case 'add-permissions':
                $this->addPermissions();
                break;

            case 'delete-permissions':
                $this->deletePermissions();
                break;

            case 'index':
            default     :
                $this->index();
                break;
        }
    }

    private function showAddGroup() {
        $presenter = new Admin_PermissionDelegationGroupModalPresenter();
        $this->renderer->renderToPage('group_modal', $presenter);
    }

    private function showEditGroup($group_id) {
        $group = $this->user_group_factory->getForgeUserGroupById($group_id);

        $presenter = new Admin_PermissionDelegationGroupModalPresenter($group);
        $this->renderer->renderToPage('group_modal', $presenter);
    }

    private function updateGroup() {
        $id          = $this->request->get('group-id');
        $name        = $this->request->get('group-name');
        $description = $this->request->get('group-description');

        try {
            if ($id) {
                $user_group = new User_ForgeUGroup($id, $name, $description);
                $this->user_group_manager->updateUserGroup($user_group);
            } else {
                $user_group = $this->user_group_factory->createForgeUGroup($name, $description);
                $id = $user_group->getId();
            }
        } catch (User_UserGroupNameInvalidException $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'user_group_already_exists'));
        } catch(User_UserGroupNotFoundException $e) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'ugroup_not_found'));
        }

        $this->index($id);
    }

    private function showDeleteGroup($group_id) {
        $group = $this->user_group_factory->getForgeUserGroupById($group_id);

        $presenter = new Admin_PermissionDelegationDeleteGroupModalPresenter($group);
        $this->renderer->renderToPage('delete_group_modal', $presenter);
    }

    private function deleteGroup() {
        $id = $this->request->get('group-id');

        if ($id) {
            try {
                $user_group = $this->user_group_factory->getForgeUserGroupById($id);
                $this->user_group_manager->deleteForgeUserGroup($user_group);
            } catch(User_UserGroupNotFoundException $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_permission_delegation', 'ugroup_not_found'));
            }
        }

        $this->index();
    }

    private function index($id = null) {
        $groups = $this->user_group_factory->getAllForgeUserGroups();

        if (! $id) {
            $id = $this->request->get('group-id');
        }

        $presenter = new Admin_PermissionDelegationIndexPresenter($groups, $id);

        $this->header();
        $this->renderer->renderToPage('index', $presenter);
        $this->footer();
    }

    private function showAddPermissions($group_id) {
        $group                 = $this->user_group_factory->getForgeUserGroupById($group_id);
        $available_permissions = $this->user_group_permissions_factory->getPermissionsForForgeUserGroup($group);

        $presenter = new Admin_PermissionDelegationPermissionsModalPresenter($group, $available_permissions);
        $this->renderer->renderToPage('permissions_modal', $presenter);
    }

    private function addPermissions() {
        $id = $this->request->get('group-id');

        if ($id) {

        } else {

        }

        $this->index();
    }

    private function deletePermissions() {
        $id = $this->request->get('group-id');

        if ($id) {

        } else {

        }

        $this->index();
    }

    private function header() {
        $GLOBALS['HTML']->header(array('title' => $GLOBALS['Language']->getText('admin_permission_delegation', 'page_title')));
        echo '<script type="text/javascript" src="/scripts/admin/permission_delegation.js"></script>';
    }

    private function footer() {
        $GLOBALS['HTML']->footer(array());
    }

    private function getTemplatesDir() {
        return Config::get('codendi_dir') .'/src/templates/admin/permission_delegation/';
    }
}
?>
