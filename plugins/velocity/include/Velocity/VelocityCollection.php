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

use Tracker_Artifact;

class VelocityCollection
{
    /**
     * @var Tracker_Artifact[]
     */
    private $invalid_artifacts = [];
    /**
     * @var VelocityRepresentation[]
     */
    private $velocity_representations = [];

    /**
     * @return VelocityRepresentation[]
     */
    public function getVelocityRepresentations()
    {
        return $this->velocity_representations;
    }

    public function addVelocityRepresentation(VelocityRepresentation $velocity_representation)
    {
        $this->velocity_representations[] = $velocity_representation;
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
}
