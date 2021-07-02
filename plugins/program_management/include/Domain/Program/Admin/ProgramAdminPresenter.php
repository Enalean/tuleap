<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Admin;

use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTeam\PotentialTeamPresenter;
use Tuleap\ProgramManagement\Domain\Program\Admin\Team\TeamPresenter;

/**
 * @psalm-immutable
 */
final class ProgramAdminPresenter
{
    /**
     * @var PotentialTeamPresenter[]
     */
    public array $potential_teams;
    /**
     * @var TeamPresenter[]
     */
    public array $aggregated_teams;
    public bool $has_aggregated_teams;

    /**
     * @param PotentialTeamPresenter[] $potential_teams
     * @param TeamPresenter[] $aggregated_teams
     */
    public function __construct(array $potential_teams, array $aggregated_teams)
    {
        $this->potential_teams      = $potential_teams;
        $this->aggregated_teams     = $aggregated_teams;
        $this->has_aggregated_teams = count($aggregated_teams) > 0;
    }
}
