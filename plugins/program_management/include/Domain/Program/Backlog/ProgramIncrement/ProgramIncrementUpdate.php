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

use Tuleap\ProgramManagement\Domain\Events\ArtifactUpdatedEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ChangesetIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\DomainChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\PendingProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StoredChangesetNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StoredProgramIncrementNoLongerValidException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\StoredUserNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\TimeboxMirroringOrder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\VerifyIsChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\VerifyIsProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\DomainUser;
use Tuleap\ProgramManagement\Domain\Workspace\TrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyIsUser;

/**
 * I hold all the information necessary to apply updates from a source Program Increment
 * to its Mirrored Program Increments.
 * @psalm-immutable
 */
final class ProgramIncrementUpdate implements TimeboxMirroringOrder
{
    private function __construct(
        private ProgramIncrementIdentifier $program_increment,
        private ProgramIncrementTrackerIdentifier $tracker,
        private ChangesetIdentifier $changeset,
        private UserIdentifier $user
    ) {
    }

    public static function fromArtifactUpdatedEvent(
        VerifyIsProgramIncrementTracker $program_increment_verifier,
        ArtifactUpdatedEvent $event
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

    /**
     * @throws StoredProgramIncrementNoLongerValidException
     * @throws StoredUserNotFoundException
     * @throws StoredChangesetNotFoundException
     */
    public static function fromPendingUpdate(
        VerifyIsUser $user_verifier,
        VerifyIsProgramIncrement $program_increment_verifier,
        VerifyIsVisibleArtifact $visibility_verifier,
        VerifyIsChangeset $changeset_verifier,
        RetrieveProgramIncrementTracker $program_increment_tracker_retriever,
        PendingProgramIncrementUpdate $pending_update
    ): self {
        $user_id = $pending_update->getUserId();
        $user    = DomainUser::fromId($user_verifier, $user_id);
        if (! $user) {
            throw new StoredUserNotFoundException($user_id);
        }
        $program_increment_id = $pending_update->getProgramIncrementId();
        try {
            $program_increment = ProgramIncrementIdentifier::fromId(
                $program_increment_verifier,
                $visibility_verifier,
                $program_increment_id,
                $user
            );
        } catch (ProgramIncrementNotFoundException $e) {
            throw new StoredProgramIncrementNoLongerValidException($program_increment_id);
        }
        $program_increment_tracker = ProgramIncrementTrackerIdentifier::fromProgramIncrement(
            $program_increment_tracker_retriever,
            $program_increment
        );
        $changeset_id              = $pending_update->getChangesetId();
        $changeset                 = DomainChangeset::fromId($changeset_verifier, $changeset_id);
        if (! $changeset) {
            throw new StoredChangesetNotFoundException($changeset_id);
        }
        return new self($program_increment, $program_increment_tracker, $changeset, $user);
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

    public function getChangeset(): ChangesetIdentifier
    {
        return $this->changeset;
    }

    public function getUser(): UserIdentifier
    {
        return $this->user;
    }
}
