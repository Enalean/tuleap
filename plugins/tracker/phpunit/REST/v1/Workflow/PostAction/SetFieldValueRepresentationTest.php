<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\REST\v1\Workflow\PostAction;

use PHPUnit\Framework\TestCase;
use Transition_PostAction_Field_Date;

class SetFieldValueRepresentationTest extends TestCase
{
    public function testForDate()
    {
        $representation = SetFieldValueRepresentation::forDate(
            "1",
            43,
            Transition_PostAction_Field_Date::CLEAR_DATE
        );
        $this->assertEquals("1", $representation->id);
        $this->assertEquals("date", $representation->field_type);
        $this->assertEquals(43, $representation->field_id);
        $this->assertEquals(SetFieldValueRepresentation::EMPTY_DATE_VALUE, $representation->value);
    }

    public function testForDateWithCurrentDate()
    {
        $representation = SetFieldValueRepresentation::forDate(
            "1",
            43,
            Transition_PostAction_Field_Date::FILL_CURRENT_TIME
        );
        $this->assertEquals(SetFieldValueRepresentation::CURRENT_DATE_VALUE, $representation->value);
    }

    public function testForDateWithoutValue()
    {
        $representation = SetFieldValueRepresentation::forDate("1", 43, 0);
        $this->assertEquals(SetFieldValueRepresentation::UNSET_DATE_VALUE, $representation->value);
    }

    public function testForDateThrowsWhenUnsupportedValue()
    {
        $this->expectException(UnsupportedDateValueException::class);
        SetFieldValueRepresentation::forDate("1", 43, 99);
    }
}
