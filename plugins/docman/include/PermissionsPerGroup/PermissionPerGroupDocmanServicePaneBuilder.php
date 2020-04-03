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

namespace Tuleap\Docman\PermissionsPerGroup;

use Docman_PermissionsManager;
use Project;
use ProjectUGroup;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPanePresenter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRetriever;
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
                "name"   => dgettext('tuleap-docman', 'Document manager administrators'),
                "groups" => $formatted_permissions->getPermissions(),
                "url"    => $this->getGlobalAdminLink($event)
            ];
        }

        return new PermissionPerGroupPanePresenter($docman_admin, $user_group);
    }

    private function addGroupsToPermission(
        PermissionPerGroupPaneCollector $event,
        DocmanGlobalAdminPermissionCollection $permissions
    ) {
        if ($event->getSelectedUGroupId()) {
            $all_permissions = $this->extractUGroupsFromSelection($event);
        } else {
            $all_permissions = $this->permission_retriever->getAllUGroupForObject(
                $event->getProject(),
                $event->getProject()->getID(),
                Docman_PermissionsManager::PLUGIN_DOCMAN_ADMIN
            );
            $this->addProjectAdministrators($event->getProject(), $permissions);
        }

        if (count($all_permissions) > 0 || (int) $event->getSelectedUGroupId() === ProjectUGroup::PROJECT_ADMIN) {
            $this->addProjectAdministrators($event->getProject(), $permissions);
        }

        foreach ($all_permissions as $permission) {
            $user_group = $this->ugroup_manager->getUGroup($event->getProject(), $permission);
            if ($user_group) {
                $permissions->addPermission(
                    $permission,
                    $this->formatter->formatGroup($user_group)
                );
            }
        }
    }

    private function addProjectAdministrators(Project $project, DocmanGlobalAdminPermissionCollection $permissions)
    {
        $user_group = $this->ugroup_manager->getProjectAdminsUGroup($project);
        $permissions->addPermission(
            ProjectUGroup::PROJECT_ADMIN,
            $this->formatter->formatGroup($user_group)
        );
    }

    private function extractUGroupsFromSelection(PermissionPerGroupPaneCollector $event)
    {
        $all_ugroups = $this->permission_retriever->getAllUGroupForObject(
            $event->getProject(),
            $event->getProject()->getID(),
            Docman_PermissionsManager::PLUGIN_DOCMAN_ADMIN
        );

        if (
            in_array($event->getSelectedUGroupId(), $all_ugroups) ||
            ((int) $event->getSelectedUGroupId() === ProjectUGroup::PROJECT_ADMIN)
        ) {
            return $all_ugroups;
        }

        return [];
    }

    private function getGlobalAdminLink(PermissionPerGroupPaneCollector $event)
    {
        return DOCMAN_BASE_URL . "/?" . http_build_query(
            [
                "group_id" => $event->getProject()->getID(),
                "action"   => "admin_permissions"
            ]
        );
    }
}
