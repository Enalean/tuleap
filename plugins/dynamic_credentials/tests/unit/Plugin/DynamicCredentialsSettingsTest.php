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

final class DynamicCredentialsSettingsTest extends TestCase
{
    use ForgeConfigSandbox;

    private const PUBLIC_KEY = 'ka7Gcvo3RO0FeksfVkBCgTndCz/IMLfwCQA3DoN8k68=';

    private PluginInfo&\PHPUnit\Framework\MockObject\Stub $plugin_info;
    private DynamicCredentialsSettings $settings;

    protected function setUp(): void
    {
        $this->plugin_info = $this->createStub(PluginInfo::class);
        $this->settings    = new DynamicCredentialsSettings($this->plugin_info);
    }

    public function testFetchSettingsFromLegacyIncFile(): void
    {
        $expected_real_name = 'Some real name';
        $this->plugin_info->method('getPropertyValueForName')->willReturnMap([
            ['signature_public_key', self::PUBLIC_KEY],
            ['dynamic_user_realname', $expected_real_name],
        ]);

        $key = $this->settings->getSignaturePublicKey();
        self::assertTrue($key->isValue());
        $name = $this->settings->getDynamicUserRealname();
        self::assertSame($expected_real_name, $name);
    }

    public function testFetchSettingsFromForgeConfig(): void
    {
        $this->plugin_info->method('getPropertyValueForName')->willReturn(false);

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
        $this->plugin_info->method('getPropertyValueForName')->willReturn(false);

        $key = $this->settings->getSignaturePublicKey();
        self::assertTrue($key->isNothing());
    }
}
