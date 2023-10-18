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

use Feedback;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Password\PasswordSanityChecker;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\Password\Change\PasswordChanger;
use Tuleap\User\PasswordVerifier;
use User_StatusInvalidException;
use User_UserStatusManager;

final class UpdatePasswordControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \CSRFSynchronizerToken&\PHPUnit\Framework\MockObject\MockObject
     */
    private $csrf_token;
    /**
     * @var UpdatePasswordController
     */
    private $controller;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PasswordVerifier
     */
    private $password_verifier;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&User_UserStatusManager
     */
    private $user_status_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PasswordChanger
     */
    private $password_changer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PasswordSanityChecker
     */
    private $password_sanity_checker;
    /**
     * @var LayoutInspector
     */
    private $layout_inspector;
    /**
     * @var EventDispatcherInterface
     */
    private $event_manager;

    protected function setUp(): void
    {
        $this->event_manager = new class implements EventDispatcherInterface {
            private $password_change       = true;
            private $old_password_required = true;

            public function dispatch(object $event)
            {
                assert($event instanceof PasswordPreUpdateEvent);
                if (! $this->password_change) {
                    $event->forbidUserToChangePassword();
                }
                if (! $this->old_password_required) {
                    $event->oldPasswordIsNotRequiredToUpdatePassword();
                }
                return $event;
            }

            public function disablePasswordChange(): void
            {
                $this->password_change = false;
            }
        };

        $this->csrf_token          = $this->createMock(\CSRFSynchronizerToken::class);
        $this->password_verifier   = $this->createMock(PasswordVerifier::class);
        $this->user_status_manager = $this->createMock(User_UserStatusManager::class);

        $this->password_changer        = $this->createMock(PasswordChanger::class);
        $this->password_sanity_checker = $this->createMock(PasswordSanityChecker::class);

        $this->layout_inspector = new LayoutInspector();

        $this->controller = new UpdatePasswordController(
            $this->event_manager,
            $this->csrf_token,
            $this->password_verifier,
            $this->user_status_manager,
            $this->password_changer,
            $this->password_sanity_checker,
        );

        $this->user = UserTestBuilder::aUser()->withId(120)->build();
        $this->user->setUserPw('some_password_hash');
    }

    public function testItThrowsExceptionWhenUserIsAnonymous(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->password_changer->expects(self::never())->method('changePassword');

        $this->controller->process(
            HTTPRequestBuilder::get()->withAnonymousUser()->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItChecksCSRFToken(): void
    {
        $this->csrf_token->expects(self::once())->method('check')->with('/account/security');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItCannotUpdatePasswordWhenUserStatusIsNotValid(): void
    {
        $this->password_verifier->method('verifyPassword');
        $this->csrf_token->method('check');

        $this->user_status_manager->method('checkStatus')->willThrowException(new User_StatusInvalidException());

        $this->password_changer->expects(self::never())->method('changePassword');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('current_password', 'the_old_password')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItCannotUpdateIfOldPasswordIsNotVerified(): void
    {
        $this->user_status_manager->method('checkStatus');
        $this->csrf_token->method('check');

        $this->password_verifier->expects(self::once())->method('verifyPassword')->with(
            $this->user,
            self::callback(
                static function (ConcealedString $password): bool {
                    return $password->isIdenticalTo(new ConcealedString('the_old_password'));
                }
            )
        )->willReturn(false);

        $this->password_changer->expects(self::never())->method('changePassword');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('current_password', 'the_old_password')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItCannotUpdateWhenNewPasswordDoesntMatch(): void
    {
        $this->user_status_manager->method('checkStatus');
        $this->csrf_token->method('check');

        $this->password_verifier->method('verifyPassword')->with($this->user, new ConcealedString('the_old_password'))->willReturn(true);

        $this->password_changer->expects(self::never())->method('changePassword');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()
                ->withUser($this->user)
                ->withParam('current_password', 'the_old_password')
                ->withParam('new_password', 'some new password')
                ->withParam('repeat_new_password', 'some other password')
                ->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItCannotUpdateWhenNewPasswordIsIdenticalToOldPassword(): void
    {
        $this->user_status_manager->method('checkStatus');
        $this->csrf_token->method('check');

        $this->password_verifier->method('verifyPassword')->with($this->user, 'the_old_password')->willReturn(true);

        $this->password_changer->expects(self::never())->method('changePassword');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()
                ->withUser($this->user)
                ->withParam('current_password', 'the_old_password')
                ->withParam('new_password', 'the_old_password')
                ->withParam('repeat_new_password', 'the_old_password')
                ->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItCannotUpdateIfPasswordStrategyIsNotMet(): void
    {
        $this->user_status_manager->method('checkStatus');
        $this->csrf_token->method('check');

        $this->password_verifier->method('verifyPassword')->willReturn(true);

        $this->password_sanity_checker->method('check')->willReturn(false);
        $this->password_sanity_checker->method('getErrors')->willReturn(['some error about been pwned']);

        $this->password_changer->expects(self::never())->method('changePassword');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()
                ->withUser($this->user)
                ->withParam('current_password', 'the_old_password')
                ->withParam('new_password', 'the_new_password')
                ->withParam('repeat_new_password', 'the_new_password')
                ->build(),
            LayoutBuilder::buildWithInspector($this->layout_inspector),
            []
        );

        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(1, $feedback);
        self::assertEquals(Feedback::ERROR, $feedback[0]['level']);
        self::assertEquals('some error about been pwned', $feedback[0]['message']);
    }

    public function testItReportsAnErrorIfPasswordChangeFails(): void
    {
        $this->user_status_manager->method('checkStatus');
        $this->csrf_token->method('check');

        $this->password_verifier->method('verifyPassword')->willReturn(true);
        $this->password_sanity_checker->method('check')->willReturn(true);

        $this->password_changer->method('changePassword')->willThrowException(new \Exception());

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()
                ->withUser($this->user)
                ->withParam('current_password', 'the_old_password')
                ->withParam('new_password', 'the_new_password')
                ->withParam('repeat_new_password', 'the_new_password')
                ->build(),
            LayoutBuilder::buildWithInspector($this->layout_inspector),
            []
        );

        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(1, $feedback);
        self::assertEquals(Feedback::ERROR, $feedback[0]['level']);
        self::assertStringContainsStringIgnoringCase('could not update password', $feedback[0]['message']);
    }

    public function testItFailsWhenPluginDisablePasswordChange(): void
    {
        $this->user_status_manager->method('checkStatus');
        $this->csrf_token->method('check');

        $this->event_manager->disablePasswordChange();

        $this->password_verifier->expects(self::never())->method('verifyPassword');
        $this->password_changer->expects(self::never())->method('changePassword');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()
                ->withUser($this->user)
                ->withParam('current_password', 'the_old_password')
                ->withParam('new_password', 'the_new_password')
                ->withParam('repeat_new_password', 'the_new_password')
                ->build(),
            LayoutBuilder::buildWithInspector($this->layout_inspector),
            []
        );
    }

    public function testItChangesPasswordWithSuccess(): void
    {
        $this->user_status_manager->method('checkStatus');
        $this->csrf_token->method('check');

        $this->password_verifier->method('verifyPassword')->willReturn(true);
        $this->password_sanity_checker->expects(self::once())->method('check')->with(self::callback(
            static function (ConcealedString $str): bool {
                return $str->isIdenticalTo(new ConcealedString('the_new_password'));
            }
        ))->willReturn(true);

        $this->password_changer->expects(self::once())->method('changePassword');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()
                ->withUser($this->user)
                ->withParam('current_password', 'the_old_password')
                ->withParam('new_password', 'the_new_password')
                ->withParam('repeat_new_password', 'the_new_password')
                ->build(),
            LayoutBuilder::buildWithInspector($this->layout_inspector),
            []
        );

        $feedback = $this->layout_inspector->getFeedback();
        self::assertCount(1, $feedback);
        self::assertEquals(Feedback::INFO, $feedback[0]['level']);
        self::assertStringContainsStringIgnoringCase('success', $feedback[0]['message']);
    }
}
