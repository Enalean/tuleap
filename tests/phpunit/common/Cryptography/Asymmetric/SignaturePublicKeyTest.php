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

namespace Tuleap\Cryptography\Asymmetric;

use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Exception\InvalidKeyException;

final class SignaturePublicKeyTest extends TestCase
{
    public function testSignaturePublicKeyCanBeConstructed(): void
    {
        $key = new SignaturePublicKey(new ConcealedString(str_repeat('a', \SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES)));

        $this->assertEquals(\SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES, mb_strlen($key->getRawKeyMaterial()));
    }

    public function testSignaturePublicKeyWithAWronglySizedKeyMaterialIsNotConstructed(): void
    {
        $this->expectException(InvalidKeyException::class);
        new SignaturePublicKey(new ConcealedString('wrongly_sized_key_material'));
    }
}
