<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Baseline\Stub;

use PFUser;
use Project;
use Tuleap\Baseline\Domain\ProjectRepository;

class ProjectRepositoryStub implements ProjectRepository
{
    /** @var array Project[] */
    private $projects = [];

    public function add(Project $project): void
    {
        $this->projects[] = $project;
    }

    public function findById(PFUser $current_user, int $id): ?Project
    {
        $matching_projects = array_filter(
            $this->projects,
            function (Project $project) use ($id) {
                return $project->getId() === $id;
            }
        );
        if (count($matching_projects) === 0) {
            return null;
        }
        return $matching_projects[0];
    }
}
