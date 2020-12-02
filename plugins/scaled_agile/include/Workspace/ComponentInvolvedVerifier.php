<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Workspace;

use Tuleap\ScaledAgile\Program\ProgramStore;
use Tuleap\ScaledAgile\Project;
use Tuleap\ScaledAgile\Team\Creation\TeamStore;

final class ComponentInvolvedVerifier
{
    /**
     * @var TeamStore
     */
    private $team_store;
    /**
     * @var ProgramStore
     */
    private $program_store;

    public function __construct(TeamStore $team_store, ProgramStore $program_store)
    {
        $this->team_store    = $team_store;
        $this->program_store = $program_store;
    }

    public function isInvolvedInAScaledAgileWorkspace(Project $project_data): bool
    {
        return $this->team_store->isATeam($project_data->getId()) || $this->program_store->isProjectAProgramProject($project_data->getId());
    }
}
