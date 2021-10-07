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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\GatherFieldValues;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\TextFieldValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DescriptionFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\EndPeriodFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StartDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldReference;

final class GatherFieldValuesStub implements GatherFieldValues
{
    private function __construct(
        private RetrieveTitleValueStub $title_stub,
        private RetrieveDescriptionValueStub $description_stub,
        private RetrieveStartDateValueStub $start_date_stub,
        private RetrieveEndPeriodValueStub $end_period_stub,
        private RetrieveStatusValuesStub $status_stub
    ) {
    }

    public static function withError(): self
    {
        return new self(
            RetrieveTitleValueStub::withError(),
            RetrieveDescriptionValueStub::withValue('enfasten', 'text'),
            RetrieveStartDateValueStub::withValue('2024-05-24'),
            RetrieveEndPeriodValueStub::withValue('2031-01-15'),
            RetrieveStatusValuesStub::withValues('Planned')
        );
    }

    /**
     * @param string[] $status_values
     */
    public static function withValues(
        string $title,
        string $description_value,
        string $description_format,
        string $start_date,
        string $end_period,
        array $status_values
    ): self {
        return new self(
            RetrieveTitleValueStub::withValue($title),
            RetrieveDescriptionValueStub::withValue($description_value, $description_format),
            RetrieveStartDateValueStub::withValue($start_date),
            RetrieveEndPeriodValueStub::withValue($end_period),
            RetrieveStatusValuesStub::withValues(...$status_values)
        );
    }

    public function getTitleValue(TitleFieldReference $title): string
    {
        return $this->title_stub->getTitleValue($title);
    }

    public function getDescriptionValue(DescriptionFieldReference $description): TextFieldValue
    {
        return $this->description_stub->getDescriptionValue($description);
    }

    public function getStartDateValue(StartDateFieldReference $start_date): string
    {
        return $this->start_date_stub->getStartDateValue($start_date);
    }

    public function getEndPeriodValue(EndPeriodFieldReference $end_period): string
    {
        return $this->end_period_stub->getEndPeriodValue($end_period);
    }

    public function getStatusValues(StatusFieldReference $status): array
    {
        return $this->status_stub->getStatusValues($status);
    }
}
