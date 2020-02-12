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

// Tests inspired from From Symfony\Component\HttpFoundation\Tests\IpUtilsTest @ 3.2-dev
class HTTPRequestGetServerURLTrustedProxyTests extends HTTPRequestGetServerURLTests // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{

    public function setUp(): void
    {
        parent::setUp();

        $_SERVER['HTTPS']       = 'on';

        ForgeConfig::set('sys_default_domain', 'example.com');
        ForgeConfig::set('sys_https_host', 'example.com');
    }

    public function testItDoesntTakeHostWhenForwardedProtoIsSetByAnUntrustedProxy()
    {
        $_SERVER['HTTP_HOST']              = 'example.org';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';

        $this->assertEquals('https://example.com', $this->request->getServerUrl());
    }

    public function testItTrustsProxy()
    {
        $_SERVER['HTTP_HOST']              = 'example.org';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '192.168.1.1';

        $this->request->setTrustedProxies(array('192.168.1.1'));
        $this->assertEquals('https://example.org', $this->request->getServerUrl());
    }

    public function testItAllowsCIDRNotation()
    {
        $_SERVER['HTTP_HOST']              = 'example.org';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '192.168.1.1';

        $this->request->setTrustedProxies(array('192.168.1.1/1'));
        $this->assertEquals('https://example.org', $this->request->getServerUrl());
    }

    public function testItAllowsCIDRNotationWithSlash24()
    {
        $_SERVER['HTTP_HOST']              = 'example.org';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '192.168.1.1';

        $this->request->setTrustedProxies(array('192.168.1.1/24'));
        $this->assertEquals('https://example.org', $this->request->getServerUrl());
    }

    public function testItDoesntAllowsNotMatchingCIDRNotation()
    {
        $_SERVER['HTTP_HOST']              = 'example.org';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '192.168.1.1';

        $this->request->setTrustedProxies(array('1.2.3.4/1'));
        $this->assertEquals('https://example.com', $this->request->getServerUrl());
    }

    public function testItDoesntAllowsInvalidSubnet()
    {
        $_SERVER['HTTP_HOST']              = 'example.org';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '192.168.1.1';

        $this->request->setTrustedProxies(array('192.168.1.1/33'));
        $this->assertEquals('https://example.com', $this->request->getServerUrl());
    }

    public function testItAllowsWhenAtLeastOneSubnetMatches()
    {
        $_SERVER['HTTP_HOST']              = 'example.org';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '192.168.1.1';

        $this->request->setTrustedProxies(array('1.2.3.4/1', '192.168.1.0/24'));
        $this->assertEquals('https://example.org', $this->request->getServerUrl());
    }

    public function testItDoesntAllowsWhenNoSubnetMatches()
    {
        $_SERVER['HTTP_HOST']              = 'example.org';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '192.168.1.1';

        $this->request->setTrustedProxies(array('1.2.3.4/1', '4.3.2.1/1'));
        $this->assertEquals('https://example.com', $this->request->getServerUrl());
    }

    public function testItDoesntAllowsInvalidCIDRNotation()
    {
        $_SERVER['HTTP_HOST']              = 'example.org';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '1.2.3.4';

        $this->request->setTrustedProxies(array('256.256.256/0'));
        $this->assertEquals('https://example.com', $this->request->getServerUrl());
    }

    public function testItAllowsWithExtremCIDRNotation1()
    {
        $_SERVER['HTTP_HOST']              = 'example.org';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '1.2.3.4';

        $this->request->setTrustedProxies(array('0.0.0.0/0'));
        $this->assertEquals('https://example.org', $this->request->getServerUrl());
    }

    public function testItAllowsWithExtremCIDRNotation2()
    {
        $_SERVER['HTTP_HOST']              = 'example.org';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '1.2.3.4';

        $this->request->setTrustedProxies(array('192.168.1.0/0'));
        $this->assertEquals('https://example.org', $this->request->getServerUrl());
    }
}
