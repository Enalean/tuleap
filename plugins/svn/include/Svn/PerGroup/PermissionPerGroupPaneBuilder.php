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

use Tuleap\Project\Admin\PerGroup\PermissionPerGroupPanePresenter;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\Permission\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\Permission\PermissionPerGroupUGroupRetriever;
use Tuleap\Svn\SvnPermissionManager;
use UGroupManager;

class PermissionPerGroupPaneBuilder
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
        $permissions = array();

        if ($event->getSelectedUGroupId()) {
            $all_permissions = $this->permission_retriever->getAdminUGroupIdsForProjectContainingUGroupId(
                $event->getProject(),
                SvnPermissionManager::PERMISSION_ADMIN,
                $event->getSelectedUGroupId()
            );
        } else {
            $all_permissions = $this->permission_retriever->getAllUGroupForProject(
                $event->getProject(),
                SvnPermissionManager::PERMISSION_ADMIN
            );
        }

        foreach ($all_permissions as $permission) {
            $permissions[] = $this->formatter->formatGroup($event->getProject(), $permission);
        }

        $selected_group = $event->getSelectedUGroupId();
        $ugroup         = $this->ugroup_manager->getUGroup($event->getProject(), $selected_group);

        return new PermissionPerGroupPanePresenter($permissions, $ugroup);
    }
}
