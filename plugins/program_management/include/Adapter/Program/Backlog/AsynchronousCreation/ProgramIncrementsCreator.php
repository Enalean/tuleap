<?php
/**
 * Copyright (c) Enalean 2020 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\AsynchronousCreation;

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkTypeProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ArtifactCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\CreateArtifact;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\CreateProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MapStatusByValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxFirstChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementArtifactCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;

final class ProgramIncrementsCreator implements CreateProgramIncrements
{
    public function __construct(
        private DBTransactionExecutor $transaction_executor,
        private MapStatusByValue $status_mapper,
        private CreateArtifact $artifact_creator,
        private GatherSynchronizedFields $gather_synchronized_fields
    ) {
    }

    /**
     * @throws FieldSynchronizationException
     * @throws ProgramIncrementArtifactCreationException
     */
    public function createProgramIncrements(
        SourceTimeboxChangesetValues $values,
        TrackerCollection $mirrored_timeboxes,
        UserIdentifier $user_identifier
    ): void {
        $artifact_link_value = ArtifactLinkValue::fromArtifactAndType(
            $values->getSourceTimebox(),
            ArtifactLinkTypeProxy::fromMirrorTimeboxType()
        );
        $this->transaction_executor->execute(
            function () use ($values, $artifact_link_value, $mirrored_timeboxes, $user_identifier) {
                foreach ($mirrored_timeboxes->getTrackers() as $mirrored_timebox_tracker) {
                    $this->createOneProgramIncrement(
                        $mirrored_timebox_tracker,
                        $values,
                        $artifact_link_value,
                        $user_identifier
                    );
                }
            }
        );
    }

    /**
     * @throws FieldSynchronizationException
     * @throws ProgramIncrementArtifactCreationException
     */
    private function createOneProgramIncrement(
        TrackerIdentifier $mirrored_timebox_tracker,
        SourceTimeboxChangesetValues $values,
        ArtifactLinkValue $artifact_link_value,
        UserIdentifier $user
    ): void {
        $changeset = MirroredTimeboxFirstChangeset::fromMirroredTimeboxTracker(
            $this->gather_synchronized_fields,
            $this->status_mapper,
            $mirrored_timebox_tracker,
            $values,
            $artifact_link_value,
            $user
        );
        try {
            $this->artifact_creator->create($changeset);
        } catch (ArtifactCreationException $e) {
            throw new ProgramIncrementArtifactCreationException($values->getSourceTimebox()->getId());
        }
    }
}
