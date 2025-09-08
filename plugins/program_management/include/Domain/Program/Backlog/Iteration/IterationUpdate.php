<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration;

use Tuleap\ProgramManagement\Domain\Events\ArtifactUpdatedEvent;
use Tuleap\ProgramManagement\Domain\Events\IterationUpdateEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ChangesetIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\TimeboxMirroringOrder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I hold all the information necessary to update Mirrored Iterations from a source Iteration.
 * @psalm-immutable
 */
final class IterationUpdate implements TimeboxMirroringOrder
{
    private function __construct(
        private IterationIdentifier $iteration,
        private IterationTrackerIdentifier $tracker,
        private ChangesetIdentifier $changeset,
        private UserIdentifier $user,
    ) {
    }

    public static function fromArtifactUpdateEvent(
        VerifyIsIterationTracker $iteration_tracker_verifier,
        ArtifactUpdatedEvent $event,
    ): ?self {
        $iteration = IterationIdentifier::fromArtifactUpdateEvent($iteration_tracker_verifier, $event);
        if (! $iteration) {
            return null;
        }

        $iteration_tracker = IterationTrackerIdentifier::fromTrackerIdentifier(
            $iteration_tracker_verifier,
            $event->getTracker()
        );
        if (! $iteration_tracker) {
            return null;
        }

        return new self($iteration, $iteration_tracker, $event->getChangeset(), $event->getUser());
    }

    public static function fromIterationUpdateEvent(
        RetrieveIterationTracker $iteration_tracker_retriever,
        IterationUpdateEvent $event,
    ): self {
        $iteration_tracker = IterationTrackerIdentifier::fromIteration(
            $iteration_tracker_retriever,
            $event->getIteration()
        );
        return new self($event->getIteration(), $iteration_tracker, $event->getChangeset(), $event->getUser());
    }

    #[\Override]
    public function getTimebox(): TimeboxIdentifier
    {
        return $this->iteration;
    }

    public function getIteration(): IterationIdentifier
    {
        return $this->iteration;
    }

    #[\Override]
    public function getTracker(): TrackerIdentifier
    {
        return $this->tracker;
    }

    #[\Override]
    public function getChangeset(): ChangesetIdentifier
    {
        return $this->changeset;
    }

    #[\Override]
    public function getUser(): UserIdentifier
    {
        return $this->user;
    }
}
