<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\REST;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class JsonCastTest extends TestCase
{
    public function testDoesNotReturnNullIfValueIsInt(): void
    {
        $value = 85;
        $this->assertEquals(85, JsonCast::toInt($value));

        $value = '84';
        $this->assertEquals(84, JsonCast::toInt($value));

        $value = 'chaine';
        $this->assertEquals(0, JsonCast::toInt($value));
    }

    public function testCastToIntReturnsNullIfValueIsNull(): void
    {
        $value = null;

        $this->assertNull(JsonCast::toInt($value));
    }

    public function testDoesNotReturnNullIfValueIsFloat(): void
    {
        $value = 85.8;
        $this->assertEquals(85.8, JsonCast::toFloat($value));

        $value = '85.8';
        $this->assertEquals(85.8, JsonCast::toFloat($value));

        $value = 'chaine';
        $this->assertEquals(0, JsonCast::toFloat($value));
    }

    public function testCastToFloatReturnsNullIfValueIsNull(): void
    {
        $value = null;

        $this->assertNull(JsonCast::toFloat($value));
    }

    public function testCastToDateReturnsIsoFormattedDate(): void
    {
        $value = mktime(0, 0, 0, 7, 1, 2000);

        $this->assertEquals('2000-07-01T00:00:00+02:00', JsonCast::toDate($value));
    }

    public function testCastToDateReturnsNullIfValueIsNull(): void
    {
        $value = null;

        $this->assertNull(JsonCast::toDate($value));
    }

    public function testCastToObjectReturnsObjectWhenValueIsEmpty()
    {
        $value = [];

        $this->assertEquals(new \stdClass(), JsonCast::toObject($value));
    }

    public function testCastToObjectPreservesAssociativeArray(): void
    {
        $value = ['key' => 'value'];

        $this->assertEquals(['key' => 'value'], JsonCast::toObject($value));
    }

    public function testCastToObjectReturnsNulLIfValueIsNull(): void
    {
        $value = null;

        $this->assertNull(JsonCast::toObject($value));
    }

    public function testToArrayOfInts(): void
    {
        $this->assertEquals([1, 2, 3], JsonCast::toArrayOfInts(['1', '2', '3']));
    }

    public function testToArrayOfIntsReturnsNullWhenNullGiven(): void
    {
        $this->assertEquals(null, JsonCast::toArrayOfInts(null));
    }

    public function testToArrayOfIntsHandleNulls(): void
    {
        $this->assertEquals([1, null, 3], JsonCast::toArrayOfInts(['1', null, '3']));
    }

    public function testToArrayOfIntsReturnsGivenParameterWithArrayOfInts(): void
    {
        $this->assertEquals([1, 2, 3], JsonCast::toArrayOfInts([1, 2, 3]));
    }

    public function testFromDateTimeToDateWithDateTime(): void
    {
        $value = DateTime::createFromFormat(
            'Y-m-d H:i:s',
            '2019-03-21 14:47:03',
            new DateTimeZone('+0400')
        );
        $this->assertEquals("2019-03-21T14:47:03+04:00", JsonCast::fromDateTimeToDate($value));
    }

    public function testFromDateTimeToDateWithDateTimeImmutable(): void
    {
        $value = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            '2019-03-21 14:47:03',
            new DateTimeZone('+0400')
        );
        $this->assertEquals("2019-03-21T14:47:03+04:00", JsonCast::fromDateTimeToDate($value));
    }

    public function testFromDateTimeToDateReturnsNullWhenNullGiven(): void
    {
        $this->assertNull(JsonCast::fromDateTimeToDate(null));
    }
}
