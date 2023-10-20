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

namespace Tuleap\User\Account;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;

final class UserWellKnownChangePasswordControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&EventDispatcherInterface
     */
    private $event_dispatcher;
    /**
     * @var UserWellKnownChangePasswordController
     */
    private $controller;

    protected function setUp(): void
    {
        $this->user_manager     = $this->createMock(\UserManager::class);
        $this->event_dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->controller       = new UserWellKnownChangePasswordController(
            $this->user_manager,
            $this->event_dispatcher,
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            $this->createMock(EmitterInterface::class)
        );
    }

    public function testRedirectsUserToChangePasswordPage(): void
    {
        $current_user = $this->createMock(PFUser::class);
        $current_user->method('isAnonymous')->willReturn(false);
        $current_user->method('getUserPw')->willReturn('some_password_hash');
        $this->user_manager->method('getCurrentUser')->willReturn($current_user);
        $this->event_dispatcher->method('dispatch')->with(self::isInstanceOf(PasswordPreUpdateEvent::class))
            ->willReturn(new PasswordPreUpdateEvent($current_user));

        \ForgeConfig::set('sys_default_domain', 'example.com');

        $response = $this->controller->handle(new NullServerRequest());

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('https://example.com/account/security', $response->getHeaderLine('Location'));
    }

    public function testThrowsANotFoundWhenUserIsNotLoggedIn(): void
    {
        $this->user_manager->method('getCurrentUser')->willReturn(UserTestBuilder::anAnonymousUser()->build());

        $response = $this->controller->handle(new NullServerRequest());
        self::assertEquals(404, $response->getStatusCode());
    }

    public function testThrowsANotFoundWhenTheUserCannotChangeItsPassword(): void
    {
        $current_user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->method('getCurrentUser')->willReturn($current_user);
        $password_pre_update_event = new PasswordPreUpdateEvent($current_user);
        $password_pre_update_event->forbidUserToChangePassword();
        $this->event_dispatcher->method('dispatch')->with(self::isInstanceOf(PasswordPreUpdateEvent::class))
            ->willReturn($password_pre_update_event);

        $response = $this->controller->handle(new NullServerRequest());
        self::assertEquals(404, $response->getStatusCode());
    }
}
