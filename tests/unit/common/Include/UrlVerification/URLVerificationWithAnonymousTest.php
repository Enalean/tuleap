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

use ForgeAccess;
use ForgeConfig;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\AnonymousUserTestProvider;
use Tuleap\User\CurrentUserWithLoggedInInformation;

final class URLVerificationWithAnonymousTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    private $urlVerification;

    protected function setUp(): void
    {
        parent::setUp();

        $em = $this->createMock(\EventManager::class);
        $em->method('processEvent');

        $this->urlVerification = $this->createPartialMock(\URLVerification::class, [
            'getEventManager',
            'getCurrentUser',
        ]);
        $this->urlVerification->method('getEventManager')->willReturn($em);
    }

    private function currentUserIsNotLoggedIn(): void
    {
        $this->urlVerification->method('getCurrentUser')->willReturn(
            CurrentUserWithLoggedInInformation::fromAnonymous(new AnonymousUserTestProvider())
        );
    }

    private function currentUserIsLoggedIn(): void
    {
        $this->urlVerification->method('getCurrentUser')->willReturn(
            CurrentUserWithLoggedInInformation::fromLoggedInUser(UserTestBuilder::anActiveUser()->build())
        );
    }

    public function testVerifyRequestAnonymousWhenScriptException(): void
    {
        $this->currentUserIsNotLoggedIn();
        $server = [
            'SERVER_NAME' => 'example.com',
            'SCRIPT_NAME' => '/account/login.php',
        ];

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        self::assertFalse(isset($chunks['script']));
    }

    public function testVerifyRequestAnonymousWhenAllowed(): void
    {
        $this->currentUserIsNotLoggedIn();
        $server = [
            'SERVER_NAME' => 'example.com',
            'SCRIPT_NAME' => '',
            'REQUEST_URI' => '/',
        ];
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        self::assertFalse(isset($chunks['script']));
    }

    public function testVerifyRequestAuthenticatedWhenAnonymousAllowed(): void
    {
        $this->currentUserIsLoggedIn();

        $server = [
            'SERVER_NAME' => 'example.com',
            'SCRIPT_NAME' => '',
        ];

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        self::assertFalse(isset($chunks['script']));
    }

    public function testVerifyRequestAnonymousWhenNotAllowedAtRoot(): void
    {
        $this->currentUserIsNotLoggedIn();
        $server = [
            'SERVER_NAME' => 'example.com',
            'SCRIPT_NAME' => '',
            'REQUEST_URI' => '/',
        ];

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        ForgeConfig::set('sys_default_domain', 'example.com');

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        self::assertEquals('/account/login.php?return_to=%2Fmy%2F', $chunks['script']);
    }

    public function testVerifyRequestAnonymousWhenNotAllowedWithScript(): void
    {
        $this->currentUserIsNotLoggedIn();
        $server = [
            'SERVER_NAME' => 'example.com',
            'SCRIPT_NAME' => '',
            'REQUEST_URI' => '/script/',
        ];

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        ForgeConfig::set('sys_default_domain', 'example.com');

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        self::assertEquals('/account/login.php?return_to=%2Fscript%2F', $chunks['script']);
    }

    public function testVerifyRequestAnonymousWhenNotAllowedWithLightView(): void
    {
        $this->currentUserIsNotLoggedIn();
        $server = [
            'SERVER_NAME' => 'example.com',
            'SCRIPT_NAME' => '',
            'REQUEST_URI' => '/script?pv=2',
        ];

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        ForgeConfig::set('sys_default_domain', 'example.com');

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        self::assertEquals('/account/login.php?return_to=%2Fscript%3Fpv%3D2&pv=2', $chunks['script']);
    }

    public function testVerifyRequestAuthenticatedWhenAnonymousNotAllowed(): void
    {
        $this->currentUserIsLoggedIn();
        $server = [
            'SERVER_NAME' => 'example.com',
            'SCRIPT_NAME' => '',
        ];

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        self::assertFalse(isset($chunks['script']));
    }
}
