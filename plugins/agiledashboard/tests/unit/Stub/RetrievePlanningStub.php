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

namespace Tuleap\AgileDashboard\Stub;

use Tuleap\AgileDashboard\Planning\RetrievePlannings;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;

final class RetrievePlanningStub implements RetrievePlannings
{
    private bool $with_non_last_level_planning;
    private bool $with_last_level_planning;

    private function __construct(bool $with_non_last_level_planning, bool $with_last_level_planning)
    {
        $this->with_non_last_level_planning = $with_non_last_level_planning;
        $this->with_last_level_planning     = $with_last_level_planning;
    }

    public function getNonLastLevelPlannings(\PFUser $user, int $project_id): array
    {
        if ($this->with_non_last_level_planning) {
            return [PlanningBuilder::aPlanning($project_id)->build()];
        }

        return [];
    }

    public function getLastLevelPlannings(\PFUser $user, int $project_id): array
    {
        if ($this->with_last_level_planning) {
            return [PlanningBuilder::aPlanning($project_id)->build()];
        }

        return [];
    }

    public static function stubNonLastLevelPlanning(): self
    {
        return new self(true, false);
    }

    public static function stubLastLevelPlanning(): self
    {
        return new self(false, true);
    }

    public static function stubAllPlannings(): self
    {
        return new self(true, true);
    }

    public static function stubNoPlannings(): self
    {
        return new self(false, false);
    }
}
