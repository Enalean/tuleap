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
use URLVerification;

final class URLVerificationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    public function testIsScriptAllowedForAnonymous(): void
    {
        $urlVerification = $this->createPartialMock(\URLVerification::class, [
            'getEventManager',
        ]);

        $em = $this->createMock(EventManager::class);
        $em->method('processEvent')->with(
            self::anything(),
            self::callback(
                function (array $params) {
                    $params['anonymous_allowed'] = false;
                    return true;
                }
            )
        );

        $urlVerification->method('getEventManager')->willReturn($em);

        self::assertTrue(
            $urlVerification->isScriptAllowedForAnonymous(
                ['REQUEST_URI' => '/account/login.php', 'SCRIPT_NAME' => '/account/login.php']
            )
        );
        self::assertTrue(
            $urlVerification->isScriptAllowedForAnonymous(
                ['REQUEST_URI' => '/account/register.php', 'SCRIPT_NAME' => '/account/register.php']
            )
        );
        self::assertTrue(
            $urlVerification->isScriptAllowedForAnonymous(
                ['REQUEST_URI' => '/include/check_pw.php', 'SCRIPT_NAME' => '/include/check_pw.php']
            )
        );
        self::assertTrue(
            $urlVerification->isScriptAllowedForAnonymous(
                ['REQUEST_URI' => '/account/lostlogin.php', 'SCRIPT_NAME' => '/account/lostlogin.php']
            )
        );

        self::assertFalse(
            $urlVerification->isScriptAllowedForAnonymous(['REQUEST_URI' => '/foobar', 'SCRIPT_NAME' => '/foobar'])
        );
    }

    public function testItDoesNotTreatRegularUrlsAsExceptions(): void
    {
        $urlVerification = new URLVerification();
        self::assertFalse($urlVerification->isException(['SCRIPT_NAME' => '/projects/foobar']));
    }

    public function testItDoesNotTreatRegularUrlsWhichContainsAPIAsExceptions(): void
    {
        $urlVerification = new URLVerification();
        self::assertFalse(
            $urlVerification->isException(
                ['SCRIPT_NAME' => '/projects/foobar/?p=/api/reference/extractCross']
            )
        );
    }

    public function testItTreatsExtractionOfCrossReferencesApiAsException(): void
    {
        $urlVerification = new URLVerification();
        self::assertTrue($urlVerification->isException(['SCRIPT_NAME' => '/api/reference/extractCross']));
    }

    public function testIsScriptAllowedForAnonymousFromHook(): void
    {
        $urlVerification = $this->createPartialMock(\URLVerification::class, [
            'getEventManager',
        ]);
        $em              = $this->createMock(EventManager::class);
        $em->method('processEvent')->with(
            self::anything(),
            self::callback(
                function (array $params) {
                    $params['anonymous_allowed'] = true;
                    return true;
                }
            )
        );
        $urlVerification->method('getEventManager')->willReturn($em);

        self::assertTrue(
            $urlVerification->isScriptAllowedForAnonymous(['REQUEST_URI' => '/foobar', 'SCRIPT_NAME' => '/foobar'])
        );
    }

    public function testItChecksUriInternal(): void
    {
        ForgeConfig::set('sys_default_domain', 'default.example.test');
        $url_verification = new URLVerification();

        self::assertFalse($url_verification->isInternal('http://evil.example.com/'));
        self::assertFalse($url_verification->isInternal('https://evil.example.com/'));
        self::assertFalse($url_verification->isInternal('javascript:alert(1)'));
        self::assertTrue($url_verification->isInternal('/path/to/feature'));
        self::assertTrue($url_verification->isInternal('?report=111'));
        self::assertFalse(
            $url_verification->isInternal('http://' . ForgeConfig::get('sys_default_domain') . '/smthing')
        );
        self::assertTrue(
            $url_verification->isInternal('https://' . ForgeConfig::get('sys_default_domain') . '/smthing')
        );

        self::assertFalse($url_verification->isInternal('//example.com'));
        self::assertFalse($url_verification->isInternal('/\example.com'));
        self::assertFalse($url_verification->isInternal(
            'https://' . ForgeConfig::get('sys_default_domain') . '@evil.example.com'
        ));
    }
}
