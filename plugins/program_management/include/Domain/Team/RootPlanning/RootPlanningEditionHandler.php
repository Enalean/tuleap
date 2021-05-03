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

namespace Tuleap\ProgramManagement\Domain\Team\RootPlanning;

use Tuleap\AgileDashboard\Planning\RootPlanning\RootPlanningEditionEvent;
use Tuleap\ProgramManagement\Domain\Team\Creation\TeamStore;

final class RootPlanningEditionHandler
{
    /**
     * @var TeamStore
     */
    private $team_store;

    public function __construct(TeamStore $team_store)
    {
        $this->team_store = $team_store;
    }

    public function handle(RootPlanningEditionEvent $event): void
    {
        if ($this->team_store->isATeam((int) $event->getProject()->getID())) {
            $event->prohibitMilestoneTrackerModification(new MilestoneTrackerUpdateProhibited());
        }
    }
}
