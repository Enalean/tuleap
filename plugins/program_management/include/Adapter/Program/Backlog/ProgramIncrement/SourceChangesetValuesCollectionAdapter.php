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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BuildArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BuildDescriptionValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BuildEndPeriodValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BuildFieldValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BuildStartDateValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BuildStatusValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BuildTitleValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceChangesetValuesCollection;
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
     * @var BuildTitleValue
     */
    private $build_title_value;
    /**
     * @var BuildDescriptionValue
     */
    private $build_description_value;
    /**
     * @var BuildStatusValue
     */
    private $build_status_value;
    /**
     * @var BuildStartDateValue
     */
    private $build_start_date_value;
    /**
     * @var BuildEndPeriodValue
     */
    private $build_end_period_value;
    /**
     * @var BuildArtifactLinkValue
     */
    private $build_artifact_link_value;

    public function __construct(
        BuildSynchronizedFields $fields_gatherer,
        BuildTitleValue $build_title_value,
        BuildDescriptionValue $build_description_value,
        BuildStatusValue $build_status_value,
        BuildStartDateValue $build_start_date_value,
        BuildEndPeriodValue $build_end_period_value,
        BuildArtifactLinkValue $build_artifact_link_value
    ) {
        $this->fields_gatherer           = $fields_gatherer;
        $this->build_title_value         = $build_title_value;
        $this->build_description_value   = $build_description_value;
        $this->build_status_value        = $build_status_value;
        $this->build_start_date_value    = $build_start_date_value;
        $this->build_end_period_value    = $build_end_period_value;
        $this->build_artifact_link_value = $build_artifact_link_value;
    }

    /**
     * @throws ProgramIncrementCreationException
     * @throws FieldRetrievalException
     */
    public function buildCollection(ReplicationData $replication_data): SourceChangesetValuesCollection
    {
        $fields              = $this->fields_gatherer->build($replication_data->getTracker());
        $title_value         = $this->build_title_value->build($fields->getTitleField(), $replication_data);
        $description_value   = $this->build_description_value->build($fields->getDescriptionField(), $replication_data);
        $status_value        = $this->build_status_value->build($fields->getStatusField(), $replication_data);
        $start_date_value    = $this->build_start_date_value->build($fields->getStartDateField(), $replication_data);
        $end_period_value    = $this->build_end_period_value->build($fields->getEndPeriodField(), $replication_data);
        $artifact_link_value = $this->build_artifact_link_value->build($replication_data);

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
