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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Fields\FieldNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BindValueLabel;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValueNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\GatherFieldValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\TextFieldValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\UnsupportedTitleFieldException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;

final class ArtifactFieldValuesRetriever implements GatherFieldValues
{
    public function __construct(private \Tracker_Artifact_Changeset $changeset, private \Tracker_FormElementFactory $form_element_factory)
    {
    }

    public function getTitleValue(SynchronizedFields $fields): string
    {
        $title_field = $fields->getTitleField();
        $full_field  = $this->form_element_factory->getFieldById($title_field->getId());
        if (! $full_field) {
            throw new FieldNotFoundException($title_field->getId());
        }

        $title_value = $this->changeset->getValue($full_field);
        if (! $title_value) {
            throw new ChangesetValueNotFoundException(
                (int) $this->changeset->getId(),
                $title_field->getId(),
                'title'
            );
        }
        if (! ($title_value instanceof \Tracker_Artifact_ChangesetValue_String)) {
            throw new UnsupportedTitleFieldException($title_field->getId());
        }
        return $title_value->getValue();
    }

    public function getDescriptionValue(SynchronizedFields $fields): TextFieldValue
    {
        $description_field = $fields->getDescriptionField();
        $full_field        = $this->form_element_factory->getFieldById($description_field->getId());
        if (! $full_field) {
            throw new FieldNotFoundException($description_field->getId());
        }

        $description_value = $this->changeset->getValue($full_field);
        if (! $description_value) {
            throw new ChangesetValueNotFoundException(
                (int) $this->changeset->getId(),
                $description_field->getId(),
                'description'
            );
        }
        assert($description_value instanceof \Tracker_Artifact_ChangesetValue_Text);
        return new TextFieldValueProxy($description_value->getValue(), $description_value->getFormat());
    }

    public function getStartDateValue(SynchronizedFields $fields): string
    {
        $start_date_field = $fields->getStartDateField();
        $full_field       = $this->form_element_factory->getFieldById($start_date_field->getId());
        if (! $full_field) {
            throw new FieldNotFoundException($start_date_field->getId());
        }

        $start_date_value = $this->changeset->getValue($full_field);
        if (! $start_date_value) {
            throw new ChangesetValueNotFoundException(
                (int) $this->changeset->getId(),
                $start_date_field->getId(),
                'timeframe start date'
            );
        }
        assert($start_date_value instanceof \Tracker_Artifact_ChangesetValue_Date);
        return $start_date_value->getDate();
    }

    public function getEndPeriodValue(SynchronizedFields $fields): string
    {
        $end_period_field = $fields->getEndPeriodField();
        $full_field       = $this->form_element_factory->getFieldById($end_period_field->getId());
        if (! $full_field) {
            throw new FieldNotFoundException($end_period_field->getId());
        }

        $end_period_value = $this->changeset->getValue($full_field);
        if (! $end_period_value) {
            throw new ChangesetValueNotFoundException(
                (int) $this->changeset->getId(),
                $end_period_field->getId(),
                'time frame end period'
            );
        }
        return (string) $end_period_value->getValue();
    }

    public function getStatusValues(SynchronizedFields $fields): array
    {
        $status_field = $fields->getStatusField();
        $full_field   = $this->form_element_factory->getFieldById($status_field->getId());
        if (! $full_field) {
            throw new FieldNotFoundException($status_field->getId());
        }

        $status_value = $this->changeset->getValue($full_field);
        if (! $status_value) {
            throw new ChangesetValueNotFoundException(
                (int) $this->changeset->getId(),
                $status_field->getId(),
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
