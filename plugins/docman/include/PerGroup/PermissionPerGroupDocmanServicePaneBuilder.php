<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Docman\PerGroup;

use Docman_PermissionsManager;
use ProjectUGroup;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupPanePresenter;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\Permission\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\Permission\PermissionPerGroupUGroupRetriever;
use UGroupManager;

class PermissionPerGroupDocmanServicePaneBuilder
{
    /**
     * @var PermissionPerGroupUGroupFormatter
     */
    private $formatter;
    /**
     * @var PermissionPerGroupUGroupRetriever
     */
    private $permission_retriever;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(
        PermissionPerGroupUGroupRetriever $permission_retriever,
        PermissionPerGroupUGroupFormatter $formatter,
        UGroupManager $ugroup_manager
    ) {
        $this->formatter            = $formatter;
        $this->permission_retriever = $permission_retriever;
        $this->ugroup_manager       = $ugroup_manager;
    }

    public function buildPresenter(PermissionPerGroupPaneCollector $event)
    {
        $formatted_permissions = new DocmanGlobalAdminPermissionCollection();
        $this->addGroupsToPermission($event, $formatted_permissions);

        $selected_group = $event->getSelectedUGroupId();
        $user_group     = $this->ugroup_manager->getUGroup($event->getProject(), $selected_group);

        $docman_admin = [];
        if (count($formatted_permissions->getPermissions()) > 0) {
            $docman_admin = [
                "name" => dgettext('tuleap-docman', 'Docman Administrators'),
                "groups" => $formatted_permissions->getPermissions()
            ];
        }

        return new PermissionPerGroupPanePresenter($docman_admin, $user_group);
    }

    private function addGroupsToPermission(
        PermissionPerGroupPaneCollector $event,
        DocmanGlobalAdminPermissionCollection $permissions
    ) {
        if ($event->getSelectedUGroupId()) {
            $all_permissions = $this->permission_retriever->getAdminUGroupIdsForProjectContainingUGroupId(
                $event->getProject(),
                Docman_PermissionsManager::PLUGIN_DOCMAN_ADMIN,
                $event->getSelectedUGroupId()
            );
        } else {
            $all_permissions = $this->permission_retriever->getAllUGroupForProject(
                $event->getProject(),
                Docman_PermissionsManager::PLUGIN_DOCMAN_ADMIN
            );
        }

        foreach ($all_permissions as $permission) {
            $permissions->addPermission(
                $permission,
                $this->formatter->formatGroup($event->getProject(), $permission)
            );
        }
    }
}
