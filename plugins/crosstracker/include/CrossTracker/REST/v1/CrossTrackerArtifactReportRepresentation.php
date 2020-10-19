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

/**
 * @psalm-immutable
 */
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
     * @var string | null
     */
    public $status;

    /**
     * @var string
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

    private function __construct(
        int $id,
        string $title,
        ?string $status,
        string $last_update_date,
        ?MinimalUserRepresentation $submitted_by,
        array $assigned_to,
        TrackerReference $tracker,
        array $badge,
        ProjectReference $project
    ) {
        $this->id               = $id;
        $this->title            = $title;
        $this->status           = $status;
        $this->last_update_date = $last_update_date;
        $this->submitted_by     = $submitted_by;
        $this->assigned_to      = $assigned_to;
        $this->tracker          = $tracker;
        $this->badge            = $badge;
        $this->project          = $project;
    }

    public static function build(\Tuleap\Tracker\Artifact\Artifact $artifact, \PFUser $user): self
    {
        $assigned_to = [];
        foreach ($artifact->getAssignedTo($user) as $user_assigned_to) {
            $assigned_to[] = MinimalUserRepresentation::build($user_assigned_to);
        }

        $tracker = $artifact->getTracker();

        return new self(
            JsonCast::toInt($artifact->getId()),
            $artifact->getTitle() ?? '',
            $artifact->getStatus(),
            JsonCast::toDate($artifact->getLastUpdateDate()),
            MinimalUserRepresentation::build($artifact->getSubmittedByUser()),
            $assigned_to,
            TrackerReference::build($tracker),
            [
                "uri"       => $artifact->getUri(),
                "color"     => $artifact->getTracker()->getColor()->getName(),
                "cross_ref" => $artifact->getXRef()
            ],
            new ProjectReference($tracker->getProject()),
        );
    }
}
