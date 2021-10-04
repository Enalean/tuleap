<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Workspace\RetrieveProject;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class RetrieveProjectStub implements RetrieveProject
{
    /**
     * @var \Project[]
     */
    private array $projects;

    /**
     * @param \Project[] $projects
     */
    public function __construct(array $projects)
    {
        $this->projects = $projects;
    }

    public static function withValidProjects(\Project ...$projects): self
    {
        return new self($projects);
    }

    public static function withoutProjects(): self
    {
        return new self([]);
    }

    public function getProjectWithId(int $project_id): \Project
    {
        if (count($this->projects) > 0) {
            return array_shift($this->projects);
        }

        throw new \LogicException('No project configured');
    }

    public function getProjectsUserIsAdmin(UserIdentifier $user_identifier): array
    {
        return $this->projects;
    }
}
