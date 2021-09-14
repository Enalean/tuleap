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

use Psr\Log\LoggerInterface;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\RetrieveChangesetSubmissionDate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\RetrieveFieldValuesGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirroredTimeboxes;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveTrackerOfArtifact;

final class ProgramIncrementUpdateProcessor implements ProcessProgramIncrementUpdate
{
    public function __construct(
        private LoggerInterface $logger,
        private GatherSynchronizedFields $fields_gatherer,
        private RetrieveFieldValuesGatherer $values_retriever,
        private RetrieveChangesetSubmissionDate $submission_date_retriever,
        private SearchMirroredTimeboxes $mirrored_timeboxes_searcher,
        private RetrieveTrackerOfArtifact $tracker_retriever,
        private MapStatusByValue $status_mapper,
        private AddChangeset $changeset_adder,
        private DeletePendingProgramIncrementUpdates $pending_update_deleter
    ) {
    }

    public function processProgramIncrementUpdate(ProgramIncrementUpdate $update): void
    {
        $program_increment_id = $update->program_increment->getId();
        $user_id              = $update->user->getId();
        $this->logger->debug(
            "Processing program increment update with program increment #$program_increment_id for user #$user_id"
        );

        try {
            $source_values = SourceTimeboxChangesetValues::fromUpdate(
                $this->fields_gatherer,
                $this->values_retriever,
                $this->submission_date_retriever,
                $update
            );
        } catch (FieldSynchronizationException | MirroredTimeboxReplicationException $exception) {
            $this->logger->error('Error during update of program increments', ['exception' => $exception]);
            return;
        }

        $mirrored_program_increments = $this->mirrored_timeboxes_searcher->searchMirroredTimeboxes(
            $program_increment_id
        );
        if (count($mirrored_program_increments) === 0) {
            $this->logger->error("Could not find any mirrors for program increment #$program_increment_id");
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
                    ArtifactLinkValue::buildEmptyValue(),
                    $update->user
                );
                $this->changeset_adder->addChangeset($changeset);
            } catch (FieldSynchronizationException | MirroredTimeboxReplicationException $exception) {
                $this->logger->error('Error during update of program increments', ['exception' => $exception]);
            }
        }
        $this->pending_update_deleter->deletePendingProgramIncrementUpdate($update);
    }
}
