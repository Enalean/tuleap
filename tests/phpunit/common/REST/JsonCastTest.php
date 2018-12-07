<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\REST;

use PHPUnit\Framework\TestCase;

class JsonCastTest extends TestCase
{
    public function testToArrayOfInts()
    {
        $this->assertEquals([1, 2, 3], JsonCast::toArrayOfInts(['1', '2', '3']));
    }

    public function testToArrayOfIntsReturnsNullWhenNullGiven()
    {
        $this->assertEquals(null, JsonCast::toArrayOfInts(null));
    }

    public function testToArrayOfIntsHandleNulls()
    {
        $this->assertEquals([1, null, 3], JsonCast::toArrayOfInts(['1', null, '3']));
    }

    public function testToArrayOfIntsReturnsGivenParameterWithArrayOfInts()
    {
        $this->assertEquals([1, 2, 3], JsonCast::toArrayOfInts([1, 2, 3]));
    }
}
