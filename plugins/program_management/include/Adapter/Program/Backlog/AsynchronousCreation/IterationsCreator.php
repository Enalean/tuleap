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

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkTypeProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ArtifactCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\CreateArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\CreateIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\IterationCreation;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MapStatusByValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredIterationCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxFirstChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredIterationTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\RetrieveMirroredIterationTracker;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\TeamHasNoMirroredIterationTrackerException;

final class IterationsCreator implements CreateIterations
{
    public function __construct(
        private DBTransactionExecutor $transaction_executor,
        private RetrieveMirroredIterationTracker $milestone_retriever,
        private MapStatusByValue $status_mapper,
        private GatherSynchronizedFields $fields_gatherer,
        private CreateArtifact $artifact_creator
    ) {
    }

    /**
     * @throws TeamHasNoMirroredIterationTrackerException
     * @throws FieldSynchronizationException
     * @throws MirroredIterationCreationException
     */
    public function createIterations(
        SourceTimeboxChangesetValues $values,
        TeamProjectsCollection $teams,
        IterationCreation $creation
    ): void {
        $artifact_link_value = ArtifactLinkValue::fromArtifactAndType(
            $values->getSourceTimebox(),
            ArtifactLinkTypeProxy::fromMirrorTimeboxType()
        );
        $this->transaction_executor->execute(
            function () use ($values, $artifact_link_value, $teams, $creation) {
                foreach ($teams->getTeamProjects() as $team) {
                    $this->createOneIteration($team, $values, $artifact_link_value, $creation);
                }
            }
        );
    }

    /**
     * @throws TeamHasNoMirroredIterationTrackerException
     * @throws FieldSynchronizationException
     * @throws MirroredIterationCreationException
     */
    private function createOneIteration(
        ProjectReference $team,
        SourceTimeboxChangesetValues $values,
        ArtifactLinkValue $artifact_link_value,
        IterationCreation $creation
    ): void {
        $mirrored_iteration_tracker = MirroredIterationTrackerIdentifier::fromTeam(
            $this->milestone_retriever,
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
            $this->artifact_creator->create($changeset);
        } catch (ArtifactCreationException $e) {
            throw new MirroredIterationCreationException($creation->getIteration(), $e);
        }
    }
}
