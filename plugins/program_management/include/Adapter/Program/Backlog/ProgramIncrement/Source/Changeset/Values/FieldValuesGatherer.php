<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BindValueLabel;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValueNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\GatherFieldValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\TextFieldValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\UnsupportedTitleFieldException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DescriptionFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DurationFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\EndDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StartDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldReference;

final class FieldValuesGatherer implements GatherFieldValues
{
    public function __construct(
        private \Tracker_Artifact_Changeset $changeset,
        private \Tracker_FormElementFactory $form_element_factory,
        private DateValueRetriever $date_value_retriever,
    ) {
    }

    #[\Override]
    public function getTitleValue(TitleFieldReference $title): string
    {
        $full_field = $this->form_element_factory->getFieldById($title->getId());
        if (! $full_field) {
            throw new FieldNotFoundException($title->getId());
        }

        $title_value = $this->changeset->getValue($full_field);
        if (! $title_value) {
            throw new ChangesetValueNotFoundException(
                (int) $this->changeset->getId(),
                $title->getId(),
                'title'
            );
        }
        if (! ($title_value instanceof \Tracker_Artifact_ChangesetValue_String)) {
            throw new UnsupportedTitleFieldException($title->getId());
        }
        return $title_value->getValue();
    }

    #[\Override]
    public function getDescriptionValue(DescriptionFieldReference $description): TextFieldValue
    {
        $full_field = $this->form_element_factory->getFieldById($description->getId());
        if (! $full_field) {
            throw new FieldNotFoundException($description->getId());
        }

        $description_value = $this->changeset->getValue($full_field);
        if (! $description_value) {
            throw new ChangesetValueNotFoundException(
                (int) $this->changeset->getId(),
                $description->getId(),
                'description'
            );
        }
        assert($description_value instanceof \Tracker_Artifact_ChangesetValue_Text);
        return TextFieldValueProxy::fromChangesetValue($description_value);
    }

    #[\Override]
    public function getStartDateValue(StartDateFieldReference $start_date): int
    {
        return $this->date_value_retriever->getDateFieldTimestamp($this->changeset, $start_date);
    }

    #[\Override]
    public function getEndDateValue(EndDateFieldReference $end_date): int
    {
        return $this->date_value_retriever->getDateFieldTimestamp($this->changeset, $end_date);
    }

    #[\Override]
    public function getDurationValue(DurationFieldReference $duration): int
    {
        $full_field = $this->form_element_factory->getFieldById($duration->getId());
        if (! $full_field) {
            throw new FieldNotFoundException($duration->getId());
        }

        $duration_value = $this->changeset->getValue($full_field);
        if (! $duration_value) {
            throw new ChangesetValueNotFoundException((int) $this->changeset->getId(), $duration->getId(), 'timeframe duration');
        }
        return $duration_value->getValue();
    }

    #[\Override]
    public function getStatusValues(StatusFieldReference $status): array
    {
        $full_field = $this->form_element_factory->getFieldById($status->getId());
        if (! $full_field) {
            throw new FieldNotFoundException($status->getId());
        }

        $status_value = $this->changeset->getValue($full_field);
        if (! $status_value) {
            throw new ChangesetValueNotFoundException(
                (int) $this->changeset->getId(),
                $status->getId(),
                'status'
            );
        }
        assert($status_value instanceof \Tracker_Artifact_ChangesetValue_List);

        return array_map(
            static fn(
                \Tracker_FormElement_Field_List_BindValue $bind_value,
            ): BindValueLabel => BindValueLabelProxy::fromListBindValue($bind_value),
            $status_value->getListValues()
        );
    }
}
