<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\Cryptography;

class ConcealedStringTest extends \TuleapTestCase
{
    public function itDoesNotAlterTheValue()
    {
        $value_to_hide    = 'my_cleartext_credential';
        $concealed_string = new ConcealedString($value_to_hide);

        $this->assertEqual($value_to_hide, (string) $concealed_string);
        $this->assertEqual($value_to_hide, $concealed_string->getString());
    }

    public function itCannotBeConstructedFromADifferentScalarThanAString()
    {
        try {
            new ConcealedString(true);
        } catch (\TypeError $error) {
            $this->pass();
            return;
        }
        $this->fail();
    }
}
