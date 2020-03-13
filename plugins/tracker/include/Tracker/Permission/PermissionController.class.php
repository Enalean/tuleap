<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

class Tracker_Permission_PermissionController implements Tracker_Dispatchable_Interface
{
    /** @var Tracker */
    private $tracker;

    /** @var TemplateRenderer */
    private $renderer;

    /** @var Tracker_Permission_PermissionPresenterBuilder */
    private $presenter_builder;

    public function __construct(Tracker $tracker)
    {
        $this->tracker           = $tracker;
        $this->renderer          = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR);
        $this->presenter_builder = new Tracker_Permission_PermissionPresenterBuilder();
    }

    public function getTracker()
    {
        return $this->tracker;
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        if ($request->get('update')) {
            $this->save($request);
        } else {
            $this->view($layout);
        }
    }

    private function view(Tracker_IDisplayTrackerLayout $layout)
    {
        $presenter = $this->presenter_builder->getPresenter($this->tracker);
        $this->tracker->displayAdminPermsHeader($layout, $presenter->title());
        $this->renderer->renderToPage('admin-perms-tracker', $presenter);
        $this->displayFooter($layout);
    }

    private function displayFooter(Tracker_IDisplayTrackerLayout $layout)
    {
        $this->tracker->displayFooter($layout);
    }

    private function save(Codendi_Request $request)
    {
        $permission_setter  = $this->getPermissionSetter();
        $permission_request = new Tracker_Permission_PermissionRequest(array());
        $permission_request->setFromRequest($request, $permission_setter->getAllGroupIds());

        $permission_manager = new Tracker_Permission_PermissionManager();
        $permission_manager->save($permission_request, $permission_setter);

        $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->tracker->getId() . '&func=admin-perms-tracker');
    }

    private function getPermissionSetter()
    {
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
