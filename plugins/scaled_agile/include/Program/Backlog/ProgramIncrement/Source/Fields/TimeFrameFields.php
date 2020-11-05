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

namespace Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields;

final class TimeFrameFields
{
    /**
     * @var FieldData
     * @psalm-readonly
     */
    private $start_date;
    /**
     * @var FieldData
     * @psalm-readonly
     */
    private $end_period_field_data;


    private function __construct(
        FieldData $start_date,
        FieldData $end_period_field_data
    ) {
        $this->start_date            = $start_date;
        $this->end_period_field_data = $end_period_field_data;
    }

    public static function fromStartDateAndDuration(
        FieldData $start_date,
        FieldData $duration
    ): self {
        return new self($start_date, $duration);
    }

    public static function fromStartAndEndDates(
        FieldData $start_date,
        FieldData $end_date
    ): self {
        return new self($start_date, $end_date);
    }

    public function getStartDateField(): FieldData
    {
        return $this->start_date;
    }

    public function getEndPeriodField(): FieldData
    {
        return $this->end_period_field_data;
    }
}
