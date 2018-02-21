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

namespace Tuleap\Svn\PerGroup;

use ProjectUGroup;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\Permission\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\Permission\PermissionPerGroupUGroupRetriever;
use Tuleap\Svn\SvnPermissionManager;
use UGroupManager;
use UserManager;

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
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(
        PermissionPerGroupUGroupRetriever $permission_retriever,
        PermissionPerGroupUGroupFormatter $formatter,
        UGroupManager $ugroup_manager,
        UserManager $user_manager
    ) {
        $this->formatter            = $formatter;
        $this->permission_retriever = $permission_retriever;
        $this->ugroup_manager       = $ugroup_manager;
        $this->user_manager         = $user_manager;
    }

    public function buildPresenter(PermissionPerGroupPaneCollector $event)
    {
        $selected_group = $event->getSelectedUGroupId();

        $permissions = new PermissionPerGroupSVNGlobalAdminPermissionCollection();
        $this->addUGroupsToPermissions($event, $permissions);

        $user_group = $this->ugroup_manager->getUGroup($event->getProject(), $selected_group);

        $user = $this->user_manager->getCurrentUser();

        return new PermissionPerGroupServicePresenter(
            $permissions->getPermissions(),
            $event->getProject(),
            $user,
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

            $permissions->addPermission(
                ProjectUGroup::PROJECT_ADMIN,
                $this->formatter->formatGroup($project, ProjectUGroup::PROJECT_ADMIN)
            );
        }

        if (count($ugroups) > 0 || (int) $event->getSelectedUGroupId() === ProjectUGroup::PROJECT_ADMIN) {
            $permissions->addPermission(
                ProjectUGroup::PROJECT_ADMIN,
                $this->formatter->formatGroup($project, ProjectUGroup::PROJECT_ADMIN)
            );
        }

        foreach ($ugroups as $ugroup) {
            $permissions->addPermission(
                $ugroup,
                $this->formatter->formatGroup($project, $ugroup)
            );
        }
    }

    private function extractUGroupsFromSelection(PermissionPerGroupPaneCollector $event)
    {
        $all_ugroups = $this->permission_retriever->getAllUGroupForObject(
            $event->getProject(),
            $event->getProject()->getID(),
            SvnPermissionManager::PERMISSION_ADMIN
        );

        if (in_array($event->getSelectedUGroupId(), $all_ugroups) ||
            ((int) $event->getSelectedUGroupId() === ProjectUGroup::PROJECT_ADMIN)
        ) {
            return $all_ugroups;
        }

        return [];
    }
}
