<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\FRS;

use HTTPRequest;
use TemplateRendererFactory;
use ForgeConfig;
use UGroupManager;
use Project;
use Feedback;
use UserManager;
use PFUser;

class FRSRouter
{
    /** @var Project */
    private $project;
    /** @var PermissionController */
    private $permission_controller;

    public function __construct(PermissionController $permission_controller)
    {
        $this->permission_controller = $permission_controller;
    }

    /**
     * Routes the request to the correct controller
     * @param HTTPRequest $request
     * @return void
     */
    public function route(HTTPRequest $request, Project $project)
    {
        $user = UserManager::instance()->getCurrentUser();
        if (! $request->get('action')) {
            $this->useDefaultRoute($request, $project, $user);
            return;
        }

        $action = $request->get('action');

        switch ($action) {
            case "edit-permissions":
                $this->useDefaultRoute($request, $project, $user);
                break;
            case "admin-frs-admins":
                $select_project_ids = $request->get('frs_admins');

                if ($select_project_ids) {
                    $this->permission_controller->updatePermissions($project, $user, $select_project_ids);
                } else {
                    $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('file_file_utils', 'no_data_selected'));
                }

                $this->useDefaultRoute($request, $project, $user);
                break;
            default:
                $this->useDefaultRoute($request, $project, $user);
                break;
        }
    }

    /**
     * @param HTTPRequest $request
     */
    private function useDefaultRoute(HTTPRequest $request, Project $project, PFUser $user)
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') .'/src/templates/frs');

        $this->permission_controller->displayToolbar($project);
        $this->permission_controller->displayPermissions($project, $user);
    }
}
