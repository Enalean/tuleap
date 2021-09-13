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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ArtifactLinkValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DescriptionValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\EndPeriodValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\MappedStatusValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StartDateValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\TitleValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\ArtifactLinkFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DescriptionFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\EndPeriodFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\NoDuckTypedMatchingValueException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StartDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFieldReferences;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldReference;

/**
 * I hold all the field identifiers and all the corresponding field values necessary to create a new
 * changeset in a Mirrored Timebox.
 * @psalm-immutable
 */
final class MirroredTimeboxChangesetValues
{
    private function __construct(
        private ArtifactLinkFieldReference $artifact_link_field,
        private ArtifactLinkValue $artifact_link_value,
        private TitleFieldReference $title_field,
        private TitleValue $title_value,
        private DescriptionFieldReference $description_field,
        private DescriptionValue $description_value,
        private StatusFieldReference $status_field,
        private MappedStatusValue $mapped_status_value,
        private StartDateFieldReference $start_date_field,
        private StartDateValue $start_date_value,
        private EndPeriodFieldReference $end_period_field,
        private EndPeriodValue $end_period_value,
    ) {
    }

    /**
     * @throws NoDuckTypedMatchingValueException
     */
    public static function fromSourceChangesetValuesAndSynchronizedFields(
        MapStatusByValue $status_mapper,
        SourceTimeboxChangesetValues $field_values,
        ArtifactLinkValue $artifact_link_value,
        SynchronizedFieldReferences $target_fields
    ): self {
        $mapped_status = MappedStatusValue::fromStatusValueAndListField(
            $status_mapper,
            $field_values->getStatusValue(),
            $target_fields->status
        );
        return new self(
            $target_fields->artifact_link,
            $artifact_link_value,
            $target_fields->title,
            $field_values->getTitleValue(),
            $target_fields->description,
            $field_values->getDescriptionValue(),
            $target_fields->status,
            $mapped_status,
            $target_fields->start_date,
            $field_values->getStartDateValue(),
            $target_fields->end_period,
            $field_values->getEndPeriodValue()
        );
    }

    /**
     * @return array<int,string|array>
     */
    public function toFieldsDataArray(): array
    {
        return [
            $this->artifact_link_field->getId() => $this->artifact_link_value->getValues(),
            $this->title_field->getId()         => $this->title_value->getValue(),
            $this->description_field->getId()   => $this->description_value->getValue(),
            $this->status_field->getId()        => $this->mapped_status_value->getValues(),
            $this->start_date_field->getId()    => $this->start_date_value->getValue(),
            $this->end_period_field->getId()    => $this->end_period_value->getValue(),
        ];
    }
}
