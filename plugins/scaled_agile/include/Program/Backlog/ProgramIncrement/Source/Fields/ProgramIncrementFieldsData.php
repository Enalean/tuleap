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

namespace Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields;

use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\EndPeriodValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\MappedStatusValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceChangesetValuesCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StartDateValue;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Changeset\Values\TitleValue;

/**
 * @psalm-immutable
 */
final class ProgramIncrementFieldsData
{
    /**
     * @var int
     */
    private $artifact_link_field_id;
    /**
     * @var int
     */
    private $title_field_id;
    /**
     * @var TitleValue
     */
    private $title_value;
    /**
     * @var int
     */
    private $description_field_id;
    /**
     * @var DescriptionValue
     */
    private $description_value;
    /**
     * @var int
     */
    private $status_field_id;
    /**
     * @var MappedStatusValue
     */
    private $mapped_status_value;
    /**
     * @var StartDateValue
     */
    private $start_date_value;
    /**
     * @var EndPeriodValue
     */
    private $end_period_value;
    /**
     * @var int
     */
    private $start_date_field_id;
    /**
     * @var int
     */
    private $end_period_field_id;
    /**
     * @var ArtifactLinkValue
     */
    private $artifact_link_value;

    public function __construct(
        int $artifact_link_field_id,
        ArtifactLinkValue $artifact_link_value,
        int $title_field_id,
        TitleValue $title_value,
        int $description_field_id,
        DescriptionValue $description_value,
        int $status_field_id,
        MappedStatusValue $mapped_status_value,
        int $start_date_field_id,
        StartDateValue $start_date_value,
        int $end_period_field_id,
        EndPeriodValue $end_period_value
    ) {
        $this->artifact_link_field_id = $artifact_link_field_id;
        $this->artifact_link_value    = $artifact_link_value;
        $this->title_field_id         = $title_field_id;
        $this->title_value            = $title_value;
        $this->description_field_id   = $description_field_id;
        $this->description_value      = $description_value;
        $this->status_field_id        = $status_field_id;
        $this->mapped_status_value    = $mapped_status_value;
        $this->start_date_field_id    = $start_date_field_id;
        $this->start_date_value       = $start_date_value;
        $this->end_period_field_id    = $end_period_field_id;
        $this->end_period_value       = $end_period_value;
    }

    public static function fromSourceChangesetValuesAndSynchronizedFields(
        SourceChangesetValuesCollection $changeset_values_collection,
        MappedStatusValue $mapped_status_value,
        SynchronizedFields $target_fields
    ): self {
        return new self(
            $target_fields->getArtifactLinkField()->getId(),
            $changeset_values_collection->getArtifactLinkValue(),
            $target_fields->getTitleField()->getId(),
            $changeset_values_collection->getTitleValue(),
            $target_fields->getDescriptionField()->getId(),
            $changeset_values_collection->getDescriptionValue(),
            $target_fields->getStatusField()->getId(),
            $mapped_status_value,
            $target_fields->getStartDateField()->getId(),
            $changeset_values_collection->getStartDateValue(),
            $target_fields->getEndPeriodField()->getId(),
            $changeset_values_collection->getEndPeriodValue()
        );
    }

    /**
     * @return array<int,string|array>
     */
    public function toFieldsDataArray(): array
    {
        return [
            $this->artifact_link_field_id => $this->artifact_link_value->getValues(),
            $this->title_field_id         => $this->title_value->getValue(),
            $this->description_field_id   => $this->description_value->getValue(),
            $this->status_field_id        => $this->mapped_status_value->getValues(),
            $this->start_date_field_id    => $this->start_date_value->getValue(),
            $this->end_period_field_id    => $this->end_period_value->getValue(),
        ];
    }
}
