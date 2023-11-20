<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

final class URLVerificationPermissionsOverriderAnonymousPlatformTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    private $url_verification;
    private $server;

    protected function setUp(): void
    {
        parent::setUp();


        $event_manager = $this->createMock(\EventManager::class);

        $this->url_verification = $this->createPartialMock(\URLVerification::class, [
            'getEventManager',
            'getCurrentUser',
        ]);
        $this->url_verification->method('getEventManager')->willReturn($event_manager);
        $fixtures = dirname(__FILE__) . '/_fixtures';
        $GLOBALS['Language']->method('getContent')->willReturn($fixtures . '/empty.txt');

        $this->server = ['SERVER_NAME' => 'example.com'];

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
    }

    private function getScriptChunk(): ?string
    {
        $this->url_verification->verifyRequest($this->server);
        $chunks = $this->url_verification->getUrlChunks();
        return $chunks['script'] ?? null;
    }

    public function testItLetAnonymousAccessLogin(): void
    {
        $this->server['SCRIPT_NAME'] = '/account/login.php';
        $this->url_verification->method('getCurrentUser')->willReturn(CurrentUserWithLoggedInInformation::fromAnonymous(new AnonymousUserTestProvider()));

        self::assertEquals(null, $this->getScriptChunk());
    }

    public function testItLetAuthenticatedAccessPages(): void
    {
        $this->server['SCRIPT_NAME'] = '';
        $this->url_verification->method('getCurrentUser')->willReturn(CurrentUserWithLoggedInInformation::fromLoggedInUser(UserTestBuilder::anActiveUser()->build()));

        self::assertEquals(null, $this->getScriptChunk());
    }
}
