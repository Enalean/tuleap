<?php
/**
 * Copyright (c) Enalean, 2013-2014. All Rights Reserved.
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
use Tuleap\REST\JsonCast;
use Tuleap\REST\ResourceReference;
use Tuleap\REST\v1\PlanningRepresentationBase;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\Tracker\REST\TrackerReference;
use Tuleap\Tracker\REST\CompleteTrackerRepresentation;

/**
 * Basic representation of a planning
 */
class PlanningRepresentation extends PlanningRepresentationBase
{

    public function build(Planning $planning)
    {
        $this->id                = JsonCast::toInt($planning->getId());
        $this->uri               = ResourceReference::NO_ROUTE;
        $this->label             = $planning->getName();
        $this->milestones_uri    = self::ROUTE . '/' . $this->id . '/' . MilestoneRepresentation::ROUTE;

        $this->milestone_tracker = new TrackerReference();
        $this->milestone_tracker->build($planning->getPlanningTracker());

        $this->project = new ProjectReference();
        $this->project->build($planning->getGroupId());

        $this->backlog_trackers = array_map(
            function ($id) {
                $reference = new ResourceReference();
                $reference->build($id, CompleteTrackerRepresentation::ROUTE);

                return $reference;
            },
            $planning->getBacklogTrackersIds()
        );
    }
}
