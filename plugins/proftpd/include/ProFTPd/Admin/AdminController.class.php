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

use Tuleap\ProFTPd\ServiceProFTPd;
use Tuleap\ProFTPd\Presenter\AdminPresenter;
use HTTPRequest;
use Project;
use Feedback;

class AdminController {
    const NAME = 'admin';

    /** @var PermissionsManager */
    private $permissions_manager;

    public function __construct(PermissionsManager $permissions_manager) {
        $this->permissions_manager = $permissions_manager;
    }

    public function getName() {
        return self::NAME;
    }

    /**
     * @param HTTPRequest $request
     * @return bool
     */
    private function userIsAdmin(HTTPRequest $request) {
        return $request->getProject()->userIsAdmin($request->getCurrentUser());
    }

    public function index(ServiceProFTPd $service, HTTPRequest $request) {
        if ($this->userIsAdmin($request)) {
            $service->renderInPage(
                $request,
                $GLOBALS['Language']->getText('global', 'Admin'),
                'admin',
                $this->getPresenter($request->getProject())
            );
        }
        exit_error($GLOBALS['Language']->getText('global', 'error_perm_denied'), $GLOBALS['Language']->getText('plugin_proftpd', 'error_not_admin'));
    }

    private function getPresenter(Project $project) {
        return new AdminPresenter(
            $project->getID(),
            $this->permissions_manager->getUGroups($project),
            $this->permissions_manager->getSelectUGroupFor($project, PermissionsManager::PERM_READ),
            $this->permissions_manager->getSelectUGroupFor($project, PermissionsManager::PERM_WRITE)
        );
    }

    public function save(ServiceProFTPd $service, HTTPRequest $request) {
        if ($this->userIsAdmin($request)) {
            $project = $request->getProject();
            if ($project && ! $project->isError()) {
                $this->permissions_manager->savePermission(
                    $project,
                    PermissionsManager::PERM_READ,
                    $this->getUGroupsForPermission(
                        $request,
                        PermissionsManager::PERM_READ
                    )
                );
                $this->permissions_manager->savePermission(
                    $project,
                    PermissionsManager::PERM_WRITE,
                    $this->getUGroupsForPermission(
                        $request,
                        PermissionsManager::PERM_WRITE
                    )
                );

                $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('plugin_proftpd', 'permissions_updated'));

                $GLOBALS['Response']->redirect('?'.http_build_query(array(
                    'group_id'   => $project->getID(),
                    'controller' => self::NAME,
                    'action'     => 'index',
                )));
            }
        }
        exit_error($GLOBALS['Language']->getText('global', 'error_perm_denied'), $GLOBALS['Language']->getText('plugin_proftpd', 'error_not_admin'));
    }

    private function getUGroupsForPermission(HTTPRequest $request, $permission) {
        return array(
            (int) $request->getInArray('permissions', $permission)
        );
    }
}
