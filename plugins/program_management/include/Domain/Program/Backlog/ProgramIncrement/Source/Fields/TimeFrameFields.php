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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields;

use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Numeric;

final class TimeFrameFields
{
    /**
     * @psalm-var Field<Tracker_FormElement_Field_Date>
     * @psalm-readonly
     */
    private $start_date;
    /**
     * @psalm-var Field<Tracker_FormElement_Field_Date>|Field<Tracker_FormElement_Field_Numeric>
     * @psalm-readonly
     */
    private $end_period_field_data;

    /**
     * @psalm-param Field<Tracker_FormElement_Field_Date> $start_date
     * @psalm-param Field<Tracker_FormElement_Field_Date>|Field<Tracker_FormElement_Field_Numeric> $end_period_field_data
     */
    private function __construct(
        Field $start_date,
        Field $end_period_field_data
    ) {
        $this->start_date            = $start_date;
        $this->end_period_field_data = $end_period_field_data;
    }

    /**
     * @psalm-param Field<Tracker_FormElement_Field_Date> $start_date
     * @psalm-param Field<Tracker_FormElement_Field_Numeric> $duration
     */
    public static function fromStartDateAndDuration(
        Field $start_date,
        Field $duration
    ): self {
        return new self($start_date, $duration);
    }

    /**
     * @psalm-param Field<Tracker_FormElement_Field_Date> $start_date
     * @psalm-param Field<Tracker_FormElement_Field_Date> $end_date
     */
    public static function fromStartAndEndDates(
        Field $start_date,
        Field $end_date
    ): self {
        return new self($start_date, $end_date);
    }

    /**
     * @psalm-return Field<Tracker_FormElement_Field_Date>
     */
    public function getStartDateField(): Field
    {
        return $this->start_date;
    }

    /**
     * @psalm-return Field<Tracker_FormElement_Field_Date>|Field<Tracker_FormElement_Field_Numeric>
     */
    public function getEndPeriodField(): Field
    {
        return $this->end_period_field_data;
    }
}
