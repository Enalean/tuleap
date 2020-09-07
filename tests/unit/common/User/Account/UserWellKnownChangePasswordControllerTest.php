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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\UserTestBuilder;

final class UserWellKnownChangePasswordControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|EventDispatcherInterface
     */
    private $event_dispatcher;
    /**
     * @var UserWellKnownChangePasswordController
     */
    private $controller;

    protected function setUp(): void
    {
        $this->user_manager     = \Mockery::mock(\UserManager::class);
        $this->event_dispatcher = \Mockery::mock(EventDispatcherInterface::class);
        $this->controller       = new UserWellKnownChangePasswordController(
            $this->user_manager,
            $this->event_dispatcher,
            HTTPFactoryBuilder::responseFactory(),
            HTTPFactoryBuilder::streamFactory(),
            \Mockery::mock(EmitterInterface::class)
        );
    }

    public function testRedirectsUserToChangePasswordPage(): void
    {
        $current_user = \Mockery::mock(PFUser::class);
        $current_user->shouldReceive('isAnonymous')->andReturn(false);
        $current_user->shouldReceive('getUserPw')->andReturn('some_password_hash');
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($current_user);
        $this->event_dispatcher->shouldReceive('dispatch')->with(\Mockery::type(PasswordPreUpdateEvent::class))
            ->andReturn(new PasswordPreUpdateEvent($current_user));

        \ForgeConfig::set('sys_https_host', 'example.com');

        $response = $this->controller->handle(new NullServerRequest());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('https://example.com/account/security', $response->getHeaderLine('Location'));
    }

    public function testThrowsANotFoundWhenUserIsNotLoggedIn(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn(UserTestBuilder::anAnonymousUser()->build());

        $response = $this->controller->handle(new NullServerRequest());
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testThrowsANotFoundWhenTheUserCannotChangeItsPassword(): void
    {
        $current_user = UserTestBuilder::aUser()->withId(102)->build();
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($current_user);
        $password_pre_update_event = new PasswordPreUpdateEvent($current_user);
        $password_pre_update_event->forbidUserToChangePassword();
        $this->event_dispatcher->shouldReceive('dispatch')->with(\Mockery::type(PasswordPreUpdateEvent::class))
            ->andReturn($password_pre_update_event);

        $response = $this->controller->handle(new NullServerRequest());
        $this->assertEquals(404, $response->getStatusCode());
    }
}
