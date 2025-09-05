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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Events\PendingIterationCreation;
use Tuleap\ProgramManagement\Domain\Events\ProgramIncrementUpdateEvent;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\JustLinkedIterationCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\SearchIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\VerifyIsIteration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\RetrieveProgramOfProgramIncrement;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\LogMessage;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

/**
 * I hold all the information necessary to create Mirrored Iterations from a source Iteration.
 * @psalm-immutable
 */
final class IterationCreation implements TimeboxMirroringOrder
{
    private function __construct(
        private IterationIdentifier $iteration,
        private IterationTrackerIdentifier $tracker,
        private ProgramIncrementIdentifier $program_increment,
        private UserIdentifier $user,
        private ChangesetIdentifier $changeset,
    ) {
    }

    /**
     * @return self[]
     */
    public static function buildCollectionFromJustLinkedIterations(
        RetrieveLastChangeset $changeset_retriever,
        RetrieveIterationTracker $tracker_retriever,
        LogMessage $logger,
        JustLinkedIterationCollection $iterations,
        UserIdentifier $user,
    ): array {
        if (count($iterations->ids) === 0) {
            return [];
        }
        // IterationIdentifier always come from the same tracker, so we retrieve it only once
        $tracker   = IterationTrackerIdentifier::fromIteration($tracker_retriever, $iterations->ids[0]);
        $creations = [];
        foreach ($iterations->ids as $iteration_identifier) {
            $last_changeset_id = DomainChangeset::fromIterationLastChangeset(
                $changeset_retriever,
                $iteration_identifier
            );
            if ($last_changeset_id === null) {
                $logger->error(
                    sprintf(
                        'Could not retrieve last changeset of iteration #%s, skipping it',
                        $iteration_identifier->getId()
                    ),
                );
                continue;
            }
            $creations[] = new self(
                $iteration_identifier,
                $tracker,
                $iterations->program_increment,
                $user,
                $last_changeset_id
            );
        }
        return $creations;
    }

    /**
     * @return IterationCreation[]
     */
    public static function buildCollectionFromProgramIncrementUpdateEvent(
        RetrieveIterationTracker $tracker_retriever,
        ProgramIncrementUpdateEvent $event,
    ): array {
        $pending_iterations = $event->getIterations();
        if (count($pending_iterations) === 0) {
            return [];
        }
        // IterationIdentifier always come from the same tracker, so we retrieve it only once
        $tracker = IterationTrackerIdentifier::fromIteration(
            $tracker_retriever,
            $pending_iterations[0]->getIteration()
        );
        return array_map(
            static fn(PendingIterationCreation $pending_iteration) => new self(
                $pending_iteration->getIteration(),
                $tracker,
                $event->getProgramIncrement(),
                $event->getUser(),
                $pending_iteration->getChangeset()
            ),
            $pending_iterations
        );
    }

    /**
     * @return self[]
     */
    public static function fromProgramIncrementCreation(
        RetrieveProgramOfProgramIncrement $program_retriever,
        BuildProgram $program_builder,
        RetrieveIterationTracker $iteration_tracker_retriever,
        VerifyIsIteration $verify_is_iteration,
        VerifyIsVisibleArtifact $verify_is_visible_artifact,
        SearchIterations $search_iterations,
        RetrieveLastChangeset $retrieve_last_changeset,
        ProgramIncrementCreation $event,
    ): array {
        $program_increment_identifier = $event->getProgramIncrement();
        $user_identifier              = $event->getUser();
        $iteration_tracker            = IterationTrackerIdentifier::fromProgramIncrement(
            $program_retriever,
            $program_builder,
            $iteration_tracker_retriever,
            $program_increment_identifier,
            $user_identifier
        );

        if (! $iteration_tracker) {
            return [];
        }

        $iterations_to_update = [];
        $iterations_to_create = $search_iterations->searchIterations($program_increment_identifier);

        foreach ($iterations_to_create as $iteration) {
            $iteration_identifier = IterationIdentifier::fromId(
                $verify_is_iteration,
                $verify_is_visible_artifact,
                $iteration['id'],
                $user_identifier,
            );

            if (! $iteration_identifier) {
                continue;
            }

            $iteration_changeset_identifier = DomainChangeset::fromIterationLastChangeset($retrieve_last_changeset, $iteration_identifier);
            if (! $iteration_changeset_identifier) {
                continue;
            }

            $iterations_to_update[] = new self(
                $iteration_identifier,
                $iteration_tracker,
                $program_increment_identifier,
                $user_identifier,
                $iteration_changeset_identifier
            );
        }
        return $iterations_to_update;
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

    public function getProgramIncrement(): ProgramIncrementIdentifier
    {
        return $this->program_increment;
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
}
