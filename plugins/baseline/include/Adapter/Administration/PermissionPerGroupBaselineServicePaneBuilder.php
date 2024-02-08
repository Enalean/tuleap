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

namespace Tuleap\Baseline\Adapter\Administration;

use Tuleap\Baseline\Adapter\ProjectProxy;
use Tuleap\Baseline\Domain\Role;
use Tuleap\Baseline\Domain\RoleAssignment;
use Tuleap\Baseline\Domain\RoleAssignmentRepository;
use Tuleap\Baseline\Domain\RoleBaselineAdmin;
use Tuleap\Baseline\Domain\RoleBaselineReader;
use Tuleap\Baseline\ServiceAdministrationController;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPanePresenter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\UGroupRetriever;

class PermissionPerGroupBaselineServicePaneBuilder
{
    public function __construct(
        private PermissionPerGroupUGroupFormatter $formatter,
        private RoleAssignmentRepository $role_assignment_repository,
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
        return [
            $this->getAdministrators($project),
            $this->getReaders($project),
        ];
    }

    private function getAdministrators(\Project $project): array
    {
        $admins = $this->getPermissionsFor(
            new RoleBaselineAdmin(),
            dgettext('tuleap-baseline', 'Baseline administrators'),
            $project
        );

        $project_admins = $this->ugroup_retriever->getUGroup($project, \ProjectUGroup::PROJECT_ADMIN);
        if ($project_admins) {
            array_unshift($admins['groups'], $this->formatter->formatGroup($project_admins));
        }

        return $admins;
    }

    private function getReaders(\Project $project): array
    {
        return $this->getPermissionsFor(new RoleBaselineReader(), dgettext('tuleap-baseline', 'Baseline readers'), $project);
    }

    private function getPermissionsFor(Role $role, string $name, \Project $project): array
    {
        $user_groups  = [];
        $assignements = $this->role_assignment_repository->findByProjectAndRole(
            ProjectProxy::buildFromProject($project),
            $role
        );
        foreach ($assignements as $assignment) {
            $user_group = $this->ugroup_retriever->getUGroup($project, $assignment->getUserGroupId());
            if ($user_group) {
                $user_groups[] = $this->formatter->formatGroup($user_group);
            }
        }

        return [
            'name'   => $name,
            'groups' => $user_groups,
            'url'    => ServiceAdministrationController::getAdminUrl($project),
        ];
    }

    private function getPermissionsForUserGroup(\Project $project, \ProjectUGroup $selected_ugroup): array
    {
        $is_administrator = $selected_ugroup->getId() === \ProjectUGroup::PROJECT_ADMIN
            || in_array(
                $selected_ugroup->getId(),
                array_map(
                    static fn (RoleAssignment $assignment): int => $assignment->getUserGroupId(),
                    $this->role_assignment_repository->findByProjectAndRole(ProjectProxy::buildFromProject($project), new RoleBaselineAdmin())
                ),
                true
            );
        $is_reader        = in_array(
            $selected_ugroup->getId(),
            array_map(
                static fn (RoleAssignment $assignment): int => $assignment->getUserGroupId(),
                $this->role_assignment_repository->findByProjectAndRole(ProjectProxy::buildFromProject($project), new RoleBaselineReader())
            ),
            true
        );

        $formatted_ugroup = $this->formatter->formatGroup($selected_ugroup);
        $permissions      = [];
        if ($is_administrator) {
            $permissions[] = [
                'name'   => dgettext('tuleap-baseline', 'Baseline administrators'),
                'groups' => [$formatted_ugroup],
                'url'    => ServiceAdministrationController::getAdminUrl($project),
            ];
        }
        if ($is_reader) {
            $permissions[] = [
                'name'   => dgettext('tuleap-baseline', 'Baseline readers'),
                'groups' => [$formatted_ugroup],
                'url'    => ServiceAdministrationController::getAdminUrl($project),
            ];
        }
        return $permissions;
    }
}
