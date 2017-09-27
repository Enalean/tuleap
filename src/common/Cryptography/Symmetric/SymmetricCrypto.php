<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Cryptography\Symmetric;

use Tuleap\Cryptography\ConcealedString;

final class SymmetricCrypto
{
    public function __construct()
    {
        throw new \RuntimeException('Do not instantiate this class, invoke the static methods directly');
    }

    /**
     * @return string
     */
    public static function encrypt(ConcealedString $plaintext, EncryptionKey $secret_key)
    {
        $nonce = sodium_randombytes_buf(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        return $nonce . sodium_crypto_secretbox($plaintext->getString(), $nonce, $secret_key->getRawKeyMaterial());
    }
}
