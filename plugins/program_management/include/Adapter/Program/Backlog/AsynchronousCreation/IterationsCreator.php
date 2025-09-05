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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Psr\Log\LoggerInterface;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkTypeProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\AddArtifactLinkChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\AddArtifactLinkChangesetException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ArtifactCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\CreateArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\CreateIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MapStatusByValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredIterationCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredProgramIncrementNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxFirstChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\RetrieveProjectReference;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\ArtifactLinkChangeset;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredIterationTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredIterationTracker;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredProgramIncrementFromTeam;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\TeamHasNoMirroredIterationTrackerException;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifier;
use Tuleap\ProgramManagement\Domain\Team\TeamIdentifierCollection;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\RetrieveTrackerOfArtifact;

final class IterationsCreator implements CreateIterations
{
    public function __construct(
        private LoggerInterface $logger,
        private DBTransactionExecutor $transaction_executor,
        private RetrieveMirroredIterationTracker $milestone_retriever,
        private MapStatusByValue $status_mapper,
        private GatherSynchronizedFields $fields_gatherer,
        private CreateArtifact $artifact_creator,
        private RetrieveMirroredProgramIncrementFromTeam $mirrored_program_increment_retriever,
        private VerifyIsVisibleArtifact $visibility_verifier,
        private RetrieveTrackerOfArtifact $tracker_retriever,
        private AddArtifactLinkChangeset $link_adder,
        private RetrieveProjectReference $project_retriever,
    ) {
    }

    /**
     * @throws AddArtifactLinkChangesetException
     * @throws FieldSynchronizationException
     * @throws MirroredIterationCreationException
     * @throws MirroredProgramIncrementNotFoundException
     * @throws TeamHasNoMirroredIterationTrackerException
     */
    #[\Override]
    public function createIterations(
        SourceTimeboxChangesetValues $values,
        TeamIdentifierCollection $teams,
        IterationCreation $creation,
    ): void {
        $artifact_link_value = ArtifactLinkValue::fromArtifactAndType(
            $values->getSourceTimebox(),
            ArtifactLinkTypeProxy::fromMirrorTimeboxType()
        );
        $this->transaction_executor->execute(
            function () use ($values, $artifact_link_value, $teams, $creation) {
                foreach ($teams->getTeams() as $team) {
                    $this->createOneIteration($team, $values, $artifact_link_value, $creation);
                }
            }
        );
    }

    /**
     * @throws AddArtifactLinkChangesetException
     * @throws FieldSynchronizationException
     * @throws MirroredIterationCreationException
     * @throws MirroredProgramIncrementNotFoundException
     * @throws TeamHasNoMirroredIterationTrackerException
     */
    private function createOneIteration(
        TeamIdentifier $team,
        SourceTimeboxChangesetValues $values,
        ArtifactLinkValue $artifact_link_value,
        IterationCreation $creation,
    ): void {
        $this->logger->debug(sprintf('%s createOneIteration for #%d', self::class, $team->getId()));
        $mirrored_iteration_tracker = MirroredIterationTrackerIdentifier::fromTeam(
            $this->milestone_retriever,
            $this->project_retriever,
            $team,
            $creation->getUser()
        );
        if (! $mirrored_iteration_tracker) {
            throw new TeamHasNoMirroredIterationTrackerException($team);
        }
        $changeset = MirroredTimeboxFirstChangeset::fromMirroredTimeboxTracker(
            $this->fields_gatherer,
            $this->status_mapper,
            $mirrored_iteration_tracker,
            $values,
            $artifact_link_value,
            $creation->getUser()
        );
        try {
            $mirrored_iteration = $this->artifact_creator->create($changeset);
        } catch (ArtifactCreationException $e) {
            throw new MirroredIterationCreationException($creation->getIteration(), $e);
        }
        $mirrored_program_increment = MirroredProgramIncrementIdentifier::fromProgramIncrementAndTeam(
            $this->mirrored_program_increment_retriever,
            $this->visibility_verifier,
            $creation->getProgramIncrement(),
            $team,
            $creation->getUser()
        );
        if (! $mirrored_program_increment) {
            throw new MirroredProgramIncrementNotFoundException($creation->getProgramIncrement(), $team);
        }
        $program_increment_link_value = ArtifactLinkValue::fromArtifactAndType(
            $mirrored_iteration,
            ArtifactLinkTypeProxy::fromIsChildType()
        );
        $program_increment_changeset  = ArtifactLinkChangeset::fromMirroredProgramIncrement(
            $this->tracker_retriever,
            $this->fields_gatherer,
            $mirrored_program_increment,
            $creation->getUser(),
            $program_increment_link_value
        );
        $this->link_adder->addArtifactLinkChangeset($program_increment_changeset);
    }
}
