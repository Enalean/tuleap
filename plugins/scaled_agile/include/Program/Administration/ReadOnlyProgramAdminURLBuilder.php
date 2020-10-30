<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Administration;

use Tuleap\ScaledAgile\Program\Backlog\ProgramDao;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningData;

class ReadOnlyProgramAdminURLBuilder
{
    /**
     * @var ProgramDao
     */
    private $program_dao;

    public function __construct(ProgramDao $program_dao)
    {
        $this->program_dao = $program_dao;
    }

    public function buildURL(PlanningData $planning, ?PlanningData $root_planning): ?string
    {
        if ($root_planning === null) {
            return null;
        }

        $project    = $planning->getProjectData();
        if (! $this->program_dao->isProjectAProgramProject($project->getId())) {
            return null;
        }

        $planning_id      = $planning->getId();
        $root_planning_id = $root_planning->getId();

        if ($planning_id !== $root_planning_id) {
            return null;
        }

        return '/project/' . urlencode($project->getName()) . '/backlog/admin/' . urlencode((string) $planning_id);
    }
}
