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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MapStatusByValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\EndPeriodValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\MappedStatusValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceChangesetValuesCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StartDateValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\TitleValue;

/**
 * I hold all the field identifiers and all the corresponding field values necessary to create a new
 * changeset in a Mirrored Program Increment.
 * @psalm-immutable
 */
final class MirroredProgramIncrementChangeset
{
    private int $artifact_link_field_id;
    private int $title_field_id;
    private TitleValue $title_value;
    private int $description_field_id;
    private DescriptionValue $description_value;
    private int $status_field_id;
    private MappedStatusValue $mapped_status_value;
    private int $start_date_field_id;
    private StartDateValue $start_date_value;
    private int $end_period_field_id;
    private EndPeriodValue $end_period_value;
    private ArtifactLinkValue $artifact_link_value;

    private function __construct(
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
        MapStatusByValue $status_mapper,
        SourceChangesetValuesCollection $field_values,
        SynchronizedFields $target_fields
    ): self {
        $mapped_status = MappedStatusValue::fromStatusValueAndListField(
            $status_mapper,
            $field_values->getStatusValue(),
            $target_fields->getStatusField()
        );
        return new self(
            $target_fields->getArtifactLinkField()->getId(),
            $field_values->getArtifactLinkValue(),
            $target_fields->getTitleField()->getId(),
            $field_values->getTitleValue(),
            $target_fields->getDescriptionField()->getId(),
            $field_values->getDescriptionValue(),
            $target_fields->getStatusField()->getId(),
            $mapped_status,
            $target_fields->getStartDateField()->getId(),
            $field_values->getStartDateValue(),
            $target_fields->getEndPeriodField()->getId(),
            $field_values->getEndPeriodValue()
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
