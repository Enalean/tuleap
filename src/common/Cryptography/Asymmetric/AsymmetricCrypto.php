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

namespace Tuleap\Cryptography\Asymmetric;

use Tuleap\Cryptography\Exception\InvalidSignatureException;

final class AsymmetricCrypto
{
    public function __construct()
    {
        throw new \RuntimeException('Do not instantiate this class, invoke the static methods directly');
    }

    public static function sign(string $message, SignatureSecretKey $secret_key): string
    {
        $raw_key_material = $secret_key->getRawKeyMaterial();

        $signature = \sodium_crypto_sign_detached($message, $raw_key_material);

        \sodium_memzero($raw_key_material);

        return $signature;
    }

    /**
     * @throws InvalidSignatureException
     */
    public static function verify(string $message, SignaturePublicKey $public_key, string $signature): bool
    {
        if (\mb_strlen($signature, '8bit') !== SODIUM_CRYPTO_SIGN_BYTES) {
            throw new InvalidSignatureException('Signature must be SODIUM_CRYPTO_SIGN_BYTES long');
        }

        $raw_key_material = $public_key->getRawKeyMaterial();

        $is_valid = \sodium_crypto_sign_verify_detached($signature, $message, $raw_key_material);

        \sodium_memzero($raw_key_material);

        return $is_valid;
    }
}
