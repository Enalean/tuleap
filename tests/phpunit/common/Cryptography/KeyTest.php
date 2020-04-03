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
use Tuleap\Cryptography\Exception\CannotSerializeKeyException;

final class KeyTest extends TestCase
{
    public function testRawKeyMaterialCanBeRetrieved(): void
    {
        $key_material = 'key_material';
        $key          = new Key(new ConcealedString($key_material));

        $this->assertEquals($key_material, $key->getRawKeyMaterial());
    }

    public function testSerializationIsDisabled(): void
    {
        $key = new Key(new ConcealedString('key_material'));

        $this->expectException(CannotSerializeKeyException::class);
        serialize($key);
    }

    public function testUnserializationIsDisabled(): void
    {
        $this->expectException(CannotSerializeKeyException::class);
        unserialize('O:23:"Tuleap\Cryptography\Key":0:{}');
    }

    public function testKeyCanNotBeTransformedToAString(): void
    {
        $key = new Key(new ConcealedString('key_material'));

        $this->assertEquals('', (string) $key);
    }

    public function testKeyMaterialIsNotPresentInTheDebugInformation(): void
    {
        $key_material = 'key_material_value';
        $key          = new Key(new ConcealedString($key_material));

        $this->assertStringNotContainsString($key_material, print_r($key, true));
    }
}
