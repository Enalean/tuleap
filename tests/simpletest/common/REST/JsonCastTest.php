<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\REST\JsonCast;

class Rest_JsonCastTest extends TuleapTestCase
{

    public function itDoesNotReturnNullIfValueIsInt()
    {
        $value = 85;
        $this->assertEqual(JsonCast::toInt($value), 85);

        $value = "84";
        $this->assertEqual(JsonCast::toInt($value), 84);

        $value = "chaine";
        $this->assertEqual(JsonCast::toInt($value), 0);
    }

    public function testCastToIntReturnsNullIfValueIsNull()
    {
        $value = null;

        $this->assertNull(JsonCast::toInt($value));
    }

    public function itDoesNotReturnNullIfValueIsFloat()
    {
        $value = 85.8;
        $this->assertEqual(JsonCast::toFloat($value), 85.8);

        $value = "85.8";
        $this->assertEqual(JsonCast::toFloat($value), 85.8);

        $value = "chaine";
        $this->assertEqual(JsonCast::toFloat($value), 0);
    }

    public function testCastToFloatReturnsNullIfValueIsNull()
    {
        $value = null;

        $this->assertNull(JsonCast::toFloat($value));
    }

    public function testCastToDateReturnsIsoFormattedDate()
    {
        $value = mktime(0, 0, 0, 7, 1, 2000);

        $this->assertEqual(JsonCast::toDate($value), '2000-07-01T00:00:00+02:00');
    }

    public function testCastToDateReturnsNullIfValueIsNull()
    {
        $value = null;

        $this->assertNull(JsonCast::toDate($value));
    }

    public function testCastToObjectReturnsObjectWhenValueIsEmpty()
    {
        $value = [];

        $this->assertEqual(new stdClass(), JsonCast::toObject($value));
    }

    public function testCastToObjectPreservesAssociativeArray()
    {
        $value = ['key' => 'value'];

        $this->assertEqual(['key' => 'value'], JsonCast::toObject($value));
    }

    public function testCastToObjectReturnsNulLIfValueIsNull()
    {
        $value = null;

        $this->assertNull(JsonCast::toObject($value));
    }
}
