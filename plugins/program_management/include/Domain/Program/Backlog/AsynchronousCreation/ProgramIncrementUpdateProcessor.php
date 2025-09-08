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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\RetrieveChangesetSubmissionDate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\RetrieveFieldValuesGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\MirroredProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirroredTimeboxes;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\LogMessage;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\RetrieveTrackerOfArtifact;

final class ProgramIncrementUpdateProcessor implements ProcessProgramIncrementUpdate
{
    public function __construct(
        private LogMessage $logger,
        private GatherSynchronizedFields $fields_gatherer,
        private RetrieveFieldValuesGatherer $values_retriever,
        private RetrieveChangesetSubmissionDate $submission_date_retriever,
        private SearchMirroredTimeboxes $mirrored_timeboxes_searcher,
        private VerifyIsVisibleArtifact $visibility_verifier,
        private RetrieveTrackerOfArtifact $tracker_retriever,
        private MapStatusByValue $status_mapper,
        private AddChangeset $changeset_adder,
    ) {
    }

    #[\Override]
    public function processUpdate(ProgramIncrementUpdate $update): void
    {
        $this->logger->debug(
            sprintf(
                'Processing program increment update with program increment #%d for user #%d',
                $update->getProgramIncrement()->getId(),
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
        } catch (FieldSynchronizationException | MirroredTimeboxReplicationException $exception) {
            $this->logger->error('Error during update of program increments', ['exception' => $exception]);
            return;
        }

        $mirrored_program_increments = MirroredProgramIncrementIdentifier::buildCollectionFromProgramIncrement(
            $this->mirrored_timeboxes_searcher,
            $this->visibility_verifier,
            $update->getProgramIncrement(),
            $update->getUser()
        );
        if (count($mirrored_program_increments) === 0) {
            $this->logger->error(
                sprintf(
                    'Could not find any mirrors for program increment #%d',
                    $update->getProgramIncrement()->getId()
                )
            );
            return;
        }

        foreach ($mirrored_program_increments as $mirrored_program_increment) {
            try {
                $changeset = MirroredTimeboxChangeset::fromMirroredTimebox(
                    $this->tracker_retriever,
                    $this->fields_gatherer,
                    $this->status_mapper,
                    $mirrored_program_increment,
                    $source_values,
                    $update->getUser()
                );
                $this->changeset_adder->addChangeset($changeset);
            } catch (FieldSynchronizationException | MirroredTimeboxReplicationException $exception) {
                $this->logger->error('Error during update of program increments', ['exception' => $exception]);
            }
        }
    }
}
