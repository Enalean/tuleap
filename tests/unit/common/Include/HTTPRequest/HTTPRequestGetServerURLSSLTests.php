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

class HTTPRequestGetServerURLSSLTests extends HTTPRequestGetServerURLTests // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->request->setTrustedProxies(array('17.18.19.20'));
        $_SERVER['HTTP_HOST'] = 'example.com';
        ForgeConfig::set('sys_default_domain', 'example.com');
    }

    public function testItReturnsHttpsWhenHTTPSIsTerminatedBySelf()
    {
        $_SERVER['HTTPS'] = 'on';

        $this->assertEquals('https://example.com', $this->request->getServerUrl());
    }

    public function testItReturnsHttpWhenHTTPSIsNotEnabled()
    {
        $this->assertEquals('http://example.com', $this->request->getServerUrl());
    }

    public function testItReturnsHTTPSWhenReverseProxyTerminateSSLAndCommunicateInClearWithTuleap()
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

        $this->assertEquals('https://example.com', $this->request->getServerUrl());
    }

    public function testItReturnsHTTPWhenReverseProxyDoesntTerminateSSLAndCommunicateInClearWithTuleap()
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';

        $this->assertEquals('http://example.com', $this->request->getServerUrl());
    }

    public function testItReturnsHTTPWhenReverseProxyDoesntTerminateSSLAndCommunicateInSSLWithTuleap()
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';

        $this->assertEquals('http://example.com', $this->request->getServerUrl());
    }

    public function testItReturnsHTTPSWhenEverythingIsSSL()
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

        $this->assertEquals('https://example.com', $this->request->getServerUrl());
    }

    public function testItReturnsHTTPSURLWhenHTTPSIsAvailableAndRequestDoesNotFromATrustedProxy()
    {
        ForgeConfig::set('sys_https_host', 'example.com');
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '192.0.2.1';

        $this->assertEquals('https://example.com', $this->request->getServerUrl());
    }

    public function testItReturnsHTTPSURLWhenHTTPSIsAvailableAndProxyIsMisconfigured()
    {
        ForgeConfig::set('sys_https_host', 'example.com');
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';

        $this->assertEquals('https://example.com', $this->request->getServerUrl());
    }
}
