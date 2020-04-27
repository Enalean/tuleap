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

use PHPUnit\Framework\TestCase;

final class SigningPublicKeyTest extends TestCase
{
    private const SIGNING_PUBLIC_KEY_FINGERPRINT = '13e908c0c14b52fa364f6573cda85971d16de83b17d6ef8793447724c464c01c';
    private const SIGNING_PUBLIC_KEY             = <<<EOT
        -----BEGIN PUBLIC KEY-----
        MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApVp45DC1lniS5l9yiR81
        OM3BCESDLyZYX3pXS32oJz0eOIqgA4mnqGNvupo/ARJnu1W/KVNNqxBNGno1oNLg
        V3GkHULBV+D4NDaX4064I0k1dk0HZBd8OG8QB0dwFoNFZ19SNrsEyq4xFn3CIysl
        lfFE6GVQVht84/etmvO5+p4Dj6kUM4FO46jBXQBxSQs7ErE22m67CViu9ApDjZ1W
        9e7mHItPZfw0ldH6Y6+ZXfz8SBs/lblm/1BST1C7l/5vQtjStgHmiGlVL6CRIzyx
        DCJKYKP1r0FrwUEnMJEU1h+MyMSKPP9gzln8+icbhSvQF/eX6oZCfl+ibrC/nRZf
        2QIDAQAB
        -----END PUBLIC KEY-----
        EOT;

    public function testComputesFingerprint(): void
    {
        $public_key = SigningPublicKey::fromPEMFormat(self::SIGNING_PUBLIC_KEY);

        $this->assertEquals(self::SIGNING_PUBLIC_KEY, $public_key->getPEMPublicKey());
        $this->assertEquals(self::SIGNING_PUBLIC_KEY_FINGERPRINT, $public_key->getFingerprint());
    }
}
