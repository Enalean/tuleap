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

use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\SubmissionDate;

/**
 * @psalm-immutable
 */
final class SourceChangesetValuesCollection
{
    /**
     * @var EndPeriodValue
     */
    private $end_period_value;
    /**
     * @var StartDateValue
     */
    private $start_date_value;
    /**
     * @var int
     */
    private $source_artifact_id;
    /**
     * @var SubmissionDate
     */
    private $submitted_on;
    /**
     * @var StatusValue
     */
    private $status_value;
    /**
     * @var DescriptionValue
     */
    private $description_value;
    /**
     * @var TitleValue
     */
    private $title_value;
    /**
     * @var ArtifactLinkValue
     */
    private $artifact_link_value_data;

    public function __construct(
        int $source_artifact_id,
        TitleValue $title_value,
        DescriptionValue $description_value,
        StatusValue $status_value,
        SubmissionDate $submitted_on,
        StartDateValue $start_date_value,
        EndPeriodValue $end_period_value,
        ArtifactLinkValue $artifact_link_value_data
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

    public function getArtifactLinkValue(): ArtifactLinkValue
    {
        return $this->artifact_link_value_data;
    }
}
