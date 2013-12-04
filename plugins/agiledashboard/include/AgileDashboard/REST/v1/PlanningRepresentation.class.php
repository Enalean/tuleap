<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

use \Planning;
use \Tuleap\REST\ResourceReference;
use \Tuleap\Project\REST\ProjectReference;
use \Tuleap\Tracker\REST\TrackerRepresentation;

/**
 * Basic representation of a planning
 */
class PlanningRepresentation {

    const ROUTE = 'plannings';

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $uri;

    /**
     * @var String
     */
    public $label;

    /**
     * @var Tuleap\REST\ResourceReference
     */
    public $project;

    /**
     * @var Tuleap\REST\ResourceReference
     */
    public $milestone_tracker;

    /**
     * @var Array {@type Tuleap\REST\ResourceReference}
     */
    public $backlog_trackers;

    /**
     * @var string
     */
    public $milestones_uri;

    public function build(Planning $planning) {
        $this->id                = $planning->getId();
        $this->uri               = ResourceReference::NO_ROUTE;
        $this->label             = $planning->getName();
        $this->milestones_uri    = self::ROUTE .'/'. $this->id .'/'. MilestoneRepresentation::ROUTE;
        $this->milestone_tracker = new ResourceReference();
        $this->milestone_tracker->build($planning->getPlanningTrackerId(), TrackerRepresentation::ROUTE);
        $this->project           = new ProjectReference($planning->getGroupId());
        $this->backlog_trackers  = array_map(
            function ($id) {
                $reference = new ResourceReference();
                $reference->build($id, TrackerRepresentation::ROUTE);

                return $reference;
            },
            $planning->getBacklogTrackersIds()
        );
    }
}
