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
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Password\PasswordSanityChecker;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\Password\Change\PasswordChanger;
use Tuleap\User\PasswordVerifier;
use User_StatusInvalidException;
use User_UserStatusManager;

final class UpdatePasswordControllerTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \CSRFSynchronizerToken|M\LegacyMockInterface|M\MockInterface
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
     * @var M\LegacyMockInterface|M\MockInterface|PasswordVerifier
     */
    private $password_verifier;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|User_UserStatusManager
     */
    private $user_status_manager;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|PasswordChanger
     */
    private $password_changer;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|PasswordSanityChecker
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
            private $password_change = true;
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

            public function disablePasswordChange()
            {
                $this->password_change = false;
            }

            public function disableNeedOfOldPassword()
            {
                $this->old_password_required = false;
            }
        };

        $this->csrf_token = M::mock(\CSRFSynchronizerToken::class);
        $this->csrf_token->shouldReceive('check')->byDefault();

        $this->password_verifier = M::mock(PasswordVerifier::class);
        $this->password_verifier->shouldReceive('verifyPassword')->byDefault();

        $this->user_status_manager = M::mock(User_UserStatusManager::class);
        $this->user_status_manager->shouldReceive('checkStatus')->byDefault();

        $this->password_changer        = M::mock(PasswordChanger::class);
        $this->password_sanity_checker = M::mock(PasswordSanityChecker::class);

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
    }

    public function testItThrowsExceptionWhenUserIsAnonymous(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->password_changer->shouldNotReceive('changePassword');

        $this->controller->process(
            HTTPRequestBuilder::get()->withAnonymousUser()->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItChecksCSRFToken(): void
    {
        $this->csrf_token->shouldReceive('check')->with('/account/security')->once();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItCannotUpdatePasswordWhenUserStatusIsNotValid(): void
    {
        $this->user_status_manager->shouldReceive('checkStatus')->andThrow(new User_StatusInvalidException());

        $this->password_changer->shouldNotReceive('changePassword');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('current_password', 'the_old_password')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItCannotUpdateIfOldPasswordIsNotVerified(): void
    {
        $this->password_verifier->shouldReceive('verifyPassword')->with($this->user, 'the_old_password')->once()->andReturnFalse();

        $this->password_changer->shouldNotReceive('changePassword');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('current_password', 'the_old_password')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItCannotUpdateWhenNewPasswordDoesntMatch(): void
    {
        $this->password_verifier->shouldReceive('verifyPassword')->with($this->user, 'the_old_password')->andReturnTrue();

        $this->password_changer->shouldNotReceive('changePassword');

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
        $this->password_verifier->shouldReceive('verifyPassword')->with($this->user, 'the_old_password')->andReturnTrue();

        $this->password_changer->shouldNotReceive('changePassword');

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
        $this->password_verifier->shouldReceive('verifyPassword')->with($this->user, 'the_old_password')->andReturnTrue();
        $this->password_verifier->shouldReceive('verifyPassword')->with($this->user, 'the_new_password')->andReturnTrue();

        $this->password_sanity_checker->shouldReceive('check')->with('the_new_password')->andReturnFalse();
        $this->password_sanity_checker->shouldReceive('getErrors')->andReturn(['some error about been pwned']);

        $this->password_changer->shouldNotReceive('changePassword');

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
        $this->assertCount(1, $feedback);
        $this->assertEquals(Feedback::ERROR, $feedback[0]['level']);
        $this->assertEquals('some error about been pwned', $feedback[0]['message']);
    }

    public function testItReportsAnErrorIfPasswordChangeFails(): void
    {
        $this->password_verifier->shouldReceive('verifyPassword')->with($this->user, 'the_old_password')->andReturnTrue();
        $this->password_verifier->shouldReceive('verifyPassword')->with($this->user, 'the_new_password')->andReturnTrue();
        $this->password_sanity_checker->shouldReceive('check')->with('the_new_password')->andReturnTrue();

        $this->password_changer->shouldReceive('changePassword')->andThrow(new \Exception());

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
        $this->assertCount(1, $feedback);
        $this->assertEquals(Feedback::ERROR, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('could not update password', $feedback[0]['message']);
    }

    public function testItFailsWhenPluginDisablePasswordChange(): void
    {
        $this->event_manager->disablePasswordChange();

        $this->password_verifier->shouldNotReceive('verifyPassword');
        $this->password_changer->shouldNotReceive('changePassword');

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
        $this->password_verifier->shouldReceive('verifyPassword')->with($this->user, 'the_old_password')->andReturnTrue();
        $this->password_verifier->shouldReceive('verifyPassword')->with($this->user, 'the_new_password')->andReturnTrue();
        $this->password_sanity_checker->shouldReceive('check')->with('the_new_password')->andReturnTrue();

        $this->password_changer->shouldReceive('changePassword')->with($this->user, 'the_new_password')->once();

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
        $this->assertCount(1, $feedback);
        $this->assertEquals(Feedback::INFO, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('success', $feedback[0]['message']);
    }

    public function testAPluginAllowsToChangePasswordWithoutOldPassword(): void
    {
        $this->event_manager->disableNeedOfOldPassword();

        $this->password_verifier->shouldReceive('verifyPassword')->with($this->user, 'the_new_password')->andReturnTrue();
        $this->password_sanity_checker->shouldReceive('check')->with('the_new_password')->andReturnTrue();

        $this->password_changer->shouldReceive('changePassword')->with($this->user, 'the_new_password')->once();

        $this->controller->process(
            HTTPRequestBuilder::get()
                ->withUser($this->user)
                ->withParam('new_password', 'the_new_password')
                ->withParam('repeat_new_password', 'the_new_password')
                ->build(),
            LayoutBuilder::buildWithInspector($this->layout_inspector),
            []
        );

        $feedback = $this->layout_inspector->getFeedback();
        $this->assertCount(1, $feedback);
        $this->assertEquals(Feedback::INFO, $feedback[0]['level']);
        $this->assertStringContainsStringIgnoringCase('success', $feedback[0]['message']);
    }
}
