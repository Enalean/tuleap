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
use Tuleap\ProFTPd\SystemEventManager;
use Tuleap\ProFTPd\Presenter\AdminPresenter;
use HTTPRequest;
use Project;
use Feedback;

class AdminController
{
    public const NAME = 'admin';

    /** @var PermissionsManager */
    private $permissions_manager;

    /** @var SystemEventManager */
    private $system_event_manager;

    public function __construct(PermissionsManager $permissions_manager, SystemEventManager $system_event_manager)
    {
        $this->permissions_manager  = $permissions_manager;
        $this->system_event_manager = $system_event_manager;
    }

    public function getName()
    {
        return self::NAME;
    }

    /**
     * @return bool
     */
    private function userIsAdmin(HTTPRequest $request)
    {
        return $request->getProject()->userIsAdmin($request->getCurrentUser());
    }

    public function index(ServiceProFTPd $service, HTTPRequest $request)
    {
        $this->checkUserIsAdmin($request);

        $service->renderInPage(
            $request,
            $GLOBALS['Language']->getText('global', 'Admin'),
            'admin',
            $this->getPresenter($request->getProject())
        );
    }

    private function getPresenter(Project $project)
    {
        return new AdminPresenter(
            $project->getID(),
            $this->permissions_manager->getUGroups($project),
            $this->permissions_manager->getSelectUGroupFor($project, PermissionsManager::PERM_READ),
            $this->permissions_manager->getSelectUGroupFor($project, PermissionsManager::PERM_WRITE)
        );
    }

    public function save(ServiceProFTPd $service, HTTPRequest $request)
    {
        $this->checkUserIsAdmin($request);

        $this->saveForAdmin($request);
    }

    private function checkUserIsAdmin(HTTPRequest $request)
    {
        if (! $this->userIsAdmin($request)) {
            exit_error(
                $GLOBALS['Language']->getText('global', 'error_perm_denied'),
                dgettext('tuleap-proftpd', 'You must be granted Project Administrator permission to access this')
            );
        }
    }

    private function saveForAdmin(HTTPRequest $request)
    {
        $project = $request->getProject();
        if ($project && ! $project->isError()) {
            $this->saveForProject($project, $request);
        }
    }

    private function saveForProject(Project $project, HTTPRequest $request)
    {
        $this->savePermissions($project, $request);
        $this->system_event_manager->queueACLUpdate($project->getUnixName());

        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            dgettext('tuleap-proftpd', 'Permissions will be propagated on filesystem shortly')
        );

        $GLOBALS['Response']->redirect('?' . http_build_query(array(
            'group_id'   => $project->getID(),
            'controller' => self::NAME,
            'action'     => 'index',
        )));
    }

    private function savePermissions(Project $project, HTTPRequest $request)
    {
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
    }

    private function getUGroupsForPermission(HTTPRequest $request, $permission)
    {
        return array(
            (int) $request->getInArray('permissions', $permission)
        );
    }
}
