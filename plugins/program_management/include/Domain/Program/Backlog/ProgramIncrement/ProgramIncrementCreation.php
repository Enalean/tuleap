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
use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementCreationEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ChangesetIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DomainChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\RetrieveLastChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\TimeboxMirroringOrder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\VerifyIsChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;
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
        private ChangesetIdentifier $old_changeset,
        private UserIdentifier $user,
    ) {
    }

    public static function fromArtifactCreatedEvent(
        VerifyIsProgramIncrementTracker $program_increment_verifier,
        ArtifactCreatedEvent $event,
    ): ?self {
        $program_increment = ProgramIncrementIdentifier::fromArtifactEvent($program_increment_verifier, $event);
        if (! $program_increment) {
            return null;
        }
        $tracker = ProgramIncrementTrackerIdentifier::fromId($program_increment_verifier, $event->getTracker());
        if (! $tracker) {
            return null;
        }
        return new self($program_increment, $tracker, $event->getChangeset(), $event->getOldChangeset(), $event->getUser());
    }

    public static function fromProgramIncrementCreationEvent(
        VerifyIsProgramIncrement $program_increment_verifier,
        VerifyIsVisibleArtifact $visibility_verifier,
        VerifyIsChangeset $changeset_verifier,
        RetrieveProgramIncrementTracker $tracker_retriever,
        ProgramIncrementCreationEvent $event,
    ): ?self {
        $user                 = $event->getUser();
        $program_increment_id = $event->getArtifactId();
        try {
            $program_increment = ProgramIncrementIdentifier::fromId(
                $program_increment_verifier,
                $visibility_verifier,
                $program_increment_id,
                $user
            );
        } catch (ProgramIncrementNotFoundException $e) {
            return null;
        }
        $program_increment_tracker = ProgramIncrementTrackerIdentifier::fromProgramIncrement(
            $tracker_retriever,
            $program_increment
        );
        $changeset_id              = $event->getChangesetId();
        $changeset                 = DomainChangeset::fromId($changeset_verifier, $changeset_id);
        if (! $changeset) {
            return null;
        }
        return new self($program_increment, $program_increment_tracker, $changeset, $changeset, $user);
    }

    public static function fromTeamSynchronization(
        RetrieveProgramIncrementTracker $tracker_retriever,
        RetrieveLastChangeset $retrieve_last_changeset,
        VerifyIsChangeset $verify_is_changeset,
        ProgramIncrementIdentifier $program_increment,
        UserIdentifier $user,
    ): ?self {
        $program_increment_tracker = ProgramIncrementTrackerIdentifier::fromProgramIncrement(
            $tracker_retriever,
            $program_increment
        );

        $changeset_id = $retrieve_last_changeset->retrieveLastChangesetId($program_increment);
        if (! $changeset_id) {
            return null;
        }

        $changeset_identifier = DomainChangeset::fromId(
            $verify_is_changeset,
            $changeset_id
        );

        if (! $changeset_identifier) {
            return null;
        }

        return new self(
            $program_increment,
            $program_increment_tracker,
            $changeset_identifier,
            $changeset_identifier,
            $user
        );
    }

    #[\Override]
    public function getTimebox(): TimeboxIdentifier
    {
        return $this->program_increment;
    }

    public function getProgramIncrement(): ProgramIncrementIdentifier
    {
        return $this->program_increment;
    }

    #[\Override]
    public function getTracker(): TrackerIdentifier
    {
        return $this->tracker;
    }

    public function getProgramIncrementTracker(): ProgramIncrementTrackerIdentifier
    {
        return $this->tracker;
    }

    #[\Override]
    public function getUser(): UserIdentifier
    {
        return $this->user;
    }

    #[\Override]
    public function getChangeset(): ChangesetIdentifier
    {
        return $this->changeset;
    }

    public function getOldChangeset(): ChangesetIdentifier
    {
        return $this->old_changeset;
    }
}
