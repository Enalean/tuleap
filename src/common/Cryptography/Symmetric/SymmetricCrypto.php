<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

final readonly class SymmetricCrypto
{
    private function __construct()
    {
    }

    public static function encrypt(ConcealedString $message, EncryptionAdditionalData $additional_data, EncryptionKey $secret_key): string
    {
        $nonce = \random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);

        $additional_data_canonicalized = $additional_data->canonicalize();

        $ciphertext = \sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $message->getString(),
            $additional_data_canonicalized,
            $nonce,
            $secret_key->getRawKeyMaterial(),
        );

        // See https://doc.libsodium.org/secret-key_cryptography/aead#robustness
        // https://soatok.blog/2024/09/10/invisible-salamanders-are-not-what-you-think/
        $auth_tag = \sodium_crypto_auth($nonce . $ciphertext . $additional_data_canonicalized, $secret_key->getRawKeyMaterial());

        return $auth_tag . $nonce . $ciphertext;
    }

    /**
     * @throws \Tuleap\Cryptography\Exception\InvalidCiphertextException
     */
    public static function decrypt(string $ciphertext_with_auth_tag, EncryptionAdditionalData $additional_data, EncryptionKey $secret_key): ConcealedString
    {
        $auth_tag   = \mb_substr($ciphertext_with_auth_tag, 0, SODIUM_CRYPTO_AUTH_BYTES, '8bit');
        $nonce      = \mb_substr($ciphertext_with_auth_tag, SODIUM_CRYPTO_AUTH_BYTES, SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES, '8bit');
        $ciphertext = \mb_substr(
            $ciphertext_with_auth_tag,
            SODIUM_CRYPTO_AUTH_BYTES + SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES,
            \mb_strlen($ciphertext_with_auth_tag, '8bit'),
            '8bit'
        );

        $additional_data_canonicalized = $additional_data->canonicalize();

        if (
            \mb_strlen($auth_tag, '8bit') !== SODIUM_CRYPTO_AUTH_BYTES ||
            ! \sodium_crypto_auth_verify(
                $auth_tag,
                $nonce . $ciphertext . $additional_data_canonicalized,
                $secret_key->getRawKeyMaterial()
            )
        ) {
            throw new InvalidCiphertextException();
        }

        $raw_plaintext = \sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext,
            $additional_data_canonicalized,
            $nonce,
            $secret_key->getRawKeyMaterial()
        );

        if ($raw_plaintext === false) {
            throw new InvalidCiphertextException();
        }

        $plaintext = new ConcealedString($raw_plaintext);

        \sodium_memzero($raw_plaintext);

        return $plaintext;
    }
}
