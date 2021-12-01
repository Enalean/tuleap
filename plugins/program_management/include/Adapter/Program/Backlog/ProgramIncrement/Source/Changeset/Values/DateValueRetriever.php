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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\ChangesetValueNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\EndDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\FieldNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StartDateFieldReference;

final class DateValueRetriever
{
    public function __construct(private \Tracker_FormElementFactory $form_element_factory)
    {
    }

    /**
     * @throws ChangesetValueNotFoundException
     */
    public function getDateFieldTimestamp(
        \Tracker_Artifact_Changeset $changeset,
        StartDateFieldReference|EndDateFieldReference $date_field,
    ): int {
        $date_field_id = $date_field->getId();
        $full_field    = $this->form_element_factory->getFieldById($date_field_id);
        if (! $full_field) {
            throw new FieldNotFoundException($date_field_id);
        }

        $date_value = $changeset->getValue($full_field);
        if (! $date_value) {
            throw new ChangesetValueNotFoundException(
                (int) $changeset->getId(),
                $date_field_id,
                $this->getFieldType($date_field)
            );
        }
        assert($date_value instanceof \Tracker_Artifact_ChangesetValue_Date);
        $timestamp = $date_value->getTimestamp();
        if ($timestamp === null) {
            throw new ChangesetValueNotFoundException(
                (int) $changeset->getId(),
                $date_field_id,
                $this->getFieldType($date_field)
            );
        }
        return $timestamp;
    }

    private function getFieldType(StartDateFieldReference|EndDateFieldReference $date_field): string
    {
        return $date_field instanceof StartDateFieldReference ? 'timeframe start date' : 'timeframe end date';
    }
}
