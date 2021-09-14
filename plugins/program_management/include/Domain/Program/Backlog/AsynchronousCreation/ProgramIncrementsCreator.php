<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ArtifactCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\CreateArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldReferences;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

class ProgramIncrementsCreator
{
    public function __construct(
        private DBTransactionExecutor $transaction_executor,
        private MapStatusByValue $status_mapper,
        private CreateArtifact $artifact_creator,
        private RetrieveUser $retrieve_user,
        private GatherSynchronizedFields $gather_synchronized_fields
    ) {
    }

    /**
     * @throws ProgramIncrementArtifactCreationException
     * @throws FieldRetrievalException
     * @throws FieldSynchronizationException
     */
    public function createProgramIncrements(
        SourceTimeboxChangesetValues $values,
        TrackerCollection $mirrored_timeboxes,
        UserIdentifier $user_identifier
    ): void {
        $current_user        = $this->retrieve_user->getUserWithId($user_identifier);
        $artifact_link_value = ArtifactLinkValue::fromSourceTimeboxValues($values);
        $this->transaction_executor->execute(
            function () use ($values, $artifact_link_value, $mirrored_timeboxes, $current_user) {
                foreach ($mirrored_timeboxes->getTrackers() as $mirrored_timebox_tracker) {
                    $synchronized_fields = SynchronizedFieldReferences::fromTrackerIdentifier(
                        $this->gather_synchronized_fields,
                        $mirrored_timebox_tracker,
                        null
                    );

                    $mirrored_program_increment_changeset = MirroredTimeboxChangesetValues::fromSourceChangesetValuesAndSynchronizedFields(
                        $this->status_mapper,
                        $values,
                        $artifact_link_value,
                        $synchronized_fields
                    );
                    try {
                        $this->artifact_creator->create(
                            $mirrored_timebox_tracker,
                            $mirrored_program_increment_changeset,
                            $current_user,
                            $values->getSubmittedOn(),
                        );
                    } catch (ArtifactCreationException $e) {
                        throw new ProgramIncrementArtifactCreationException($values->getSourceArtifactId());
                    }
                }
            }
        );
    }
}
