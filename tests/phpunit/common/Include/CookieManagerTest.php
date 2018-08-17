<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

class CookieManagerTest extends \PHPUnit\Framework\TestCase // phpcs:ignore
{
    protected function setUp()
    {
        ForgeConfig::store();
        ForgeConfig::set('sys_cookie_prefix', 'test');
    }

    protected function tearDown()
    {
        ForgeConfig::restore();
    }

    /**
     * @runInSeparateProcess
     */
    public function testCookiePrefixIsSet()
    {
        ForgeConfig::set('sys_default_domain', 'example.com');
        ForgeConfig::set('sys_https_host', 'example.com');
        $cookie_manager = new CookieManager();
        $cookie_manager->setCookie('name', 'value');

        $headers = xdebug_get_headers();

        $this->assertSame($headers[0], 'Set-Cookie: __Host-test_name=value; path=/; secure; httponly; SameSite=Lax');
    }

    /**
     * @runInSeparateProcess
     */
    public function testItDoesNotSetCookiePrefixIfHTTPSIsNotAvailable()
    {
        ForgeConfig::set('sys_default_domain', 'example.com');
        ForgeConfig::set('sys_https_host', '');
        $cookie_manager = new CookieManager();
        $cookie_manager->setCookie('name', 'value');

        $headers = xdebug_get_headers();

        $this->assertSame($headers[0], 'Set-Cookie: test_name=value; path=/; httponly; SameSite=Lax');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCookiesRemoval()
    {
        $cookie_manager = new CookieManager();
        $cookie_manager->removeCookie('name');

        $headers = xdebug_get_headers();

        $this->assertSame($headers[0], 'Set-Cookie: test_name=deleted; expires=Thu, 01-Jan-1970 00:00:01 GMT; Max-Age=0; path=/; httponly; SameSite=Lax');
    }

    public function testItDeterminesIfACookieCanUseSecureFlag()
    {
        ForgeConfig::set('sys_https_host', '');
        $this->assertFalse(CookieManager::canCookieUseSecureFlag());
        ForgeConfig::set('sys_https_host', 'example.com');
        $this->assertTrue(CookieManager::canCookieUseSecureFlag());
    }
}
