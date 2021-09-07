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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\EndPeriodFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StartDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldReference;

final class FieldValuesGatherer implements GatherFieldValues
{
    public function __construct(
        private \Tracker_Artifact_Changeset $changeset,
        private \Tracker_FormElementFactory $form_element_factory
    ) {
    }

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

    public function getStartDateValue(StartDateFieldReference $start_date): string
    {
        $full_field = $this->form_element_factory->getFieldById($start_date->getId());
        if (! $full_field) {
            throw new FieldNotFoundException($start_date->getId());
        }

        $start_date_value = $this->changeset->getValue($full_field);
        if (! $start_date_value) {
            throw new ChangesetValueNotFoundException(
                (int) $this->changeset->getId(),
                $start_date->getId(),
                'timeframe start date'
            );
        }
        assert($start_date_value instanceof \Tracker_Artifact_ChangesetValue_Date);
        return $start_date_value->getDate();
    }

    public function getEndPeriodValue(EndPeriodFieldReference $end_period): string
    {
        $full_field = $this->form_element_factory->getFieldById($end_period->getId());
        if (! $full_field) {
            throw new FieldNotFoundException($end_period->getId());
        }

        $end_period_value = $this->changeset->getValue($full_field);
        if (! $end_period_value) {
            throw new ChangesetValueNotFoundException(
                (int) $this->changeset->getId(),
                $end_period->getId(),
                'timeframe end period'
            );
        }
        return (string) $end_period_value->getValue();
    }

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
                \Tracker_FormElement_Field_List_BindValue $bind_value
            ): BindValueLabel => BindValueLabelProxy::fromListBindValue($bind_value),
            $status_value->getListValues()
        );
    }
}
