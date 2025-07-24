<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Test\Stubs;

use Project_NotFoundException;
use Tuleap\Project\ProjectByIDFactory;

final class ProjectByIDFactoryStub implements ProjectByIDFactory
{
    /**
     * @var array<int, \Project>
     */
    private array $projects;

    private function __construct(\Project ...$projects)
    {
        foreach ($projects as $project) {
            $this->projects[(int) $project->getID()] = $project;
        }
    }

    public static function buildWithoutProject(): self
    {
        return new self();
    }

    /**
     * @no-named-arguments
     */
    public static function buildWith(\Project $first_project, \Project ...$other_projects): self
    {
        return new self($first_project, ...$other_projects);
    }

    #[\Override]
    public function getValidProjectById(int $project_id): \Project
    {
        if (isset($this->projects[$project_id]) && $this->isValid($this->projects[$project_id])) {
            return $this->projects[$project_id];
        }
        throw new Project_NotFoundException();
    }

    private function isValid(\Project $project): bool
    {
        return ! $project->isError() && ! $project->isDeleted();
    }

    #[\Override]
    public function getProjectById(int $project_id): \Project
    {
        if (isset($this->projects[$project_id])) {
            return $this->projects[$project_id];
        }
        return new \Project(false);
    }
}
