<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\REST\v1;

use Tuleap\Project\REST\ProjectReference;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\REST\TrackerReference;
use Tuleap\User\REST\MinimalUserRepresentation;

class CrossTrackerArtifactReportRepresentation
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $status;

    /**
     * @var int
     */
    public $last_update_date;

    /**
     * @var MinimalUserRepresentation|null
     */
    public $submitted_by;

    /**
     * @var MinimalUserRepresentation[]
     */
    public $assigned_to;

    /**
     * @var TrackerReference
     */
    public $tracker;

    /**
     * @var array
     */
    public $badge;

    /**
     * @var ProjectReference
     */
    public $project;

    public function build(\Tracker_Artifact $artifact, \PFUser $user)
    {
        $this->id               = JsonCast::toInt($artifact->getId());
        $this->title            = $artifact->getTitle() ?? '';
        $this->badge            =
            array(
                "uri"       => $artifact->getUri(),
                "color"     => $artifact->getTracker()->getColor()->getName(),
                "cross_ref" => $artifact->getXRef()
            );
        $this->status           = $artifact->getStatus();
        $this->last_update_date = JsonCast::toDate($artifact->getLastUpdateDate());

        $user_representation = new MinimalUserRepresentation();
        $user_representation->build($artifact->getSubmittedByUser());
        $this->submitted_by = $user_representation;

        foreach ($artifact->getAssignedTo($user) as $user_assigned_to) {
            $user_assigned_representation = new MinimalUserRepresentation();
            $user_assigned_representation->build($user_assigned_to);

            $this->assigned_to[] = $user_assigned_representation;
        }

        $tracker = $artifact->getTracker();
        $tracker_representation = new TrackerReference();
        $tracker_representation->build($tracker);

        $this->tracker = $tracker_representation;

        $project_reference = new ProjectReference();
        $project_reference->build($tracker->getProject());

        $this->project = $project_reference;
    }
}
