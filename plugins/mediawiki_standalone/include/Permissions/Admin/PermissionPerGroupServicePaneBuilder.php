<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Permissions\Admin;

use Tuleap\MediawikiStandalone\Permissions\Permission;
use Tuleap\MediawikiStandalone\Permissions\PermissionAdmin;
use Tuleap\MediawikiStandalone\Permissions\PermissionRead;
use Tuleap\MediawikiStandalone\Permissions\PermissionWrite;
use Tuleap\MediawikiStandalone\Permissions\ProjectPermissions;
use Tuleap\MediawikiStandalone\Permissions\ProjectPermissionsRetriever;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPanePresenter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\UGroupRetriever;

class PermissionPerGroupServicePaneBuilder
{
    public function __construct(
        private PermissionPerGroupUGroupFormatter $formatter,
        private ProjectPermissionsRetriever $permissions_retriever,
        private UGroupRetriever $ugroup_retriever,
    ) {
    }

    public function buildPresenter(PermissionPerGroupPaneCollector $event): PermissionPerGroupPanePresenter
    {
        $project = $event->getProject();

        $selected_ugroup_id = $event->getSelectedUGroupId();
        $selected_ugroup    = $selected_ugroup_id
            ? $this->ugroup_retriever->getUGroup($project, $selected_ugroup_id)
            : null;

        return new PermissionPerGroupPanePresenter(
            $selected_ugroup
                ? $this->getPermissionsForUserGroup($project, $selected_ugroup)
                : $this->getAllPermissions($project),
            $selected_ugroup
        );
    }

    private function getAllPermissions(\Project $project): array
    {
        $project_permissions = $this->permissions_retriever->getProjectPermissions($project);

        return [
            $this->getAdministrators($project, $project_permissions),
            $this->getWriters($project, $project_permissions),
            $this->getReaders($project, $project_permissions),
        ];
    }

    private function getAdministrators(\Project $project, ProjectPermissions $project_permissions): array
    {
        return $this->getPermissionsFor(
            $project_permissions,
            new PermissionAdmin(),
            dgettext('tuleap-mediawiki_standalone', 'MediaWiki administrators'),
            $project
        );
    }

    private function getWriters(\Project $project, ProjectPermissions $project_permissions): array
    {
        return $this->getPermissionsFor(
            $project_permissions,
            new PermissionWrite(),
            dgettext('tuleap-mediawiki_standalone', 'MediaWiki writers'),
            $project
        );
    }

    private function getReaders(\Project $project, ProjectPermissions $project_permissions): array
    {
        return $this->getPermissionsFor(
            $project_permissions,
            new PermissionRead(),
            dgettext('tuleap-mediawiki_standalone', 'MediaWiki readers'),
            $project
        );
    }

    private function getPermissionsFor(
        ProjectPermissions $project_permissions,
        Permission $permission,
        string $name,
        \Project $project,
    ): array {
        $user_groups = [];

        $ugroup_ids = match ($permission->getName()) {
            PermissionAdmin::NAME => $project_permissions->admins,
            PermissionWrite::NAME => $project_permissions->writers,
            PermissionRead::NAME => $project_permissions->readers,
            default => []
        };
        foreach ($ugroup_ids as $ugroup_id) {
            $user_group = $this->ugroup_retriever->getUGroup($project, $ugroup_id);
            if ($user_group) {
                $user_groups[] = $this->formatter->formatGroup($user_group);
            }
        }

        return $this->getPresenter($project, $name, $user_groups);
    }

    private function getPermissionsForUserGroup(\Project $project, \ProjectUGroup $selected_ugroup): array
    {
        $project_permissions = $this->permissions_retriever->getProjectPermissions($project);

        $is_reader = in_array(
            $selected_ugroup->getId(),
            $project_permissions->readers,
            true
        );

        $is_writer = in_array(
            $selected_ugroup->getId(),
            $project_permissions->writers,
            true
        );

        $is_administrator = in_array(
            $selected_ugroup->getId(),
            $project_permissions->admins,
            true
        );

        $formatted_ugroup = $this->formatter->formatGroup($selected_ugroup);
        $permissions      = [];
        if ($is_administrator) {
            $permissions[] = $this->getPresenter(
                $project,
                dgettext('tuleap-mediawiki_standalone', 'MediaWiki administrators'),
                [$formatted_ugroup],
            );
        }
        if ($is_writer) {
            $permissions[] = $this->getPresenter(
                $project,
                dgettext('tuleap-mediawiki_standalone', 'MediaWiki writers'),
                [$formatted_ugroup],
            );
        }
        if ($is_reader) {
            $permissions[] = $this->getPresenter(
                $project,
                dgettext('tuleap-mediawiki_standalone', 'MediaWiki readers'),
                [$formatted_ugroup],
            );
        }

        return $permissions;
    }

    private function getPresenter(\Project $project, string $name, array $ugroups): array
    {
        return [
            'name' => $name,
            'groups' => $ugroups,
            'url' => AdminPermissionsController::getAdminUrl($project),
        ];
    }
}
