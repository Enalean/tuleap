<?php
/**
 * Copyright (c) Enalean, 2014-2019. All Rights Reserved.
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
use Tuleap\Test\Network\HTTPHeaderStack;

class CookieManagerTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        ForgeConfig::store();
        ForgeConfig::set('sys_cookie_prefix', 'test');
    }

    protected function tearDown() : void
    {
        ForgeConfig::restore();
        HTTPHeaderStack::clear();
    }

    public function testCookiePrefixIsSet() : void
    {
        ForgeConfig::set('sys_default_domain', 'example.com');
        ForgeConfig::set('sys_https_host', 'example.com');
        $cookie_manager = new CookieManager();
        $cookie_manager->setCookie('name', 'value');

        $headers = HTTPHeaderStack::getStack();

        $this->assertCount(1, $headers);
        $this->assertSame($headers[0]->getHeader(), 'Set-Cookie: __Host-test_name=value; path=/; secure; httponly; SameSite=Lax');
    }

    public function testItDoesNotSetCookiePrefixIfHTTPSIsNotAvailable() : void
    {
        ForgeConfig::set('sys_default_domain', 'example.com');
        ForgeConfig::set('sys_https_host', '');
        $cookie_manager = new CookieManager();
        $cookie_manager->setCookie('name', 'value');

        $headers = HTTPHeaderStack::getStack();

        $this->assertCount(1, $headers);
        $this->assertSame($headers[0]->getHeader(), 'Set-Cookie: test_name=value; path=/; httponly; SameSite=Lax');
    }

    public function testCookiesRemoval() : void
    {
        $cookie_manager = new CookieManager();
        $cookie_manager->removeCookie('name');

        $headers = HTTPHeaderStack::getStack();

        $this->assertCount(1, $headers);
        $this->assertSame($headers[0]->getHeader(), 'Set-Cookie: test_name=deleted; expires=Thu, 01-Jan-1970 00:00:01 GMT; Max-Age=0; path=/; httponly; SameSite=Lax');
    }

    public function testItDeterminesIfACookieCanUseSecureFlag() : void
    {
        ForgeConfig::set('sys_https_host', '');
        $this->assertFalse(CookieManager::canCookieUseSecureFlag());
        ForgeConfig::set('sys_https_host', 'example.com');
        $this->assertTrue(CookieManager::canCookieUseSecureFlag());
    }
}
