<?php
/**
 * Copyright (c) Enalean 2016-Present. All rights reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

namespace Tuleap\Webdav;

use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;

class WebdavURLVerificationTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var \HTTPRequest|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $request;

    /**
     * @var \Mockery\Mock
     */
    private $webdavURLVerification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = \Mockery::spy(\HTTPRequest::class);
        $this->webdavURLVerification = \Mockery::mock(\Webdav_URLVerification::class)->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testAssertValidUrlHTTPAndHTTPSHostNotAvailable(): void
    {
        $server = ['HTTP_HOST' => 'webdav.tuleap.test'];

        ForgeConfig::set('sys_default_domain', 'example.com');
        ForgeConfig::set('sys_https_host', '');

        $this->webdavURLVerification->shouldReceive('getWebDAVHost')->andReturns('webdav.tuleap.test');

        $this->webdavURLVerification->shouldReceive('forbiddenError')->never();
        $this->webdavURLVerification->shouldReceive('isException')->never(); // no parent call

        $this->webdavURLVerification->assertValidUrl($server, $this->request);
    }

    public function testAssertValidUrlHTTPSAndHTTPSHostNotAvailable(): void
    {
        $server = ['HTTP_HOST' => 'webdav.tuleap.test'];
        $this->request->shouldReceive('isSecure')->andReturns(true);

        ForgeConfig::set('sys_default_domain', 'example.com');
        ForgeConfig::set('sys_https_host', '');

        $this->webdavURLVerification->shouldReceive('getWebDAVHost')->andReturns('webdav.tuleap.test');

        $this->webdavURLVerification->shouldReceive('forbiddenError')->never();
        $this->webdavURLVerification->shouldReceive('isException')->never(); // no parent call

        $this->webdavURLVerification->assertValidUrl($server, $this->request);
    }

    public function testAssertValidUrlHTTPAndHTTPSHostAvailable(): void
    {
        $server = ['HTTP_HOST' => 'webdav.tuleap.test'];

        ForgeConfig::set('sys_default_domain', 'example.com');
        ForgeConfig::set('sys_https_host', 'example.com');

        $this->webdavURLVerification->shouldReceive('getWebDAVHost')->andReturns('webdav.tuleap.test');

        $this->webdavURLVerification->shouldReceive('forbiddenError')->once();
        $this->webdavURLVerification->shouldReceive('isException')->never(); // no parent call

        $this->webdavURLVerification->assertValidUrl($server, $this->request);
    }

    public function testAssertValidUrlHTTPSAndHTTPSHostAvailable(): void
    {
        $server = ['HTTP_HOST' => 'webdav.tuleap.test'];
        $this->request->shouldReceive('isSecure')->andReturns(true);

        ForgeConfig::set('sys_default_domain', 'example.com');
        ForgeConfig::set('sys_https_host', 'example.com');

        $this->webdavURLVerification->shouldReceive('getWebDAVHost')->andReturns('webdav.tuleap.test');

        $this->webdavURLVerification->shouldReceive('forbiddenError')->never();
        $this->webdavURLVerification->shouldReceive('isException')->never(); // no parent call

        $this->webdavURLVerification->assertValidUrl($server, $this->request);
    }

    public function testAssertValidUrlNotPluginHost(): void
    {
        $server = ['HTTP_HOST' => 'codendi.org'];

        $this->webdavURLVerification->shouldReceive('getWebDAVHost')->andReturns('webdav.codendi.org');

        $this->webdavURLVerification->shouldReceive('forbiddenError')->never(); // parent call
        $this->webdavURLVerification->shouldReceive('isException')->once()->andReturns(true);

        $this->webdavURLVerification->assertValidUrl($server, $this->request);
    }

    public function testAssertValidUrlButWebdavHostIsDefaultDomain(): void
    {
        $server = ['HTTP_HOST' => 'a.example.com'];

        ForgeConfig::set('sys_default_domain', 'a.example.com');

        $this->webdavURLVerification->shouldReceive('getWebDAVHost')->andReturns('a.example.com');

        $this->webdavURLVerification->shouldReceive('forbiddenError')->never(); // parent call
        $this->webdavURLVerification->shouldReceive('isException')->once()->andReturns(true);

        $this->webdavURLVerification->assertValidUrl($server, $this->request);
    }

    public function testAssertValidUrlButWebdavHostIsHttpsHost(): void
    {
        $server = ['HTTP_HOST' => 'b.example.com'];

        ForgeConfig::set('sys_default_domain', 'example.com');
        ForgeConfig::set('sys_https_host', 'b.example.com');

        $this->webdavURLVerification->shouldReceive('getWebDAVHost')->andReturns('b.example.com');

        $this->webdavURLVerification->shouldReceive('forbiddenError')->never(); // parent call
        $this->webdavURLVerification->shouldReceive('isException')->once()->andReturns(true);

        $this->webdavURLVerification->assertValidUrl($server, $this->request);
    }
}
