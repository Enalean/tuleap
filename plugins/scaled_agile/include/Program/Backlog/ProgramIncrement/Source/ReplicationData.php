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

use Tuleap\ScaledAgile\Project;
use Tuleap\ScaledAgile\ScaledAgileTracker;

/**
 * @psalm-immutable
 */
class ReplicationData
{
    /**
     * @var ScaledAgileTracker
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
     * @var Artifact
     */
    private $artifact;
    /**
     * @var Project
     */
    private $project;

    public function __construct(
        ScaledAgileTracker $tracker,
        \Tracker_Artifact_Changeset $changeset,
        \PFUser $user,
        Artifact $artifact,
        Project $project
    ) {
        $this->tracker   = $tracker;
        $this->changeset = $changeset;
        $this->user      = $user;
        $this->artifact  = $artifact;
        $this->project   = $project;
    }

    public function getTracker(): ScaledAgileTracker
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

    public function getArtifact(): Artifact
    {
        return $this->artifact;
    }

    public function getProject(): Project
    {
        return $this->project;
    }
}
