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
use URLVerification;

final class URLVerificationTest extends \Tuleap\Test\PHPUnit\TestCase
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
                ['REQUEST_URI' => '/account/login.php', 'SCRIPT_NAME' => '/account/login.php']
            )
        );
        $this->assertTrue(
            $urlVerification->isScriptAllowedForAnonymous(
                ['REQUEST_URI' => '/account/register.php', 'SCRIPT_NAME' => '/account/register.php']
            )
        );
        $this->assertTrue(
            $urlVerification->isScriptAllowedForAnonymous(
                ['REQUEST_URI' => '/include/check_pw.php', 'SCRIPT_NAME' => '/include/check_pw.php']
            )
        );
        $this->assertTrue(
            $urlVerification->isScriptAllowedForAnonymous(
                ['REQUEST_URI' => '/account/lostlogin.php', 'SCRIPT_NAME' => '/account/lostlogin.php']
            )
        );

        $this->assertFalse(
            $urlVerification->isScriptAllowedForAnonymous(['REQUEST_URI' => '/foobar', 'SCRIPT_NAME' => '/foobar'])
        );
    }

    public function testItDoesNotTreatRegularUrlsAsExceptions(): void
    {
        $urlVerification = new URLVerification();
        $this->assertFalse((bool) $urlVerification->isException(['SCRIPT_NAME' => '/projects/foobar']));
    }

    public function testItDoesNotTreatRegularUrlsWhichContainsAPIAsExceptions(): void
    {
        $urlVerification = new URLVerification();
        $this->assertFalse(
            (bool) $urlVerification->isException(
                ['SCRIPT_NAME' => '/projects/foobar/?p=/api/reference/extractCross']
            )
        );
    }

    public function testItTreatsExtractionOfCrossReferencesApiAsException(): void
    {
        $urlVerification = new URLVerification();
        $this->assertTrue((bool) $urlVerification->isException(['SCRIPT_NAME' => '/api/reference/extractCross']));
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
            $urlVerification->isScriptAllowedForAnonymous(['REQUEST_URI' => '/foobar', 'SCRIPT_NAME' => '/foobar'])
        );
    }

    public function testItChecksUriInternal(): void
    {
        ForgeConfig::set('sys_default_domain', 'default.example.test');
        $url_verification = new URLVerification();

        $this->assertFalse($url_verification->isInternal('http://evil.example.com/'));
        $this->assertFalse($url_verification->isInternal('https://evil.example.com/'));
        $this->assertFalse($url_verification->isInternal('javascript:alert(1)'));
        $this->assertTrue($url_verification->isInternal('/path/to/feature'));
        $this->assertTrue($url_verification->isInternal('?report=111'));
        $this->assertFalse(
            $url_verification->isInternal('http://' . ForgeConfig::get('sys_default_domain') . '/smthing')
        );
        $this->assertTrue(
            $url_verification->isInternal('https://' . ForgeConfig::get('sys_default_domain') . '/smthing')
        );

        $this->assertFalse($url_verification->isInternal('//example.com'));
        $this->assertFalse($url_verification->isInternal('/\example.com'));
        $this->assertFalse($url_verification->isInternal(
            'https://' . ForgeConfig::get('sys_default_domain') . '@evil.example.com'
        ));
    }
}
