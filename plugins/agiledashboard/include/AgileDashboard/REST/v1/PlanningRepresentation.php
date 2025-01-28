<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\AgileDashboard\REST\v1;

use Planning;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\REST\JsonCast;
use Tuleap\REST\ResourceReference;
use Tuleap\Tracker\REST\CompleteTrackerRepresentation;
use Tuleap\Tracker\REST\TrackerReference;

/**
 * Basic representation of a planning
 *
 * @psalm-immutable
 */
final readonly class PlanningRepresentation extends PlanningRepresentationBase
{
    public function __construct(Planning $planning)
    {
        $planning_id = JsonCast::toInt($planning->getId());
        parent::__construct(
            $planning_id,
            ResourceReference::NO_ROUTE,
            $planning->getName(),
            new ProjectReference($planning->getGroupId()),
            TrackerReference::build($planning->getPlanningTracker()),
            array_map(
                static fn(int $id) => new ResourceReference($id, CompleteTrackerRepresentation::ROUTE),
                $planning->getBacklogTrackersIds(),
            ),
            self::ROUTE . '/' . $planning_id . '/' . MilestoneRepresentation::ROUTE,
        );
    }
}
