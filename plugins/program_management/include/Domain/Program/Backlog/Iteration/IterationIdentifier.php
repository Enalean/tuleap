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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration;

use Tuleap\ProgramManagement\Domain\Events\ArtifactUpdatedEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I hold the identifier of an Artifact from the Iteration tracker.
 * Program Administrator can choose a Tracker to be the Iteration tracker.
 * Inside a single Program, Iterations are always from the same tracker.
 * Iterations are always in programs. They are always children of Program Increments
 * @see ProgramIncrementIdentifier
 * @psalm-immutable
 */
final class IterationIdentifier implements TimeboxIdentifier
{
    private function __construct(private int $id)
    {
    }

    public static function fromId(
        VerifyIsIteration $iteration_verifier,
        VerifyIsVisibleArtifact $visibility_verifier,
        int $artifact_id,
        UserIdentifier $user,
    ): ?self {
        if (
            ! $iteration_verifier->isIteration($artifact_id)
            || ! $visibility_verifier->isVisible($artifact_id, $user)
        ) {
            return null;
        }
        return new self($artifact_id);
    }

    /**
     * @return self[]
     */
    public static function buildCollectionFromProgramIncrement(
        SearchIterations $iteration_searcher,
        VerifyIsVisibleArtifact $visibility_verifier,
        ProgramIncrementIdentifier $program_increment,
        UserIdentifier $user,
    ): array {
        $iteration_ids = $iteration_searcher->searchIterations($program_increment);
        $visible_ids   = array_filter(
            array_column($iteration_ids, 'id'),
            static fn(int $id): bool => $visibility_verifier->isVisible($id, $user)
        );
        return array_map(
            static fn(int $iteration_id): self => new self($iteration_id),
            array_values($visible_ids)
        );
    }

    public static function fromArtifactUpdateEvent(
        VerifyIsIterationTracker $iteration_tracker_verifier,
        ArtifactUpdatedEvent $event,
    ): ?self {
        if (! $iteration_tracker_verifier->isIterationTracker($event->getTracker()->getId())) {
            return null;
        }
        return new self($event->getArtifact()->getId());
    }

    #[\Override]
    public function getId(): int
    {
        return $this->id;
    }
}
