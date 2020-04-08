<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2Server\OpenIDConnect\JWK;

/**
 * @psalm-immutable
 *
 * @see https://tools.ietf.org/html/rfc7517#section-4
 * @see https://tools.ietf.org/html/rfc7518#section-6.3.1
 */
final class JSONWebKey
{
    /**
     * @var string
     */
    public $kty = 'RSA';
    /**
     * @var string
     */
    public $alg = 'RS256';
    /**
     * @var string
     */
    public $use;
    /**
     * @var string
     */
    public $kid;
    /**
     * @var string
     */
    public $n;
    /**
     * @var string
     */
    public $e;

    private function __construct(string $use, string $n, string $e, string $key_id)
    {
        $this->use = $use;
        $this->n   = $n;
        $this->e   = $e;
        $this->kid = $key_id;
    }

    public static function fromPEMRSAPublicKeyForSignature(string $pem_public_key): self
    {
        $public_key = \openssl_get_publickey($pem_public_key);
        if ($public_key === false) {
            throw new InvalidPublicRSAKeyPEMFormatException($pem_public_key);
        }
        $details    = \openssl_pkey_get_details($public_key);
        if (! isset($details['rsa']['n'], $details['rsa']['e'])) {
            throw new InvalidPublicRSAKeyPEMFormatException($pem_public_key);
        }

        return new self(
            'sig',
            sodium_bin2base64($details['rsa']['n'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
            sodium_bin2base64($details['rsa']['e'], SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
            self::getPublicKeyFingerprint($pem_public_key)
        );
    }

    private static function getPublicKeyFingerprint(string $pem_public_key): string
    {
        $raw_key_base64 = str_replace(
            ['-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----', "\n"],
            ['', '', ''],
            $pem_public_key
        );
        $raw_key = sodium_base642bin($raw_key_base64, SODIUM_BASE64_VARIANT_ORIGINAL);

        return hash('sha256', $raw_key);
    }
}
