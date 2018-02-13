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

namespace Tuleap\FRS\PerGroup;

use ForgeConfig;
use Project;
use ProjectUGroup;
use TemplateRendererFactory;
use Tuleap\FRS\FRSPermission;
use Tuleap\FRS\FRSPermissionFactory;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupPanePresenter;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\Permission\PermissionPerGroupCollection;
use Tuleap\Project\Admin\Permission\PermissionPerGroupPaneCollector;

class PermissionPerGroupFRSServicePresenterBuilder
{
    /**
     * @var FRSPermissionFactory
     */
    private $frs_permission_factory;
    /**
     * @var PermissionPerGroupUGroupFormatter
     */
    private $formatter;
    /**
     * @var \UGroupManager
     */
    private $ugroup_manager;

    public function __construct(
        FRSPermissionFactory $frs_permission_factory,
        PermissionPerGroupUGroupFormatter $formatter,
        \UGroupManager $ugroup_manager
    ) {
        $this->frs_permission_factory = $frs_permission_factory;
        $this->formatter              = $formatter;
        $this->ugroup_manager         = $ugroup_manager;
    }

    public function getPanePresenter(Project $project, $selected_ugroup)
    {
        $permissions = new PermissionPerGroupCollection();
        $this->extractPermissionByType(
            $project,
            $permissions,
            FRSPermission::FRS_ADMIN,
            $GLOBALS['Language']->getText('file_file_utils', 'administrators_title'),
            $selected_ugroup
        );
        $this->extractPermissionByType(
            $project,
            $permissions,
            FRSPermission::FRS_READER,
            $GLOBALS['Language']->getText('file_file_utils', 'readers_title'),
            $selected_ugroup
        );

        $ugroup = $this->ugroup_manager->getUGroup($project, $selected_ugroup);

        return new PermissionPerGroupPanePresenter(
            $permissions->getPermissions(),
            $ugroup
        );
    }

    /**
     * @param Project $project
     * @param PermissionPerGroupCollection $permissions
     * @param                              $type
     *
     * @return array
     */
    private function extractPermissionByType(
        Project $project,
        PermissionPerGroupCollection $permissions,
        $type,
        $permission_title,
        $selected_ugroup = null
    ) {
        if ($selected_ugroup) {
            $ugroups = $this->extractUGroupsFromSelection($project, $type, $selected_ugroup);
        } else {
            $ugroups = $this->frs_permission_factory->getFrsUGroupsByPermission($project, $type);
        }

        if (! isset($ugroups)) {
            return;
        }

        $formatted_group = array();
        if ($type === FRSPermission::FRS_ADMIN) {
            $formatted_group[] = $this->addProjectAdministratorToPermissions($project);
        }

        foreach ($ugroups as $ugroup) {
            $formatted_group[] = $this->formatter->formatGroup($project, $ugroup->getUGroupId());
        }

        $permissions->addPermissions(array('name' => $permission_title, 'groups' => $formatted_group));
    }

    /**
     * @param Project $project
     * @param         $type
     * @param         $selected_ugroup
     *
     * @return FRSPermission[]
     */
    private function extractUGroupsFromSelection(Project $project, $type, $selected_ugroup = null)
    {
        $all_ugroups = $this->frs_permission_factory->getFrsUGroupsByPermission($project, $type);
        if (isset($all_ugroups[$selected_ugroup])) {
            return $all_ugroups;
        }

        return;
    }

    private function addProjectAdministratorToPermissions(Project $project)
    {
        return $this->formatter->formatGroup($project, ProjectUGroup::PROJECT_ADMIN);
    }
}
