<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Configuration;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2ServerCore\App\LastGeneratedClientSecret;
use Tuleap\Test\PHPUnit\TestCase;

final class LocalSettingsFactoryTest extends TestCase
{
    public function testGeneratesRepresentation(): void
    {
        $factory = new LocalSettingsFactory(
            new class implements MediaWikiOAuth2AppSecretGenerator {
                public function generateOAuth2AppSecret(): LastGeneratedClientSecret
                {
                    return new LastGeneratedClientSecret(789, new ConcealedString('random_oauth2_secret'));
                }
            },
            new class implements MediaWikiSharedSecretGenerator
            {
                public function generateSharedSecret(): ConcealedString
                {
                    return new ConcealedString('random_shared_secret');
                }
            }
        );

        $representation = $factory->generateTuleapLocalSettingsRepresentation();

        self::assertStringContainsString('789', $representation->oauth2_client_id);
        self::assertEquals('random_oauth2_secret', $representation->oauth2_client_secret->getString());
        self::assertEquals('random_shared_secret', $representation->pre_shared_key->getString());
    }
}
