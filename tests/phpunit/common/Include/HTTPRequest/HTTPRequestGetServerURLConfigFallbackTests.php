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
 *
 */

declare(strict_types=1);

class HTTPRequestGetServerURLConfigFallbackTests extends HTTPRequestGetServerURLTests // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public function setUp(): void
    {
        parent::setUp();

        $this->request->setTrustedProxies(array('17.18.19.20'));
        ForgeConfig::set('sys_default_domain', 'example.clear.test');
        ForgeConfig::set('sys_https_host', 'example.ssl.test');
    }

    public function testItReturnsHostNameOfProxyWhenBehindAProxy()
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['HTTP_HOST'] = 'meow.test';

        $this->assertEquals('https://meow.test', $this->request->getServerUrl());
    }

    public function testItReturnsTheConfiguredHTTPNameWhenInHTTP()
    {
        ForgeConfig::set('sys_https_host', '');
        $this->assertEquals('http://example.clear.test', $this->request->getServerUrl());
    }

    public function testItReturnsTheConfiguredHTTPSNameWhenInHTTPS()
    {
        $_SERVER['HTTPS'] = 'on';

        $this->assertEquals('https://example.ssl.test', $this->request->getServerUrl());
    }

    public function testItReturnsTheDefaultDomainNameWhenInHTTPButNothingConfiguredAsHTTPSHost()
    {
        $_SERVER['HTTPS'] = 'on';
        ForgeConfig::set('sys_https_host', '');

        $this->assertEquals('https://example.clear.test', $this->request->getServerUrl());
    }

    public function testItReturnsHTTPSURLWhenHTTPSIsAvailable()
    {
        ForgeConfig::set('sys_https_host', 'example.clear.test');

        $this->assertEquals('https://example.clear.test', $this->request->getServerUrl());
    }
}
