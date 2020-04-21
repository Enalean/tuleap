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

namespace Tuleap\Cryptography\Symmetric;

use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Exception\InvalidKeyException;

final class EncryptionKeyTest extends TestCase
{
    public function testEncryptionKeyConstruction(): void
    {
        $key = new EncryptionKey(new ConcealedString(str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));

        $this->assertEquals(SODIUM_CRYPTO_SECRETBOX_KEYBYTES, mb_strlen($key->getRawKeyMaterial()));
    }

    public function testEncryptionKeyIsNotConstructedWhenTheKeyMaterialIsWronglySized(): void
    {
        $this->expectException(InvalidKeyException::class);
        new EncryptionKey(new ConcealedString('wrongly_sized_key_material'));
    }
}
