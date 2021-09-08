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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldReferences;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\SubmissionDate;
use Tuleap\ProgramManagement\Domain\TrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveTracker;

/**
 * I hold all field values for a given changeset for a source Timebox
 * @psalm-immutable
 */
final class SourceTimeboxChangesetValues
{
    private function __construct(
        private int $source_artifact_id,
        private TitleValue $title_value,
        private DescriptionValue $description_value,
        private StatusValue $status_value,
        private SubmissionDate $submitted_on,
        private StartDateValue $start_date_value,
        private EndPeriodValue $end_period_value
    ) {
    }

    /**
     * @throws FieldSynchronizationException
     * @throws ChangesetValueNotFoundException
     * @throws UnsupportedTitleFieldException
     */
    public static function fromReplication(
        GatherSynchronizedFields $fields_gatherer,
        RetrieveFieldValuesGatherer $field_values_retriever,
        RetrieveTracker $tracker_retriever,
        ReplicationData $replication
    ): self {
        $tracker = $tracker_retriever->getTrackerById($replication->getTracker()->getId());
        if (! $tracker) {
            throw new TrackerNotFoundException($replication->getTracker()->getId());
        }
        $fields            = SynchronizedFieldReferences::fromTrackerIdentifier($fields_gatherer, $replication->getTracker());
        $values_gatherer   = $field_values_retriever->getFieldValuesGatherer($replication);
        $title_value       = TitleValue::fromSynchronizedFields($values_gatherer, $fields);
        $description_value = DescriptionValue::fromSynchronizedFields($values_gatherer, $fields);
        $status_value      = StatusValue::fromSynchronizedFields($values_gatherer, $fields);
        $start_date_value  = StartDateValue::fromSynchronizedFields($values_gatherer, $fields);
        $end_period_value  = EndPeriodValue::fromSynchronizedFields($values_gatherer, $fields);

        return new self(
            $replication->getArtifact()->getId(),
            $title_value,
            $description_value,
            $status_value,
            new SubmissionDate($replication->getArtifact()->getSubmittedOn()),
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
