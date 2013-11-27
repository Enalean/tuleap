<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_Permission_PermissionController implements Tracker_Dispatchable_Interface {
    /** @var Tracker */
    private $tracker;

    /** @var TemplateRenderer */
    private $renderer;

    public function __construct(Tracker $tracker) {
        $this->tracker  = $tracker;
        $this->renderer = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR);
    }

    public function getTracker() {
        return $this->tracker;
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        if ($request->get('update')) {
            $this->save($request);
        } else {
            $this->view($layout);
        }
    }

    private function view(Tracker_IDisplayTrackerLayout $layout) {
        $presenter = new Tracker_Permission_PermissionPresenter($this->tracker, $this->getUGroupList());

        $this->displayHeader($layout, $presenter->title());
        $this->renderer->renderToPage('admin-perms-tracker', $presenter);
        $this->displayFooter($layout);
    }

    private function getUGroupList() {
        $ugroup_list = array();

        $ugroups_permissions = plugin_tracker_permission_get_tracker_ugroups_permissions($this->tracker->getGroupId(), $this->tracker->getId());
        ksort($ugroups_permissions);
        reset($ugroups_permissions);
        foreach($ugroups_permissions as $ugroup_permissions) {
            $ugroup      = $ugroup_permissions['ugroup'];
            $permissions = $ugroup_permissions['permissions'];

            $ugroup_list[] = new Tracker_Permission_PermissionUgroupPresenter(
                $ugroup['id'],
                $ugroup['name'],
                isset($ugroup['link']) ? $ugroup['link'] : '',
                $this->getPermissionTypeList($ugroup['id'], $permissions)
            );
        }

        return $ugroup_list;
    }

    private function getPermissionTypeList($ugroup_id, $permissions) {
        $permission_type_list = array();

        $permission_type_list[] = new Tracker_Permission_PermissionTypePresenter(
            Tracker_Permission_Command::PERMISSION_NONE,
            $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', Tracker::PERMISSION_NONE),
            count($permissions) == 0
        );

        $permission_type_list[] = new Tracker_Permission_PermissionTypePresenter(
            Tracker_Permission_Command::PERMISSION_FULL,
            $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', Tracker::PERMISSION_FULL),
            isset($permissions[Tracker::PERMISSION_FULL])
        );

        if ($ugroup_id != UGroup::ANONYMOUS) {
            $permission_type_list[] = new Tracker_Permission_PermissionTypePresenter(
                Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
                $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', Tracker::PERMISSION_SUBMITTER_ONLY),
                isset($permissions[Tracker::PERMISSION_SUBMITTER_ONLY])
            );

            if ($ugroup_id != UGroup::REGISTERED) {
                $permission_type_list[] = new Tracker_Permission_PermissionTypePresenter(
                    Tracker_Permission_Command::PERMISSION_ASSIGNEE,
                    $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', Tracker::PERMISSION_ASSIGNEE),
                    (isset($permissions[Tracker::PERMISSION_ASSIGNEE]) && !isset($permissions[Tracker::PERMISSION_SUBMITTER]))
                );

                $permission_type_list[] = new Tracker_Permission_PermissionTypePresenter(
                    Tracker_Permission_Command::PERMISSION_SUBMITTER,
                    $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', Tracker::PERMISSION_SUBMITTER),
                    !isset($permissions[Tracker::PERMISSION_ASSIGNEE]) && isset($permissions[Tracker::PERMISSION_SUBMITTER])
                );

                $permission_type_list[] = new Tracker_Permission_PermissionTypePresenter(
                    Tracker_Permission_Command::PERMISSION_ASSIGNEE_AND_SUBMITTER,
                    $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', Tracker::PERMISSION_ASSIGNEE .'_AND_'. Tracker::PERMISSION_SUBMITTER),
                    isset($permissions[Tracker::PERMISSION_ASSIGNEE]) && isset($permissions[Tracker::PERMISSION_SUBMITTER])
                );

                $permission_type_list[] = new Tracker_Permission_PermissionTypePresenter(
                    Tracker_Permission_Command::PERMISSION_ADMIN,
                    $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', Tracker::PERMISSION_ADMIN),
                    isset($permissions[Tracker::PERMISSION_ADMIN])
                );
            }
        }

        return $permission_type_list;
    }

    private function displayHeader(Tracker_IDisplayTrackerLayout $layout, $title) {
        $items = $this->tracker->getPermsItems();
        $this->tracker->displayAdminPermsHeader(
            $layout,
            $title,
            array($items['tracker'])
        );
    }

    private function displayFooter(Tracker_IDisplayTrackerLayout $layout) {
        $this->tracker->displayFooter($layout);
    }

    private function save(Codendi_Request $request) {
        $permission_setter  = $this->getPermissionSetter();
        $permission_request = new Tracker_Permission_PermissionRequest(array());
        $permission_request->setFromRequest($request, $permission_setter->getAllGroupIds());

        $permission_manager = new Tracker_Permission_PermissionManager();
        $permission_manager->save($permission_request, $permission_setter);

        $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId().'&func=admin-perms-tracker');
    }

    private function getPermissionSetter() {
        return new Tracker_Permission_PermissionSetter(
            $this->tracker,
            plugin_tracker_permission_get_tracker_ugroups_permissions(
                $this->tracker->getGroupId(),
                $this->tracker->getId()
            ),
            PermissionsManager::instance()
        );
    }
}
