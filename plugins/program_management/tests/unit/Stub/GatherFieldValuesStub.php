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
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\SynchronizedFields;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\ReplicationData;

final class GatherFieldValuesStub implements GatherFieldValues
{
    private RetrieveTitleValueStub $title_stub;
    private RetrieveDescriptionValueStub $description_stub;
    private RetrieveStartDateValueStub $start_date_stub;
    private RetrieveEndPeriodValueStub $end_period_stub;
    private RetrieveStatusValuesStub $status_stub;

    public function __construct(
        RetrieveTitleValueStub $title_stub,
        RetrieveDescriptionValueStub $description_stub,
        RetrieveStartDateValueStub $start_date_stub,
        RetrieveEndPeriodValueStub $end_period_stub,
        RetrieveStatusValuesStub $status_stub
    ) {
        $this->title_stub       = $title_stub;
        $this->description_stub = $description_stub;
        $this->start_date_stub  = $start_date_stub;
        $this->end_period_stub  = $end_period_stub;
        $this->status_stub      = $status_stub;
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

    public function getTitleValue(ReplicationData $replication, SynchronizedFields $fields): string
    {
        return $this->title_stub->getTitleValue($replication, $fields);
    }

    public function getDescriptionValue(
        ReplicationData $replication,
        SynchronizedFields $fields
    ): TextFieldValue {
        return $this->description_stub->getDescriptionValue($replication, $fields);
    }

    public function getStartDateValue(ReplicationData $replication, SynchronizedFields $fields): string
    {
        return $this->start_date_stub->getStartDateValue($replication, $fields);
    }

    public function getEndPeriodValue(ReplicationData $replication, SynchronizedFields $fields): string
    {
        return $this->end_period_stub->getEndPeriodValue($replication, $fields);
    }

    public function getStatusValues(ReplicationData $replication, SynchronizedFields $fields): array
    {
        return $this->status_stub->getStatusValues($replication, $fields);
    }
}
