<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Velocity;

use Tracker;
use Tracker_Artifact;

class VelocityCollection
{
    public const NB_MAX_VELOCITIES = 7;
    /**
     * @var Tracker_Artifact[]
     */
    private $invalid_artifacts = [];
    /**
     * @var VelocityRepresentation[]
     */
    private $velocity_representations = [];

    /**
     * @var Tracker[]
     */
    private $invalid_trackers = [];

    /**
     * @return VelocityRepresentation[]
     */
    public function getVelocityRepresentations()
    {
        ksort($this->velocity_representations);

        return array_slice(
            array_values(
                $this->velocity_representations
            ),
            -self::NB_MAX_VELOCITIES
        );
    }

    public function hasMoreThanMaxLimitDisplayVelocities()
    {
        return count($this->velocity_representations) > self::NB_MAX_VELOCITIES;
    }

    public function addVelocityRepresentation(VelocityRepresentation $velocity_representation)
    {
        $ordering_key =  $velocity_representation->start_date . $velocity_representation->id;
        $this->velocity_representations[$ordering_key] = $velocity_representation;
    }

    /**
     * @return Tracker_Artifact[]
     */
    public function getInvalidArtifacts()
    {
        return $this->invalid_artifacts;
    }

    public function addInvalidArtifact(InvalidArtifactRepresentation $invalid_artifact)
    {
        $this->invalid_artifacts[] = $invalid_artifact;
    }

    public function addInvalidTracker(Tracker $tracker)
    {
        if (! in_array($tracker, $this->invalid_trackers)) {
            $this->invalid_trackers[] = $tracker;
        }
    }

    public function getInvalidTrackersNames(): array
    {
        $tracker_names = [];

        foreach ($this->invalid_trackers as $invalid_tracker) {
            $tracker_names[] = $invalid_tracker->getName();
        }

        return $tracker_names;
    }
}
