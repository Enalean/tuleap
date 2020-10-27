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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data;

use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\SynchronizedFields;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\ArtifactLinkValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\DescriptionValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\EndPeriodValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\MappedStatusValue;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\SourceChangesetValuesCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\StartDateValueData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\TitleValueData;

/**
 * @psalm-immutable
 */
final class ProjectIncrementFieldsData
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
     * @var TitleValueData
     */
    private $title_value_data;
    /**
     * @var int
     */
    private $description_field_id;
    /**
     * @var DescriptionValueData
     */
    private $description_value_data;
    /**
     * @var int
     */
    private $status_field_id;
    /**
     * @var MappedStatusValue
     */
    private $mapped_status_value;
    /**
     * @var StartDateValueData
     */
    private $start_date_value_data;
    /**
     * @var EndPeriodValueData
     */
    private $end_period_value_data;
    /**
     * @var int
     */
    private $start_date_field_id;
    /**
     * @var int
     */
    private $end_period_field_id;
    /**
     * @var ArtifactLinkValueData
     */
    private $artifact_link_value_data;

    private function __construct(
        int $artifact_link_field_id,
        int $title_field_id,
        TitleValueData $title_value_data,
        int $description_field_id,
        DescriptionValueData $description_value_data,
        int $status_field_id,
        MappedStatusValue $mapped_status_value,
        int $start_date_field_id,
        StartDateValueData $start_date_value_data,
        int $end_period_field_id,
        EndPeriodValueData $end_period_value,
        ArtifactLinkValueData $artifact_link_value
    ) {
        $this->artifact_link_field_id   = $artifact_link_field_id;
        $this->title_field_id           = $title_field_id;
        $this->title_value_data         = $title_value_data;
        $this->description_field_id     = $description_field_id;
        $this->description_value_data   = $description_value_data;
        $this->status_field_id          = $status_field_id;
        $this->mapped_status_value      = $mapped_status_value;
        $this->start_date_field_id      = $start_date_field_id;
        $this->start_date_value_data    = $start_date_value_data;
        $this->end_period_field_id      = $end_period_field_id;
        $this->end_period_value_data    = $end_period_value;
        $this->artifact_link_value_data = $artifact_link_value;
    }

    public static function fromSourceChangesetValuesAndSynchronizedFields(
        SourceChangesetValuesCollection $changeset_values_collection,
        MappedStatusValue $mapped_status_value,
        SynchronizedFields $target_fields
    ): self {
        return new self(
            (int) $target_fields->getArtifactLinkField()->getId(),
            (int) $target_fields->getTitleField()->getId(),
            $changeset_values_collection->getTitleValue(),
            (int) $target_fields->getDescriptionField()->getId(),
            $changeset_values_collection->getDescriptionValue(),
            (int) $target_fields->getStatusField()->getId(),
            $mapped_status_value,
            (int) $target_fields->getTimeframeFields()->getStartDateField()->getId(),
            $changeset_values_collection->getStartDateValue(),
            (int) $target_fields->getTimeframeFields()->getEndPeriodField()->getId(),
            $changeset_values_collection->getEndPeriodValue(),
            $changeset_values_collection->getArtifactLinkValue()
        );
    }

    /**
     * @return array<int,string|array>
     */
    public function toFieldsDataArray(): array
    {
        return [
            $this->artifact_link_field_id => $this->artifact_link_value_data->getValues(),
            $this->title_field_id         => $this->title_value_data->getValue(),
            $this->description_field_id   => $this->description_value_data->getValue(),
            $this->status_field_id        => $this->mapped_status_value->getValues(),
            $this->start_date_field_id    => $this->start_date_value_data->getValue(),
            $this->end_period_field_id    => $this->end_period_value_data->getValue(),
        ];
    }
}
