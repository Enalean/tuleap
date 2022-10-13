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

namespace Tuleap\Baseline\Domain;

final class RoleAssignmentsUpdate
{
    /**
     * @param RoleAssignment[] $assignments
     */
    private function __construct(
        private ProjectIdentifier $project,
        private array $assignments,
    ) {
    }

    public static function build(
        ProjectIdentifier $project,
        RoleAssignment ...$assignments,
    ): self {
        foreach ($assignments as $assignment) {
            if ($assignment->getProject()->getID() !== $project->getID()) {
                throw new UserGroupDoesNotExistOrBelongToCurrentBaselineProjectException($assignment->getUserGroupId());
            }
        }

        return new self($project, $assignments);
    }

    public function getProject(): ProjectIdentifier
    {
        return $this->project;
    }

    /**
     * @return RoleAssignment[]
     */
    public function getAssignments(): array
    {
        return $this->assignments;
    }
}
