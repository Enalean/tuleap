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

namespace Tuleap\MultiProjectBacklog\Contributor;

use Tuleap\AgileDashboard\Planning\RootPlanning\RootPlanningUpdateIsAllowedEvent;

class RootPlanningUpdateIsAllowedHandler
{
    /**
     * @var ContributorDao
     */
    private $contributor_dao;

    public function __construct(ContributorDao $contributor_dao)
    {
        $this->contributor_dao = $contributor_dao;
    }

    public function handle(RootPlanningUpdateIsAllowedEvent $event): void
    {
        $is_contributor_project = $this->contributor_dao->isProjectAContributorProject(
            (int) $event->getProject()->getID()
        );
        if ($is_contributor_project && $this->didMilestoneTrackerChange($event)) {
            $event->disallowPlanningUpdate();
        }
    }

    private function didMilestoneTrackerChange(RootPlanningUpdateIsAllowedEvent $event): bool
    {
        return (int) $event->getOriginalPlanning()->getPlanningTrackerId()
            !== (int) $event->getUpdatedPlanning()->planning_tracker_id;
    }
}
