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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\DurationValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\EndDateValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\MappedStatusValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\SourceTimeboxChangesetValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StartDateValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\TitleValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\ArtifactLinkFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DescriptionFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DurationFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\EndDateFieldReference;
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
        public ArtifactLinkFieldReference $artifact_link_field,
        public ?ArtifactLinkValue $artifact_link_value,
        public TitleFieldReference $title_field,
        public TitleValue $title_value,
        public DescriptionFieldReference $description_field,
        public DescriptionValue $description_value,
        public StatusFieldReference $status_field,
        public MappedStatusValue $mapped_status_value,
        public StartDateFieldReference $start_date_field,
        public StartDateValue $start_date_value,
        public DurationFieldReference|EndDateFieldReference $end_period_field,
        public DurationValue|EndDateValue $end_period_value,
    ) {
    }

    /**
     * @throws NoDuckTypedMatchingValueException
     */
    public static function fromSourceChangesetValuesAndSynchronizedFields(
        MapStatusByValue $status_mapper,
        SourceTimeboxChangesetValues $field_values,
        SynchronizedFieldReferences $target_fields,
        ?ArtifactLinkValue $artifact_link_value,
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
}
