<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source;

use Tuleap\ScaledAgile\ProjectData;
use Tuleap\ScaledAgile\TrackerData;

/**
 * @psalm-immutable
 */
class ReplicationData
{
    /**
     * @var TrackerData
     */
    private $tracker;
    /**
     * @var \Tracker_Artifact_Changeset
     */
    private $changeset;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var ArtifactData
     */
    private $artifact_data;
    /**
     * @var ProjectData
     */
    private $project_data;

    public function __construct(
        TrackerData $tracker,
        \Tracker_Artifact_Changeset $changeset,
        \PFUser $user,
        ArtifactData $artifact_data,
        ProjectData $project_data
    ) {
        $this->tracker       = $tracker;
        $this->changeset     = $changeset;
        $this->user          = $user;
        $this->artifact_data = $artifact_data;
        $this->project_data  = $project_data;
    }

    public function getTrackerData(): TrackerData
    {
        return $this->tracker;
    }

    public function getFullChangeset(): \Tracker_Artifact_Changeset
    {
        return $this->changeset;
    }

    public function getUser(): \PFUser
    {
        return $this->user;
    }

    public function getArtifactData(): ArtifactData
    {
        return $this->artifact_data;
    }

    public function getProjectData(): ProjectData
    {
        return $this->project_data;
    }
}
