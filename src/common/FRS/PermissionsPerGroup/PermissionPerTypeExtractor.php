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

namespace Tuleap\FRS\PermissionsPerGroup;

use Project;
use ProjectUGroup;
use Tuleap\FRS\FRSPermission;
use Tuleap\FRS\FRSPermissionFactory;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupCollection;
use UGroupManager;

class PermissionPerTypeExtractor
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
     * @var FRSPermissionPerGroupURLBuilder
     */
    private $url_builder;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(
        FRSPermissionFactory $frs_permission_factory,
        PermissionPerGroupUGroupFormatter $formatter,
        FRSPermissionPerGroupURLBuilder $url_builder,
        UGroupManager $ugroup_manager
    ) {
        $this->frs_permission_factory = $frs_permission_factory;
        $this->formatter              = $formatter;
        $this->url_builder            = $url_builder;
        $this->ugroup_manager         = $ugroup_manager;
    }

    /**
     * @param                              $type
     *
     * @return array
     */
    public function extractPermissionByType(
        Project $project,
        PermissionPerGroupCollection $permissions,
        $type,
        $permission_title,
        $selected_ugroup_id = null
    ) {
        $formatted_permissions = new FrsGlobalAdminPermissionCollection();
        $this->addUGroupsToPermissions($project, $type, $selected_ugroup_id, $formatted_permissions);

        if (count($formatted_permissions->getPermissions()) > 0) {
            $permissions->addPermissions(
                array(
                    'name'   => $permission_title,
                    'groups' => $formatted_permissions->getPermissions(),
                    'url'    => $this->url_builder->getGlobalAdminLink($project)
                )
            );
        }
    }

    private function addUGroupsToPermissions(
        Project $project,
        $type,
        $selected_ugroup_id,
        FrsGlobalAdminPermissionCollection $permissions
    ) {
        if ($selected_ugroup_id) {
            $ugroups = $this->extractUGroupsFromSelection($project, $type, $selected_ugroup_id);
        } else {
            $ugroups = $this->frs_permission_factory->getFrsUGroupsByPermission($project, $type);
            $this->addProjectAdministratorsToFRSAdminPermissions($project, $type, $permissions);
        }

        if (count($ugroups) > 0 || (int) $selected_ugroup_id === ProjectUGroup::PROJECT_ADMIN) {
            $this->addProjectAdministratorsToFRSAdminPermissions($project, $type, $permissions);
        }

        foreach ($ugroups as $ugroup) {
            $user_group = $this->ugroup_manager->getUGroup($project, $ugroup->getUGroupId());
            if ($user_group) {
                $permissions->addPermission(
                    $ugroup->getUGroupId(),
                    $this->formatter->formatGroup($user_group)
                );
            }
        }
    }

    /**
     * @param         $type
     * @param         $selected_ugroup
     *
     * @return FRSPermission[]
     */
    private function extractUGroupsFromSelection(Project $project, $type, $selected_ugroup)
    {
        $all_ugroups = $this->frs_permission_factory->getFrsUGroupsByPermission($project, $type);

        if (isset($all_ugroups[$selected_ugroup]) ||
            ((int) $selected_ugroup === ProjectUGroup::PROJECT_ADMIN && $type === FRSPermission::FRS_ADMIN)
        ) {
            return $all_ugroups;
        }

        return [];
    }

    private function addProjectAdministratorsToFRSAdminPermissions(
        Project $project,
        $type,
        FrsGlobalAdminPermissionCollection $permission
    ) {
        if ($type === FRSPermission::FRS_ADMIN) {
            $user_group = $this->ugroup_manager->getProjectAdminsUGroup($project);
            $permission->addPermission(
                ProjectUGroup::PROJECT_ADMIN,
                $this->formatter->formatGroup($user_group)
            );
        }
    }
}
