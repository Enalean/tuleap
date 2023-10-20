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

use CSRFSynchronizerToken;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Password\PasswordSanityChecker;
use Tuleap\Request\ForbiddenException;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class DisplaySecurityControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;

    /**
     * @var CSRFSynchronizerToken&MockObject
     */
    private $csrf_token;
    /**
     * @var DisplaySecurityController
     */
    private $controller;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var EventDispatcherInterface
     */
    private $event_manager;

    public function setUp(): void
    {
        $this->event_manager = new class implements EventDispatcherInterface {
            private $password_change = true;

            public function dispatch(object $event)
            {
                if ($event instanceof PasswordPreUpdateEvent) {
                    if (! $this->password_change) {
                        $event->forbidUserToChangePassword();
                    }
                }
                return $event;
            }

            public function disablePasswordChange()
            {
                $this->password_change = false;
            }
        };

        $password_sanity_checker = $this->createMock(PasswordSanityChecker::class);
        $password_sanity_checker->method('getValidators')->willReturn([]);

        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('getUserAccessInfo')->willReturn(['last_auth_success' => 1, 'last_auth_failure' => 1, 'nb_auth_failure' => 1, 'prev_auth_success' => 1]);

        $this->csrf_token = $this->createMock(CSRFSynchronizerToken::class);
        $this->controller = new DisplaySecurityController(
            $this->event_manager,
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            $this->csrf_token,
            $password_sanity_checker,
            $user_manager,
        );
        $language         = $this->createStub(\BaseLanguage::class);
        $language->method('getText')->willReturn('');
        $this->user = UserTestBuilder::aUser()
            ->withId(110)
            ->withUserName('alice')
            ->withLanguage($language)
            ->build();
        $this->user->setUserPw('some_password_hash');
    }

    public function testItThrowExceptionForAnonymous(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withAnonymousUser()->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItRendersThePageWithSession(): void
    {
        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();
        self::assertStringContainsString('Session', $output);
    }

    public function testItRendersThePageWithPasswords(): void
    {
        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();
        self::assertStringContainsString('Update password', $output);

        self::assertStringContainsString('name="current_password"', $output);
        self::assertStringContainsString('name="new_password"', $output);
        self::assertStringContainsString('name="repeat_new_password"', $output);
    }

    public function testItDoesntRenderPasswordUpdateWhenItsNotAllowedForUser(): void
    {
        $this->event_manager->disablePasswordChange();

        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();
        self::assertStringNotContainsString('Update password', $output);

        self::assertStringNotContainsString('name="current_password"', $output);
        self::assertStringNotContainsString('name="new_password"', $output);
        self::assertStringNotContainsString('name="repeat_new_password"', $output);
    }
}
