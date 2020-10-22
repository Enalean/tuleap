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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields;

final class TimeframeFields
{
    /**
     * @var \Tracker_FormElement_Field_Date
     * @psalm-readonly
     */
    private $start_date;
    /**
     * @var \Tracker_FormElement_Field_Numeric | \Tracker_FormElement_Field_Date
     * @psalm-readonly
     */
    private $other_field;

    /**
     * @param \Tracker_FormElement_Field_Numeric | \Tracker_FormElement_Field_Date $other_field
     */
    private function __construct(
        \Tracker_FormElement_Field_Date $start_date,
        $other_field
    ) {
        $this->start_date  = $start_date;
        $this->other_field = $other_field;
    }

    public static function fromStartDateAndDuration(
        \Tracker_FormElement_Field_Date $start_date,
        \Tracker_FormElement_Field_Numeric $duration
    ): self {
        return new self($start_date, $duration);
    }

    public static function fromStartAndEndDates(
        \Tracker_FormElement_Field_Date $start_date,
        \Tracker_FormElement_Field_Date $end_date
    ): self {
        return new self($start_date, $end_date);
    }

    /**
     * @psalm-assert-if-true \Tracker_FormElement_Field_Numeric $this->other_field
     * @psalm-assert-if-false \Tracker_FormElement_Field_Date $this->other_field
     */
    public function isDurationConfiguration(): bool
    {
        return ($this->other_field instanceof \Tracker_FormElement_Field_Numeric);
    }

    /**
     * @psalm-mutation-free
     */
    public function getStartDateField(): \Tracker_FormElement_Field_Date
    {
        return $this->start_date;
    }

    /**
     * @return \Tracker_FormElement_Field_Date | \Tracker_FormElement_Field_Numeric
     * @psalm-mutation-free
     */
    public function getEndPeriodField()
    {
        return $this->other_field;
    }
}
