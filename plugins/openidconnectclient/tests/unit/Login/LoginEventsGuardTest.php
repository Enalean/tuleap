<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Login;

use PFUser;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager;
use Tuleap\Option\Option;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\BeforeStandardLogin;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LoginEventsGuardTest extends TestCase
{
    private UserMappingManager&\PHPUnit\Framework\MockObject\Stub $mapping_manager;
    private LoginEventsGuard $login_events_guard;

    protected function setUp(): void
    {
        $this->mapping_manager    = $this->createStub(UserMappingManager::class);
        $this->login_events_guard = new LoginEventsGuard($this->mapping_manager);
    }

    public function testDoesNothingWhenNoUserIsAssociatedWithTheEvent(): void
    {
        $event = new BeforeStandardLogin('login', new ConcealedString('password'));

        $this->login_events_guard->verifyLoginEvent(
            $event,
            Option::nothing(PFUser::class),
        );

        self::assertTrue($event->isLoginRefused()->isNothing());
    }

    public function testDoesNothingWhenTheUserAccountIsNotLinkedWithAnOIDCProvider(): void
    {
        $this->mapping_manager->method('userHasProvider')->willReturn(false);

        $event = new BeforeStandardLogin('login', new ConcealedString('password'));

        $this->login_events_guard->verifyLoginEvent(
            $event,
            Option::fromValue(UserTestBuilder::anActiveUser()->build()),
        );

        self::assertTrue($event->isLoginRefused()->isNothing());
    }

    public function testRefusesTheLoginWhenTheUserIsAssociatedWithAnOIDCProvider(): void
    {
        $this->mapping_manager->method('userHasProvider')->willReturn(true);

        $event = new BeforeStandardLogin('login', new ConcealedString('password'));

        $this->login_events_guard->verifyLoginEvent(
            $event,
            Option::fromValue(UserTestBuilder::anActiveUser()->build()),
        );

        self::assertTrue($event->isLoginRefused()->isValue());
    }
}
