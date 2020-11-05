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

namespace Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Data\SynchronizedFields;

use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\TimeFrameFields;

final class TimeFrameFieldsTest extends \PHPUnit\Framework\TestCase
{
    public function testItCanBeAStartDateAndDuration(): void
    {
        $start_date_field = new \Tracker_FormElement_Field_Date(12, 67, 10, 'start_date', 'Start Date', 'Irrelevant', true, 'P', false, '', 1);
        $duration_field   = new \Tracker_FormElement_Field_Integer(13, 67, 10, 'duration', 'Duration (in days)', 'Irrelevant', true, 'P', false, '', 2);

        $fields = TimeFrameFields::fromStartDateAndDuration(new FieldData($start_date_field), new FieldData($duration_field));

        self::assertSame($start_date_field, $fields->getStartDateField()->getFullField());
        self::assertInstanceOf(\Tracker_FormElement_Field_Integer::class, $fields->getEndPeriodField()->getFullField());
        self::assertSame($duration_field, $fields->getEndPeriodField()->getFullField());
    }

    public function testItCanBeAStartDateAndEndDate(): void
    {
        $start_date_field = new \Tracker_FormElement_Field_Date(12, 67, 10, 'start_date', 'Start Date', 'Irrelevant', true, 'P', false, '', 1);
        $end_date_field   = new \Tracker_FormElement_Field_Date(13, 67, 10, 'end_date', 'End Date', 'Irrelevant', true, 'P', false, '', 2);

        $fields = TimeFrameFields::fromStartAndEndDates(new FieldData($start_date_field), new FieldData($end_date_field));

        self::assertSame($start_date_field, $fields->getStartDateField()->getFullField());
        self::assertInstanceOf(\Tracker_FormElement_Field_Date::class, $fields->getEndPeriodField()->getFullField());
        self::assertSame($end_date_field, $fields->getEndPeriodField()->getFullField());
    }
}
