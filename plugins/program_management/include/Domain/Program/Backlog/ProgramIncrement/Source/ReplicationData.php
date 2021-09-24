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

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ChangesetIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramManagementProject;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I hold all the information necessary to create Mirrored Program Increments from a source Program Increment
 * @psalm-immutable
 */
final class ReplicationData
{
    public function __construct(
        private ProgramIncrementTrackerIdentifier $tracker,
        private ChangesetIdentifier $changeset,
        private TimeboxIdentifier $timebox,
        private ProgramManagementProject $project,
        private UserIdentifier $user_identifier
    ) {
    }

    public function getTracker(): ProgramIncrementTrackerIdentifier
    {
        return $this->tracker;
    }

    public function getChangeset(): ChangesetIdentifier
    {
        return $this->changeset;
    }

    public function getTimebox(): TimeboxIdentifier
    {
        return $this->timebox;
    }

    public function getProject(): ProgramManagementProject
    {
        return $this->project;
    }

    public function getUserIdentifier(): UserIdentifier
    {
        return $this->user_identifier;
    }
}
