<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Baseline\Support;

use Tuleap\Baseline\Adapter\ProjectProxy;
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Domain\Role;
use Tuleap\Baseline\Domain\RoleAssignment;
use Tuleap\Baseline\Stub\RetrieveBaselineUserGroupStub;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class RoleAssignmentTestBuilder
{
    private ?ProjectIdentifier $project;

    /**
     * @var \ProjectUGroup[]
     */
    private array $user_groups = [];

    private function __construct(private Role $role)
    {
    }

    public static function aRoleAssignment(Role $role): self
    {
        return new self($role);
    }

    public function withProject(ProjectIdentifier $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function withUserGroups(\ProjectUGroup ...$user_groups): self
    {
        $this->user_groups = $user_groups;

        return $this;
    }

    /**
     * @return RoleAssignment[]
     */
    public function build(): array
    {
        return RoleAssignment::fromRoleAssignmentsIds(
            RetrieveBaselineUserGroupStub::withUserGroups(
                ...$this->user_groups
            ),
            $this->project ?? ProjectProxy::buildFromProject(
                ProjectTestBuilder::aProject()->build()
            ),
            $this->role,
            ...array_map(static fn($user_group) => $user_group->getId(), $this->user_groups)
        );
    }
}
