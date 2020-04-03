<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap;

use EventManager;
use ForgeConfig;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use URLVerification;

class URLVerificationTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    public function testIsScriptAllowedForAnonymous(): void
    {
        $urlVerification = \Mockery::mock(\URLVerification::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $em = Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with(
            Mockery::any(),
            Mockery::on(
                function (array $params) {
                    $params['anonymous_allowed'] = false;
                    return true;
                }
            )
        );

        $urlVerification->shouldReceive('getEventManager')->andReturns($em);

        $this->assertTrue(
            $urlVerification->isScriptAllowedForAnonymous(
                array('REQUEST_URI' => '/account/login.php', 'SCRIPT_NAME' => '/account/login.php')
            )
        );
        $this->assertTrue(
            $urlVerification->isScriptAllowedForAnonymous(
                array('REQUEST_URI' => '/account/register.php', 'SCRIPT_NAME' => '/account/register.php')
            )
        );
        $this->assertTrue(
            $urlVerification->isScriptAllowedForAnonymous(
                array('REQUEST_URI' => '/include/check_pw.php', 'SCRIPT_NAME' => '/include/check_pw.php')
            )
        );
        $this->assertTrue(
            $urlVerification->isScriptAllowedForAnonymous(
                array('REQUEST_URI' => '/account/lostpw.php', 'SCRIPT_NAME' => '/account/lostpw.php')
            )
        );
        $this->assertTrue(
            $urlVerification->isScriptAllowedForAnonymous(
                array('REQUEST_URI' => '/account/lostlogin.php', 'SCRIPT_NAME' => '/account/lostlogin.php')
            )
        );
        $this->assertTrue(
            $urlVerification->isScriptAllowedForAnonymous(
                array('REQUEST_URI' => '/account/lostpw-confirm.php', 'SCRIPT_NAME' => '/account/lostpw-confirm.php')
            )
        );

        $this->assertFalse(
            $urlVerification->isScriptAllowedForAnonymous(array('REQUEST_URI' => '/foobar', 'SCRIPT_NAME' => '/foobar'))
        );
    }

    public function testItDoesNotTreatRegularUrlsAsExceptions(): void
    {
        $urlVerification = new URLVerification();
        $this->assertFalse((bool) $urlVerification->isException(array('SCRIPT_NAME' => '/projects/foobar')));
    }

    public function testItDoesNotTreatRegularUrlsWhichContainsSOAPAsExceptions(): void
    {
        $urlVerification = new URLVerification();
        $this->assertFalse(
            (bool) $urlVerification->isException(array('SCRIPT_NAME' => '/projects/foobar/?p=/soap/index.php'))
        );
    }

    public function testItDoesNotTreatRegularUrlsWhichContainsAPIAsExceptions(): void
    {
        $urlVerification = new URLVerification();
        $this->assertFalse(
            (bool) $urlVerification->isException(
                array('SCRIPT_NAME' => '/projects/foobar/?p=/api/reference/extractCross')
            )
        );
    }

    public function testItTreatsSOAPApiAsException(): void
    {
        $urlVerification = new URLVerification();
        $this->assertTrue((bool) $urlVerification->isException(array('SCRIPT_NAME' => '/soap/index.php')));
    }

    public function testItTreatsSOAPApiOfPluginsAsException(): void
    {
        $urlVerification = new URLVerification();
        $this->assertTrue(
            (bool) $urlVerification->isException(array('SCRIPT_NAME' => '/plugins/docman/soap/index.php'))
        );
    }

    public function testItTreatsExtractionOfCrossReferencesApiAsException(): void
    {
        $urlVerification = new URLVerification();
        $this->assertTrue((bool) $urlVerification->isException(array('SCRIPT_NAME' => '/api/reference/extractCross')));
    }

    public function testIsScriptAllowedForAnonymousFromHook(): void
    {
        $urlVerification = \Mockery::mock(\URLVerification::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $em              = Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with(
            Mockery::any(),
            Mockery::on(
                function (array $params) {
                    $params['anonymous_allowed'] = true;
                    return true;
                }
            )
        );
        $urlVerification->shouldReceive('getEventManager')->andReturns($em);

        $this->assertTrue(
            $urlVerification->isScriptAllowedForAnonymous(array('REQUEST_URI' => '/foobar', 'SCRIPT_NAME' => '/foobar'))
        );
    }

    public function testVerifyProtocolHTTPAndHTTPSIsAvailable(): void
    {
        $urlVerification = new URLVerification();

        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('isSecure')->andReturns(false);

        ForgeConfig::set('sys_https_host', true);

        $urlVerification->verifyProtocol($request);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEquals('https', $chunks['protocol']);
    }

    public function testVerifyProtocolHTTPSAndHTTPSIsAvailable(): void
    {
        $urlVerification = new URLVerification();
        $request         = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('isSecure')->andReturns(false);
        $urlVerification->verifyProtocol($request);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertFalse(isset($chunks['protocol']));
    }

    public function testVerifyProtocolHTTPAndHTTPSIsNotAvailable(): void
    {
        $urlVerification = new URLVerification();
        $request         = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('isSecure')->andReturns(false);
        $urlVerification->verifyProtocol($request);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertFalse(isset($chunks['protocol']));
    }

    public function testVerifyHostHTTPSAndHTTPSIsAvailable(): void
    {
        ForgeConfig::set('sys_https_host', 'secure.example.com');

        $urlVerification = new URLVerification();
        $request         = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('isSecure')->andReturns(true);
        $urlVerification->verifyProtocol($request);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertFalse(isset($chunks['host']));
    }

    public function testVerifyHostHTTPAndHTTPSIsAvailable(): void
    {
        ForgeConfig::set('sys_default_domain', 'example.com');
        ForgeConfig::set('sys_https_host', 'secure.example.com');

        $request = \Mockery::spy(\HTTPRequest::class);
        $request->shouldReceive('isSecure')->andReturns(false);

        $urlVerification = new URLVerification();
        $urlVerification->verifyProtocol($request);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEquals('secure.example.com', $chunks['host']);
    }

    public function testVerifyHostInvalidHostAndHTTPSIsAvailable(): void
    {
        ForgeConfig::set('sys_default_domain', 'example.com');
        ForgeConfig::set('sys_https_host', 'secure.example.com');

        $urlVerification = new URLVerification();

        $request = \Mockery::spy(\HTTPRequest::class);
        $request->shouldReceive('isSecure')->andReturns(true);

        $urlVerification->verifyProtocol($request);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertFalse(isset($chunks['host']));
    }
}
