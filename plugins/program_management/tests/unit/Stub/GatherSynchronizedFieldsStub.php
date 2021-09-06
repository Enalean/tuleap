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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DescriptionFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\EndPeriodFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\GatherSynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StartDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerIdentifier;

final class GatherSynchronizedFieldsStub implements GatherSynchronizedFields
{
    private function __construct(
        private RetrieveTitleFieldStub $title_stub,
        private RetrieveDescriptionFieldStub $description_stub,
        private RetrieveStatusFieldStub $status_stub,
        private RetrieveStartDateFieldStub $start_date_stub,
        private RetrieveEndPeriodFieldStub $end_period_stub
    ) {
    }

    public static function withFields(
        int $title_field_id,
        string $title_field_label,
        int $description_field_id,
        string $description_field_label,
        int $status_field_id,
        string $status_field_label,
        int $start_date_field_id,
        string $start_date_field_label,
        int $end_period_field_id,
        string $end_period_field_label
    ): self {
        return new self(
            RetrieveTitleFieldStub::withField($title_field_id, $title_field_label),
            RetrieveDescriptionFieldStub::withField($description_field_id, $description_field_label),
            RetrieveStatusFieldStub::withField($status_field_id, $status_field_label),
            RetrieveStartDateFieldStub::withField($start_date_field_id, $start_date_field_label),
            RetrieveEndPeriodFieldStub::withField($end_period_field_id, $end_period_field_label)
        );
    }

    public function getTitleField(ProgramIncrementTrackerIdentifier $program_increment): TitleFieldReference
    {
        return $this->title_stub->getTitleField($program_increment);
    }

    public function getDescriptionField(ProgramIncrementTrackerIdentifier $program_increment): DescriptionFieldReference
    {
        return $this->description_stub->getDescriptionField($program_increment);
    }

    public function getStatusField(ProgramIncrementTrackerIdentifier $program_increment): StatusFieldReference
    {
        return $this->status_stub->getStatusField($program_increment);
    }

    public function getStartDateField(ProgramIncrementTrackerIdentifier $program_increment): StartDateFieldReference
    {
        return $this->start_date_stub->getStartDateField($program_increment);
    }

    public function getEndPeriodField(ProgramIncrementTrackerIdentifier $program_increment): EndPeriodFieldReference
    {
        return $this->end_period_stub->getEndPeriodField($program_increment);
    }
}
