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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DurationFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\EndDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StartDateFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReference;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldReference;

final class GatherFieldValuesStub implements GatherFieldValues
{
    private function __construct(
        private RetrieveTitleValueStub $title_stub,
        private RetrieveDescriptionValueStub $description_stub,
        private RetrieveStartDateValueStub $start_date_stub,
        private ?RetrieveEndDateValueStub $end_date_stub,
        private ?RetrieveDurationValueStub $duration_stub,
        private RetrieveStatusValuesStub $status_stub,
    ) {
    }

    public static function withError(): self
    {
        return new self(
            RetrieveTitleValueStub::withError(),
            RetrieveDescriptionValueStub::withValue('enfasten', 'text'),
            RetrieveStartDateValueStub::withValue(1675447200),
            RetrieveEndDateValueStub::withValue(1939931281),
            null,
            RetrieveStatusValuesStub::withValues('Planned')
        );
    }

    /**
     * @param string[] $status_values
     * @param int      $start_date UNIX Timestamp
     * @param int      $end_date   UNIX Timestamp
     */
    public static function withValues(
        string $title,
        string $description_value,
        string $description_format,
        int $start_date,
        int $end_date,
        array $status_values,
    ): self {
        return new self(
            RetrieveTitleValueStub::withValue($title),
            RetrieveDescriptionValueStub::withValue($description_value, $description_format),
            RetrieveStartDateValueStub::withValue($start_date),
            RetrieveEndDateValueStub::withValue($end_date),
            null,
            RetrieveStatusValuesStub::withValues(...$status_values)
        );
    }

    /**
     * @param string[] $status_values
     * @param int      $start_date UNIX Timestamp
     * @param int      $duration   Number of days
     */
    public static function withDuration(
        string $title,
        string $description_value,
        string $description_format,
        int $start_date,
        int $duration,
        array $status_values,
    ): self {
        return new self(
            RetrieveTitleValueStub::withValue($title),
            RetrieveDescriptionValueStub::withValue($description_value, $description_format),
            RetrieveStartDateValueStub::withValue($start_date),
            null,
            RetrieveDurationValueStub::withValue($duration),
            RetrieveStatusValuesStub::withValues(...$status_values)
        );
    }

    #[\Override]
    public function getTitleValue(TitleFieldReference $title): string
    {
        return $this->title_stub->getTitleValue($title);
    }

    #[\Override]
    public function getDescriptionValue(DescriptionFieldReference $description): TextFieldValue
    {
        return $this->description_stub->getDescriptionValue($description);
    }

    #[\Override]
    public function getStartDateValue(StartDateFieldReference $start_date): int
    {
        return $this->start_date_stub->getStartDateValue($start_date);
    }

    #[\Override]
    public function getDurationValue(DurationFieldReference $duration): int
    {
        if (! $this->duration_stub) {
            throw new \LogicException('No Duration stub configured');
        }
        return $this->duration_stub->getDurationValue($duration);
    }

    #[\Override]
    public function getEndDateValue(EndDateFieldReference $end_date): int
    {
        if (! $this->end_date_stub) {
            throw new \LogicException('No End date stub configured');
        }
        return $this->end_date_stub->getEndDateValue($end_date);
    }

    #[\Override]
    public function getStatusValues(StatusFieldReference $status): array
    {
        return $this->status_stub->getStatusValues($status);
    }
}
