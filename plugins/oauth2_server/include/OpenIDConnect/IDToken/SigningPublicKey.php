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

namespace Tuleap\OAuth2Server\OpenIDConnect\IDToken;

/**
 * @psalm-immutable
 */
final class SigningPublicKey
{
    /**
     * @var string
     */
    private $fingerprint;
    /**
     * @var string
     */
    private $pem_public_key;

    private function __construct(string $fingerprint, string $pem_public_key)
    {
        $this->fingerprint    = $fingerprint;
        $this->pem_public_key = $pem_public_key;
    }

    public static function fromPEMFormat(string $pem_public_key): self
    {
        return new self(self::getPublicKeyFingerprint($pem_public_key), $pem_public_key);
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

    public function getFingerprint(): string
    {
        return $this->fingerprint;
    }

    public function getPEMPublicKey(): string
    {
        return $this->pem_public_key;
    }
}
