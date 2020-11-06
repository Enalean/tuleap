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

namespace Tuleap\ScaledAgile\Adapter\Program;

use Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation\ProgramIncrementCreationException;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceChangesetValuesCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\ReplicationData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\SubmissionDate;

class SourceChangesetValuesCollectionAdapter
{
    /**
     * @var SynchronizedFieldsAdapter
     */
    private $fields_gatherer;
    /**
     * @var TitleValueAdapter
     */
    private $title_value_adapter;
    /**
     * @var DescriptionValueAdapter
     */
    private $description_value_adapter;
    /**
     * @var StatusValueAdapter
     */
    private $status_value_adapter;
    /**
     * @var StartDateValueAdapter
     */
    private $start_date_value_adapter;
    /**
     * @var EndPeriodValueAdapter
     */
    private $end_period_value_adapter;
    /**
     * @var ArtifactLinkValueAdapter
     */
    private $artifact_link_value_adapter;

    public function __construct(
        SynchronizedFieldsAdapter $fields_gatherer,
        TitleValueAdapter $title_value_adapter,
        DescriptionValueAdapter $description_value_adapter,
        StatusValueAdapter $status_value_adapter,
        StartDateValueAdapter $start_date_value_adapter,
        EndPeriodValueAdapter $end_period_value_adapter,
        ArtifactLinkValueAdapter $artifact_link_value_adapter
    ) {
        $this->fields_gatherer             = $fields_gatherer;
        $this->title_value_adapter         = $title_value_adapter;
        $this->description_value_adapter   = $description_value_adapter;
        $this->status_value_adapter        = $status_value_adapter;
        $this->start_date_value_adapter    = $start_date_value_adapter;
        $this->end_period_value_adapter    = $end_period_value_adapter;
        $this->artifact_link_value_adapter = $artifact_link_value_adapter;
    }

    /**
     * @throws ProgramIncrementCreationException
     * @throws FieldRetrievalException
     */
    public function buildCollection(ReplicationData $replication_data): SourceChangesetValuesCollection
    {
        $fields              = $this->fields_gatherer->build($replication_data->getTrackerData());
        $title_value         = $this->title_value_adapter->build($fields->getTitleField(), $replication_data);
        $description_value   = $this->description_value_adapter->build($fields->getDescriptionField(), $replication_data);
        $status_value        = $this->status_value_adapter->build($fields->getStatusField(), $replication_data);
        $start_date_value    = $this->start_date_value_adapter->build($fields->getStartDateField(), $replication_data);
        $end_period_value    = $this->end_period_value_adapter->build($fields->getEndPeriodField(), $replication_data);
        $artifact_link_value = $this->artifact_link_value_adapter->build($replication_data);

        return new SourceChangesetValuesCollection(
            $replication_data->getArtifactData()->getId(),
            $title_value,
            $description_value,
            $status_value,
            new SubmissionDate($replication_data->getArtifactData()->getSubmittedOn()),
            $start_date_value,
            $end_period_value,
            $artifact_link_value
        );
    }
}
