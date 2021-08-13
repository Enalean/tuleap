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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\ProgramIncrementCreationException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BuildFieldValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BuildStatusValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\EndPeriodValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\GatherFieldValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceChangesetValuesCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StartDateValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\TitleValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\BuildSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\SubmissionDate;

final class SourceChangesetValuesCollectionAdapter implements BuildFieldValues
{
    /**
     * @var BuildSynchronizedFields
     */
    private $fields_gatherer;
    /**
     * @var BuildStatusValue
     */
    private $build_status_value;
    private GatherFieldValues $field_values_gatherer;

    public function __construct(
        BuildSynchronizedFields $fields_gatherer,
        BuildStatusValue $build_status_value,
        GatherFieldValues $field_values_gatherer
    ) {
        $this->fields_gatherer       = $fields_gatherer;
        $this->build_status_value    = $build_status_value;
        $this->field_values_gatherer = $field_values_gatherer;
    }

    /**
     * @throws ProgramIncrementCreationException
     * @throws FieldRetrievalException
     */
    public function buildCollection(ReplicationData $replication_data): SourceChangesetValuesCollection
    {
        $fields              = $this->fields_gatherer->build($replication_data->getTracker());
        $title_value         = TitleValue::fromReplicationDataAndSynchronizedFields(
            $this->field_values_gatherer,
            $replication_data,
            $fields
        );
        $description_value   = DescriptionValue::fromReplicationDataAndSynchronizedFields(
            $this->field_values_gatherer,
            $replication_data,
            $fields
        );
        $status_value        = $this->build_status_value->build($fields->getStatusField(), $replication_data);
        $start_date_value    = StartDateValue::fromReplicationAndSynchronizedFields(
            $this->field_values_gatherer,
            $replication_data,
            $fields
        );
        $end_period_value    = EndPeriodValue::fromReplicationAndSynchronizedFields(
            $this->field_values_gatherer,
            $replication_data,
            $fields
        );
        $artifact_link_value = ArtifactLinkValue::fromReplicationData($replication_data);

        return new SourceChangesetValuesCollection(
            $replication_data->getArtifact()->getId(),
            $title_value,
            $description_value,
            $status_value,
            new SubmissionDate($replication_data->getArtifact()->getSubmittedOn()),
            $start_date_value,
            $end_period_value,
            $artifact_link_value
        );
    }
}
