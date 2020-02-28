<?php
/**
 * Copyright (c) Enalean, 2017-2019. All Rights Reserved.
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

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Exception\InvalidCiphertextException;
use Tuleap\Cryptography\Exception\UnexpectedOperationFailureException;

final class SymmetricCrypto
{
    public function __construct()
    {
        throw new \RuntimeException('Do not instantiate this class, invoke the static methods directly');
    }

    public static function encrypt(ConcealedString $plaintext, EncryptionKey $secret_key) : string
    {
        $nonce = \random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

        $raw_plaintext    = $plaintext->getString();
        $raw_key_material = $secret_key->getRawKeyMaterial();

        $encrypted_data = $nonce . \sodium_crypto_secretbox($raw_plaintext, $nonce, $raw_key_material);

        \sodium_memzero($raw_plaintext);
        \sodium_memzero($raw_key_material);

        return $encrypted_data;
    }

    /**
     * @throws \Tuleap\Cryptography\Exception\InvalidCiphertextException
     */
    public static function decrypt(string $ciphertext, EncryptionKey $secret_key) : ConcealedString
    {
        $nonce             = \mb_substr($ciphertext, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext_length = \mb_strlen($ciphertext, '8bit');
        if ($ciphertext_length === false) {
            throw new UnexpectedOperationFailureException('mb_strlen() failed unexpectedly');
        }
        $encrypted = \mb_substr($ciphertext, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, $ciphertext_length, '8bit');

        $raw_plaintext = \sodium_crypto_secretbox_open($encrypted, $nonce, $secret_key->getRawKeyMaterial());
        if ($raw_plaintext === false) {
            throw new InvalidCiphertextException();
        }

        $plaintext = new ConcealedString($raw_plaintext);

        \sodium_memzero($raw_plaintext);

        return $plaintext;
    }
}
