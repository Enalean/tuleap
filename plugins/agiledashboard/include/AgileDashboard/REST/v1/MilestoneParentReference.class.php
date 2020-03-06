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

use Tuleap\Tracker\REST\TrackerReference;
use Planning_Milestone;
use Tuleap\REST\JsonCast;

class MilestoneParentReference
{
    /**
     * @var int ID of the milestone
     */
    public $id;

    /**
     * @var string URI of the milestone
     */
    public $uri;

    /**
     * @var \Tuleap\Tracker\REST\TrackerReference
     */
    public $tracker;

    public function build(Planning_Milestone $milestone)
    {
        $this->id  = JsonCast::toInt($milestone->getArtifactId());
        $this->uri = MilestoneRepresentation::ROUTE . '/' . $this->id;

        $this->tracker = new TrackerReference();
        $this->tracker->build($milestone->getArtifact()->getTracker());
    }
}
