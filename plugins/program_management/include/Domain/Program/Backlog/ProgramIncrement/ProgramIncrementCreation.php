<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Events\ArtifactCreatedEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ChangesetIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\TimeboxMirroringOrder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\TrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I hold all the information necessary to create new Mirrored Program Increments from
 * a source Program Increment.
 * @psalm-immutable
 */
final class ProgramIncrementCreation implements TimeboxMirroringOrder
{
    private function __construct(
        private ProgramIncrementIdentifier $program_increment,
        private ProgramIncrementTrackerIdentifier $tracker,
        private ChangesetIdentifier $changeset,
        private UserIdentifier $user
    ) {
    }

    public static function fromArtifactCreatedEvent(
        VerifyIsProgramIncrementTracker $program_increment_verifier,
        ArtifactCreatedEvent $event
    ): ?self {
        $program_increment = ProgramIncrementIdentifier::fromArtifactEvent($program_increment_verifier, $event);
        if (! $program_increment) {
            return null;
        }
        $tracker = ProgramIncrementTrackerIdentifier::fromId($program_increment_verifier, $event->getTracker());
        if (! $tracker) {
            return null;
        }
        return new self($program_increment, $tracker, $event->getChangeset(), $event->getUser());
    }

    public function getTimebox(): TimeboxIdentifier
    {
        return $this->program_increment;
    }

    public function getProgramIncrement(): ProgramIncrementIdentifier
    {
        return $this->program_increment;
    }

    public function getTracker(): TrackerIdentifier
    {
        return $this->tracker;
    }

    public function getProgramIncrementTracker(): ProgramIncrementTrackerIdentifier
    {
        return $this->tracker;
    }

    public function getUser(): UserIdentifier
    {
        return $this->user;
    }

    public function getChangeset(): ChangesetIdentifier
    {
        return $this->changeset;
    }
}
