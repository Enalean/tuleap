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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MirroredTimeboxReplicationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\RetrieveChangesetSubmissionDate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\SubmissionDate;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldReferences;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ReplicationData;

/**
 * I hold all field values for a given changeset for a source Timebox
 * @psalm-immutable
 */
final class SourceTimeboxChangesetValues
{
    private function __construct(
        private int $source_artifact_id,
        private SubmissionDate $submitted_on,
        private TitleValue $title_value,
        private DescriptionValue $description_value,
        private StatusValue $status_value,
        private StartDateValue $start_date_value,
        private EndPeriodValue $end_period_value
    ) {
    }

    /**
     * @throws FieldSynchronizationException
     * @throws MirroredTimeboxReplicationException
     */
    public static function fromReplication(
        GatherSynchronizedFields $fields_gatherer,
        RetrieveFieldValuesGatherer $field_values_retriever,
        RetrieveChangesetSubmissionDate $submission_retriever,
        ReplicationData $replication
    ): self {
        $program_increment_id = $replication->getArtifact()->getId();
        $fields               = SynchronizedFieldReferences::fromTrackerIdentifier(
            $fields_gatherer,
            $replication->getTracker(),
            null
        );
        $submission_date      = $submission_retriever->getSubmissionDate(
            $program_increment_id,
            $replication->getChangeset()
        );

        $values_gatherer   = $field_values_retriever->getFieldValuesGatherer($replication);
        $title_value       = TitleValue::fromTitleReference($values_gatherer, $fields->title);
        $description_value = DescriptionValue::fromDescriptionReference($values_gatherer, $fields->description);
        $status_value      = StatusValue::fromStatusReference($values_gatherer, $fields->status);
        $start_date_value  = StartDateValue::fromStartDateReference($values_gatherer, $fields->start_date);
        $end_period_value  = EndPeriodValue::fromEndPeriodReference($values_gatherer, $fields->end_period);

        return new self(
            $program_increment_id,
            $submission_date,
            $title_value,
            $description_value,
            $status_value,
            $start_date_value,
            $end_period_value
        );
    }

    /**
     * @throws FieldSynchronizationException
     * @throws MirroredTimeboxReplicationException
     */
    public static function fromUpdate(
        GatherSynchronizedFields $fields_gatherer,
        RetrieveFieldValuesGatherer $field_values_retriever,
        RetrieveChangesetSubmissionDate $submission_retriever,
        ProgramIncrementUpdate $update
    ): self {
        $program_increment_id = $update->program_increment->getId();
        $fields               = SynchronizedFieldReferences::fromTrackerIdentifier($fields_gatherer, $update->tracker, null);
        $submission_date      = $submission_retriever->getSubmissionDate($program_increment_id, $update->changeset);

        $values_gatherer   = $field_values_retriever->getGathererFromUpdate($update);
        $title_value       = TitleValue::fromTitleReference($values_gatherer, $fields->title);
        $description_value = DescriptionValue::fromDescriptionReference($values_gatherer, $fields->description);
        $status_value      = StatusValue::fromStatusReference($values_gatherer, $fields->status);
        $start_date_value  = StartDateValue::fromStartDateReference($values_gatherer, $fields->start_date);
        $end_period_value  = EndPeriodValue::fromEndPeriodReference($values_gatherer, $fields->end_period);

        return new self(
            $program_increment_id,
            $submission_date,
            $title_value,
            $description_value,
            $status_value,
            $start_date_value,
            $end_period_value
        );
    }

    public function getTitleValue(): TitleValue
    {
        return $this->title_value;
    }

    public function getDescriptionValue(): DescriptionValue
    {
        return $this->description_value;
    }

    public function getStatusValue(): StatusValue
    {
        return $this->status_value;
    }

    public function getSubmittedOn(): SubmissionDate
    {
        return $this->submitted_on;
    }

    public function getSourceArtifactId(): int
    {
        return $this->source_artifact_id;
    }

    public function getStartDateValue(): StartDateValue
    {
        return $this->start_date_value;
    }

    public function getEndPeriodValue(): EndPeriodValue
    {
        return $this->end_period_value;
    }
}
