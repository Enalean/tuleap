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

namespace Tuleap\SVN\PermissionsPerGroup;

use ProjectUGroup;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRetriever;
use Tuleap\SVN\SvnPermissionManager;
use UGroupManager;

class PermissionPerGroupSVNServicePaneBuilder
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
        $selected_group = $event->getSelectedUGroupId();

        $permissions = new PermissionPerGroupSVNGlobalAdminPermissionCollection();
        $this->addUGroupsToPermissions($event, $permissions);

        $user_group = $this->ugroup_manager->getUGroup($event->getProject(), $selected_group);

        return new PermissionPerGroupServicePresenter(
            $permissions->getPermissions(),
            $event->getProject(),
            $user_group
        );
    }

    private function addUGroupsToPermissions(
        PermissionPerGroupPaneCollector $event,
        PermissionPerGroupSVNGlobalAdminPermissionCollection $permissions
    ) {
        $project = $event->getProject();

        if ($event->getSelectedUGroupId()) {
            $ugroups = $this->extractUGroupsFromSelection($event);
        } else {
            $ugroups = $this->permission_retriever->getAllUGroupForObject(
                $project,
                $project->getID(),
                SvnPermissionManager::PERMISSION_ADMIN
            );

            $user_group = $this->ugroup_manager->getProjectAdminsUGroup($project);
            $permissions->addPermission(
                ProjectUGroup::PROJECT_ADMIN,
                $this->formatter->formatGroup($user_group)
            );
        }

        if (count($ugroups) > 0 || (int) $event->getSelectedUGroupId() === ProjectUGroup::PROJECT_ADMIN) {
            $user_group = $this->ugroup_manager->getProjectAdminsUGroup($project);
            $permissions->addPermission(
                ProjectUGroup::PROJECT_ADMIN,
                $this->formatter->formatGroup($user_group)
            );
        }

        foreach ($ugroups as $ugroup) {
            $user_group = $this->ugroup_manager->getUGroup($project, $ugroup);
            if ($user_group) {
                $permissions->addPermission(
                    $ugroup,
                    $this->formatter->formatGroup($user_group)
                );
            }
        }
    }

    private function extractUGroupsFromSelection(PermissionPerGroupPaneCollector $event)
    {
        $all_ugroups = $this->permission_retriever->getAllUGroupForObject(
            $event->getProject(),
            $event->getProject()->getID(),
            SvnPermissionManager::PERMISSION_ADMIN
        );

        if (
            in_array($event->getSelectedUGroupId(), $all_ugroups) ||
            ((int) $event->getSelectedUGroupId() === ProjectUGroup::PROJECT_ADMIN)
        ) {
            return $all_ugroups;
        }

        return [];
    }
}
