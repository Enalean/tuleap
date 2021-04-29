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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source;

use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Project;

/**
 * @psalm-immutable
 */
class ReplicationData
{
    /**
     * @var ProgramTracker
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
        ProgramTracker $tracker,
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

    public function getTracker(): ProgramTracker
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
