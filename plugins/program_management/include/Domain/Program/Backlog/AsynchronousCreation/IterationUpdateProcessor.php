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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\RetrieveChangesetSubmissionDate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\RetrieveFieldValuesGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredIterationIdentifier;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirroredTimeboxes;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\LogMessage;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\RetrieveTrackerOfArtifact;

final class IterationUpdateProcessor implements ProcessIterationUpdate
{
    public function __construct(
        private LogMessage $logger,
        private GatherSynchronizedFields $fields_gatherer,
        private RetrieveFieldValuesGatherer $values_retriever,
        private RetrieveChangesetSubmissionDate $submission_date_retriever,
        private SearchMirroredTimeboxes $mirrored_timeboxes_searcher,
        private VerifyIsVisibleArtifact $artifact_visible_verifier,
        private RetrieveTrackerOfArtifact $tracker_of_artifact_retriever,
        private MapStatusByValue $status_mapper,
        private AddChangeset $changeset_adder,
    ) {
    }

    #[\Override]
    public function processUpdate(IterationUpdate $update): void
    {
        $this->logger->debug(
            sprintf(
                'Processing iteration update of the iteration #%d from user #%d',
                $update->getIteration()->getId(),
                $update->getUser()->getId()
            )
        );

        try {
            $source_values = SourceTimeboxChangesetValues::fromMirroringOrder(
                $this->fields_gatherer,
                $this->values_retriever,
                $this->submission_date_retriever,
                $update
            );
        } catch (MirroredTimeboxReplicationException | FieldSynchronizationException $e) {
            $this->logger->error('Error during update of iterations', ['exception' => $e]);
            return;
        }

        $mirrored_iterations = MirroredIterationIdentifier::buildCollectionFromIteration(
            $this->mirrored_timeboxes_searcher,
            $this->artifact_visible_verifier,
            $update->getIteration(),
            $update->getUser()
        );

        if (count($mirrored_iterations) === 0) {
            $this->logger->error(
                sprintf(
                    'Could not find any mirrors for iteration #%d',
                    $update->getIteration()->getId()
                )
            );
            return;
        }

        foreach ($mirrored_iterations as $mirrored_iteration) {
            try {
                $changeset = MirroredTimeboxChangeset::fromMirroredTimebox(
                    $this->tracker_of_artifact_retriever,
                    $this->fields_gatherer,
                    $this->status_mapper,
                    $mirrored_iteration,
                    $source_values,
                    $update->getUser()
                );
                $this->changeset_adder->addChangeset($changeset);
            } catch (FieldSynchronizationException | NewChangesetCreationException $e) {
                $this->logger->error('Error during update of mirrored iterations', ['exception' => $e]);
            }
        }
    }
}
