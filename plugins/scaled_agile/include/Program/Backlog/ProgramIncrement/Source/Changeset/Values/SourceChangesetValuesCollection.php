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

namespace Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

/**
 * @psalm-immutable
 */
final class SourceChangesetValuesCollection
{
    /**
     * @var EndPeriodValueData
     */
    private $end_period_value;
    /**
     * @var StartDateValueData
     */
    private $start_date_value;
    /**
     * @var int
     */
    private $source_artifact_id;
    /**
     * @var int
     */
    private $submitted_on;
    /**
     * @var StatusValueData
     */
    private $status_value;
    /**
     * @var DescriptionValueData
     */
    private $description_value;
    /**
     * @var TitleValueData
     */
    private $title_value;
    /**
     * @var ArtifactLinkValueData
     */
    private $artifact_link_value_data;

    public function __construct(
        int $source_artifact_id,
        TitleValueData $title_value,
        DescriptionValueData $description_value,
        StatusValueData $status_value,
        int $submitted_on,
        StartDateValueData $start_date_value,
        EndPeriodValueData $end_period_value,
        ArtifactLinkValueData $artifact_link_value_data
    ) {
        $this->title_value        = $title_value;
        $this->description_value  = $description_value;
        $this->status_value       = $status_value;
        $this->submitted_on       = $submitted_on;
        $this->source_artifact_id = $source_artifact_id;
        $this->start_date_value   = $start_date_value;
        $this->end_period_value   = $end_period_value;
        $this->artifact_link_value_data = $artifact_link_value_data;
    }

    public function getTitleValue(): TitleValueData
    {
        return $this->title_value;
    }

    public function getDescriptionValue(): DescriptionValueData
    {
        return $this->description_value;
    }

    public function getStatusValue(): StatusValueData
    {
        return $this->status_value;
    }

    public function getSubmittedOn(): int
    {
        return $this->submitted_on;
    }

    public function getSourceArtifactId(): int
    {
        return $this->source_artifact_id;
    }

    public function getStartDateValue(): StartDateValueData
    {
        return $this->start_date_value;
    }
    public function getEndPeriodValue(): EndPeriodValueData
    {
        return $this->end_period_value;
    }

    public function getArtifactLinkValue(): ArtifactLinkValueData
    {
        return $this->artifact_link_value_data;
    }
}
