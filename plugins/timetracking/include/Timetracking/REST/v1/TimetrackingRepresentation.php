<?php
/**
 * Copyright Enalean (c) 2018-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Timetracking\REST\v1;

use Tracker_ArtifactFactory;
use Tuleap\Project\REST\MinimalProjectRepresentation;
use Tuleap\REST\JsonCast;
use Tuleap\Timetracking\Time\Time;

/**
 * @psalm-immutable
 */
final class TimetrackingRepresentation
{
    public const string NAME = 'timetracking';

    /**
     * @var MinimalArtifactRepresentation
     */
    public $artifact;

    /**
     * @var MinimalProjectRepresentation
     */
    public $project;

    /**
     * @var string
     */
    public $date;

    /**
     * @var int
     */
    public $minutes;

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $step;

    private function __construct(
        int $id,
        int $minutes,
        string $date,
        string $step,
        MinimalArtifactRepresentation $artifact,
        MinimalProjectRepresentation $project,
    ) {
        $this->id       = $id;
        $this->minutes  = $minutes;
        $this->date     = $date;
        $this->step     = $step;
        $this->artifact = $artifact;
        $this->project  = $project;
    }

    public static function fromTime(Time $time): self
    {
        $artifact_id = $time->getArtifactId();
        $artifact    = Tracker_ArtifactFactory::instance()->getArtifactById($artifact_id);
        if ($artifact === null) {
            throw new \RuntimeException(sprintf('Cannot find artifact #%d of time #%d', $artifact_id, $time->getId()));
        }

        return new self(
            JsonCast::toInt($time->getId()),
            JsonCast::toInt($time->getMinutes()),
            $time->getDay(),
            $time->getStep(),
            MinimalArtifactRepresentation::build($artifact),
            new MinimalProjectRepresentation($artifact->getTracker()->getProject()),
        );
    }
}
