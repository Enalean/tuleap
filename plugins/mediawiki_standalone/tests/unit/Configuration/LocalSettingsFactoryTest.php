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
use Tuleap\ForgeConfigSandbox;
use Tuleap\OAuth2ServerCore\App\LastGeneratedClientSecret;
use Tuleap\Test\PHPUnit\TestCase;

final class LocalSettingsFactoryTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testGeneratesRepresentation(): void
    {
        \ForgeConfig::set('sys_supported_languages', 'en_US,fr_FR');
        \ForgeConfig::set('sys_dbhost', 'dbhost');
        \ForgeConfig::set('sys_dbport', 3306);
        \ForgeConfig::set('sys_dbuser', 'dbuser');
        \ForgeConfig::set('sys_dbpasswd', 'dbpass');

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
            },
            new class implements MediaWikiCentralDatabaseParameterGenerator
            {
                public function getCentralDatabase(): ?string
                {
                    return 'tuleap_central';
                }
            }
        );

        $representation = $factory->generateTuleapLocalSettingsRepresentation();

        self::assertEquals('dbhost:3306', $representation->db_server_hostname);
        self::assertEquals('dbuser', $representation->db_server_username);
        self::assertEquals('dbpass', $representation->db_server_password->getString());
        self::assertStringContainsString('789', $representation->oauth2_client_id);
        self::assertEquals('random_oauth2_secret', $representation->oauth2_client_secret->getString());
        self::assertEquals('random_shared_secret', $representation->pre_shared_key->getString());
        self::assertEqualsCanonicalizing(['en', 'fr'], array_keys(json_decode($representation->supported_languages_json, true, 2, JSON_THROW_ON_ERROR)));
        self::assertEquals('tuleap_central', $representation->central_database);
    }
}
