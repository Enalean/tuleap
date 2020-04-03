<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

namespace Tuleap;

use ForgeConfig;

class CookieManagerTest extends \PHPUnit\Framework\TestCase
{
    use ForgeConfigSandbox;

    protected function setUp(): void
    {
        ForgeConfig::set('sys_cookie_prefix', 'test');
    }

    public function testCookiePrefixIsUsedIfPossible(): void
    {
        ForgeConfig::set('sys_https_host', 'example.com');

        $this->assertEquals('__Host-test_name', CookieManager::getCookieName('name'));
    }

    public function testItDoesNotUseCookiePrefixIfHTTPSIsNotAvailable(): void
    {
        ForgeConfig::set('sys_https_host', '');

        $this->assertEquals('test_name', CookieManager::getCookieName('name'));
    }

    public function testItDeterminesIfACookieCanUseSecureFlag(): void
    {
        ForgeConfig::set('sys_https_host', '');
        $this->assertFalse(CookieManager::canCookieUseSecureFlag());
        ForgeConfig::set('sys_https_host', 'example.com');
        $this->assertTrue(CookieManager::canCookieUseSecureFlag());
    }
}
