<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\DynamicCredentials\Plugin;

use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DynamicCredentialsSettingsTest extends TestCase
{
    use ForgeConfigSandbox;

    private const PUBLIC_KEY = 'ka7Gcvo3RO0FeksfVkBCgTndCz/IMLfwCQA3DoN8k68=';

    private DynamicCredentialsSettings $settings;

    protected function setUp(): void
    {
        $this->settings = new DynamicCredentialsSettings();
    }

    public function testFetchSettingsFromForgeConfig(): void
    {
        $expected_real_name = 'my_real_name';
        \ForgeConfig::set('dynamic_credentials_user_real_name', $expected_real_name);
        \ForgeConfig::set('dynamic_credentials_signature_public_key', self::PUBLIC_KEY);

        $key = $this->settings->getSignaturePublicKey();
        self::assertTrue($key->isValue());
        $name = $this->settings->getDynamicUserRealname();
        self::assertSame($expected_real_name, $name);
    }

    public function testDoesNotAttemptToBuildSignaturePublicKeyWhenNoInformationIsAvailable(): void
    {
        $key = $this->settings->getSignaturePublicKey();
        self::assertTrue($key->isNothing());
    }
}
