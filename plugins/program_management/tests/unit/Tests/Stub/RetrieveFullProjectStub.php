<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveFullProject;

final class RetrieveFullProjectStub implements RetrieveFullProject
{
    public function __construct(private array $project)
    {
    }

    public static function withProject(\Project $project): self
    {
        return new self([$project]);
    }

    public static function withoutProject(): self
    {
        return new self([]);
    }

    public function getProject(int $project_id): \Project
    {
        if (count($this->project) > 0) {
            return array_shift($this->project);
        }
        throw new \LogicException('No project configured');
    }

    /**
     * @no-named-arguments
     */
    public static function withSuccessiveProjects(\Project $first_project, \Project ...$other_projects): self
    {
        return new self([$first_project, ...$other_projects]);
    }
}
