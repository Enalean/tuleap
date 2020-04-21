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
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Password\PasswordSanityChecker;
use Tuleap\Request\ForbiddenException;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class DisplaySecurityControllerTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use TemporaryTestDirectory;

    /**
     * @var CSRFSynchronizerToken|M\LegacyMockInterface|M\MockInterface
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
            private $old_password_required = true;

            public function dispatch(object $event)
            {
                if ($event instanceof PasswordPreUpdateEvent) {
                    if (! $this->password_change) {
                        $event->forbidUserToChangePassword();
                    }
                    if (! $this->old_password_required) {
                        $event->oldPasswordIsNotRequiredToUpdatePassword();
                    }
                }
                return $event;
            }

            public function disablePasswordChange()
            {
                $this->password_change = false;
            }

            public function disableNeedOfOldPassword()
            {
                $this->old_password_required = false;
            }
        };

        $this->csrf_token   = M::mock(CSRFSynchronizerToken::class);
        $this->controller   = new DisplaySecurityController(
            $this->event_manager,
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            $this->csrf_token,
            M::mock(PasswordSanityChecker::class, ['getValidators' => []]),
            M::mock(\UserManager::class, ['getUserAccessInfo' => ['last_auth_success' => 1, 'last_auth_failure' => 1, 'nb_auth_failure' => 1, 'prev_auth_success' => 1]])
        );
        $this->user = UserTestBuilder::aUser()
            ->withId(110)
            ->withUserName('alice')
            ->withLanguage(M::spy(\BaseLanguage::class))
            ->build();
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
        $this->assertStringContainsString('Session', $output);
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
        $this->assertStringContainsString('Update password', $output);

        $this->assertStringContainsString('name="current_password"', $output);
        $this->assertStringContainsString('name="new_password"', $output);
        $this->assertStringContainsString('name="repeat_new_password"', $output);
    }

    public function testItRendersThePageWithoutTheNeedOfOldPassword(): void
    {
        $this->event_manager->disableNeedOfOldPassword();

        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();
        $this->assertStringContainsString('Update password', $output);

        $this->assertStringNotContainsString('name="current_password"', $output);
        $this->assertStringContainsString('name="new_password"', $output);
        $this->assertStringContainsString('name="repeat_new_password"', $output);
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
        $this->assertStringNotContainsString('Update password', $output);

        $this->assertStringNotContainsString('name="current_password"', $output);
        $this->assertStringNotContainsString('name="new_password"', $output);
        $this->assertStringNotContainsString('name="repeat_new_password"', $output);
    }
}
