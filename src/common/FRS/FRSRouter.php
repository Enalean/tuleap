<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
use Project;
use Feedback;
use UserManager;
use PFUser;

class FRSRouter
{
    /** @var PermissionController */
    private $permission_controller;

    public function __construct(PermissionController $permission_controller)
    {
        $this->permission_controller = $permission_controller;
    }

    /**
     * Routes the request to the correct controller
     * @return void
     */
    public function route(HTTPRequest $request, Project $project)
    {
        $user = UserManager::instance()->getCurrentUser();
        if (! $request->get('action')) {
            $this->useDefaultRoute($project, $user);
            return;
        }

        $action = $request->get('action');

        switch ($action) {
            case 'admin-frs-admins':
                if (! $request->isPost()) {
                    $this->redirectToDefaultRoute($project);
                }
                $admin_ugroups_ids  = $request->get('permission_frs_admins');
                $reader_ugroups_ids = $request->get('permission_frs_readers');
                if (! $admin_ugroups_ids) {
                    $admin_ugroups_ids = [];
                }
                if (! $reader_ugroups_ids) {
                    $reader_ugroups_ids = [];
                }

                if (! is_array($admin_ugroups_ids) || ! is_array($reader_ugroups_ids)) {
                    $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('file_file_utils', 'error_data_incorrect'));
                    $this->redirectToDefaultRoute($project);
                }

                $this->getCSRFToken($project)->check();
                $this->permission_controller->updatePermissions($project, $user, $admin_ugroups_ids, $reader_ugroups_ids);
                $this->redirectToDefaultRoute($project);
                // Redirect never returns
            case 'edit-permissions':
                // Default case
            default:
                $this->useDefaultRoute($project, $user);
                break;
        }
    }

    private function redirectToDefaultRoute(Project $project): never
    {
        $GLOBALS['Response']->redirect($this->defaultURL($project));
        exit;
    }

    private function getCSRFToken(Project $project): \CSRFSynchronizerToken
    {
        return new \CSRFSynchronizerToken($this->defaultURL($project));
    }

    private function defaultURL(Project $project): string
    {
        return '/file/admin/?' . http_build_query(
            [
                'group_id' => (string) $project->getId(),
                'action'   => 'edit-permissions',
            ]
        );
    }

    private function useDefaultRoute(Project $project, PFUser $user)
    {
        $this->permission_controller->displayPermissions($project, $user, $this->getCSRFToken($project));
    }
}
