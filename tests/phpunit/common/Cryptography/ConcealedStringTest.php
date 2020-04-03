<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Cryptography;

use PHPUnit\Framework\TestCase;

final class ConcealedStringTest extends TestCase
{
    public function testValueIsNotAltered(): void
    {
        $value_to_hide    = 'my_cleartext_credential';
        $concealed_string = new ConcealedString($value_to_hide);

        $this->assertEquals($value_to_hide, (string) $concealed_string);
        $this->assertEquals($value_to_hide, $concealed_string->getString());
    }

    public function testValueIsNotPresentInTheDebugInformation(): void
    {
        $value_to_hide    = 'private';
        $concealed_string = new ConcealedString($value_to_hide);

        $this->assertStringNotContainsString($value_to_hide, print_r($concealed_string, true));
    }

    public function testCompareWithIdenticatlStringsSucceeds(): void
    {
        $string_a = new ConcealedString('some content');
        $string_b = new ConcealedString('some content');
        $this->assertTrue($string_a->isIdenticalTo($string_b));
    }

    public function testCompareWithDifferentStringsFails(): void
    {
        $string_a = new ConcealedString('some content');
        $string_b = new ConcealedString('another content');
        $this->assertFalse($string_a->isIdenticalTo($string_b));
    }

    public function testSerializationIsNotAllowed(): void
    {
        $secret = new ConcealedString('a');

        $this->expectException(\LogicException::class);
        serialize($secret);
    }

    public function testDeserializationIsNotAllowed(): void
    {
        $this->expectException(\LogicException::class);
        unserialize('O:35:"Tuleap\Cryptography\ConcealedString":0:{}');
    }
}
