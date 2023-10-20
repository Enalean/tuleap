<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\AfterLocalStandardLogin;
use Tuleap\User\BeforeStandardLogin;
use Tuleap\User\UserAuthenticationSucceeded;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class User_LoginManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Tuleap\GlobalLanguageMock;

    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\User\PasswordVerifier
     */
    private $password_verifier;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&User_PasswordExpirationChecker
     */
    private $password_expiration_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&PasswordHandler
     */
    private $password_handler;
    private User_LoginManager $login_manager;

    protected function setUp(): void
    {
        $this->event_manager               = new EventManager();
        $this->user_manager                = $this->createMock(\UserManager::class);
        $this->password_verifier           = $this->createMock(\Tuleap\User\PasswordVerifier::class);
        $this->password_expiration_checker = $this->createMock(\User_PasswordExpirationChecker::class);
        $this->password_handler            = $this->createMock(\PasswordHandler::class);
        $this->login_manager               = new User_LoginManager(
            $this->event_manager,
            $this->user_manager,
            $this->user_manager,
            $this->password_verifier,
            $this->password_expiration_checker,
            $this->password_handler
        );
        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    public function testItDelegatesAuthenticationToPlugin(): void
    {
        $plugin = new class extends \Plugin {
            public $before_called        = false;
            public $after_called         = false;
            public $auth_succeded_called = false;

            public function beforeLogin(BeforeStandardLogin $event): void
            {
                if ($event->getLoginName() === 'john' && (string) $event->getPassword() === 'password') {
                    $this->before_called = true;
                    $event->setUser(UserTestBuilder::aUser()->withStatus(PFUser::STATUS_ACTIVE)->build());
                }
            }

            public function afterLocalLogin(AfterLocalStandardLogin $event): void
            {
                $this->after_called = true;
            }

            public function userAuthenticationSucceeded(UserAuthenticationSucceeded $event): void
            {
                $this->auth_succeded_called = true;
            }
        };
        $this->addListeners($plugin);

        $this->user_manager->expects(self::once())->method('getUserByUserName');

        $this->login_manager->authenticate('john', new ConcealedString('password'));

        self::assertTrue($plugin->before_called);
        self::assertFalse($plugin->after_called);
        self::assertTrue($plugin->auth_succeded_called);
    }

    public function testItUsesDbAuthIfPluginDoesntAnswer(): void
    {
        $plugin = $this->getCatchEventsPlugin();

        $this->password_verifier->method('verifyPassword')->willReturn(true);
        $this->user_manager->method('isPasswordlessOnly')->willReturn(false);
        $this->password_handler->method('isPasswordNeedRehash')->willReturn(false);

        $this->user_manager->expects(self::exactly(2))->method('getUserByUserName')->with('john')->willReturn($this->buildUser(PFUser::STATUS_ACTIVE));

        $this->login_manager->authenticate('john', new ConcealedString('password'));

        self::assertTrue($plugin->before_called);
        self::assertTrue($plugin->after_called);
        self::assertTrue($plugin->auth_succeded_called);
    }

    public function testItThrowsAnExceptionWhenUserIsNotFound(): void
    {
        $this->getCatchEventsPlugin();

        $this->expectException(\User_InvalidPasswordException::class);
        $this->user_manager->method('getUserByUserName')->willReturn(null);
        $this->login_manager->authenticate('john', new ConcealedString('password'));
    }

    public function testItThrowsAnExceptionWhenPasswordIsWrong(): void
    {
        $this->getCatchEventsPlugin();

        $this->expectException(\User_InvalidPasswordWithUserException::class);

        $this->user_manager->method('getUserByUserName')->willReturn($this->buildUser(PFUser::STATUS_ACTIVE));
        $this->password_verifier->method('verifyPassword')->willReturn(false);
        $this->user_manager->method('isPasswordlessOnly')->willReturn(false);
        $this->password_handler->method('isPasswordNeedRehash')->willReturn(false);

        $this->login_manager->authenticate('john', new ConcealedString('wrong_password'));
    }

    public function testItThrowsAnExceptionWithUserWhenPasswordIsWrong(): void
    {
        $this->getCatchEventsPlugin();
        $exception_catched = false;
        $user              = $this->buildUser(PFUser::STATUS_ACTIVE);
        $this->user_manager->method('getUserByUserName')->willReturn($user);
        $this->password_verifier->method('verifyPassword')->willReturn(false);
        $this->user_manager->method('isPasswordlessOnly')->willReturn(false);
        $this->password_handler->method('isPasswordNeedRehash')->willReturn(false);
        try {
            $this->login_manager->authenticate('john', new ConcealedString('wrong_password'));
        } catch (User_InvalidPasswordWithUserException $exception) {
            $this->assertEquals($exception->getUser(), $user);
            $exception_catched = true;
        }
        $this->assertTrue($exception_catched);
    }

    public function testItAsksPluginIfDbAuthIsAuthorizedForUser(): void
    {
        $user = $this->buildUser(PFUser::STATUS_ACTIVE);
        $this->user_manager->method('getUserByUserName')->willReturn($user);
        $this->password_verifier->method('verifyPassword')->willReturn(true);
        $this->user_manager->method('isPasswordlessOnly')->willReturn(false);
        $this->password_handler->method('isPasswordNeedRehash')->willReturn(false);

        $plugin = new class extends \Plugin {
            public $before_called        = false;
            public $after_called         = false;
            public $auth_succeded_called = false;
            public $user;

            public function beforeLogin(BeforeStandardLogin $event): void
            {
                $this->before_called = true;
            }

            public function afterLocalLogin(AfterLocalStandardLogin $event): void
            {
                $this->after_called = true;
                $this->user         = $event->user;
            }

            public function userAuthenticationSucceeded(UserAuthenticationSucceeded $event): void
            {
                $this->auth_succeded_called = true;
            }
        };
        $this->addListeners($plugin);


        $this->login_manager->authenticate('john', new ConcealedString('password'));
        self::assertTrue($plugin->before_called);
        self::assertTrue($plugin->after_called);
        self::assertTrue($plugin->auth_succeded_called);
        self::assertSame($user, $plugin->user);
    }

    public function testItReturnsTheUserOnSuccess(): void
    {
        $this->getCatchEventsPlugin();
        $this->password_verifier->method('verifyPassword')->willReturn(true);
        $this->user_manager->method('isPasswordlessOnly')->willReturn(false);
        $this->password_handler->method('isPasswordNeedRehash')->willReturn(false);
        $user = $this->buildUser(PFUser::STATUS_ACTIVE);
        $this->user_manager->method('getUserByUserName')->willReturn($user);
        self::assertSame(
            $user,
            $this->login_manager->authenticate('john', new ConcealedString('password')),
        );
    }

    public function testItPersistsValidUser(): void
    {
        $user = $this->buildUser(PFUser::STATUS_ACTIVE);

        $this->password_expiration_checker->method('checkPasswordLifetime');

        $this->user_manager->expects(self::once())->method('setCurrentUser');

        $this->login_manager->validateAndSetCurrentUser($user);
    }

    public function testItDoesntPersistUserWithInvalidStatus(): void
    {
        $user = $this->buildUser(PFUser::STATUS_DELETED);

        $this->user_manager->expects(self::never())->method('setCurrentUser');

        $this->expectException(User_StatusDeletedException::class);
        $this->login_manager->validateAndSetCurrentUser($user);
    }

    public function testItVerifiesUserPasswordLifetime(): void
    {
        $user = $this->buildUser(PFUser::STATUS_ACTIVE);

        $this->password_expiration_checker->expects(self::once())->method('checkPasswordLifetime')->with($user);
        $this->user_manager->expects(self::once())->method('setCurrentUser');

        $this->login_manager->validateAndSetCurrentUser($user);
    }

    public function testItRaisesAnExceptionIfPluginForbidLogin(): void
    {
        $user = $this->buildUser(PFUser::STATUS_ACTIVE);
        $this->user_manager->method('getUserByUserName')->willReturn($user);
        $this->password_verifier->method('verifyPassword')->willReturn(true);
        $this->user_manager->method('isPasswordlessOnly')->willReturn(false);
        $this->password_handler->method('isPasswordNeedRehash')->willReturn(false);

        $plugin = new class extends \Plugin {
            public $before_called        = false;
            public $after_called         = false;
            public $auth_succeded_called = false;

            public function beforeLogin(BeforeStandardLogin $event): void
            {
                $this->before_called = true;
            }

            public function afterLocalLogin(AfterLocalStandardLogin $event): void
            {
                $this->after_called = true;
                $event->refuseLogin("nope");
            }

            public function userAuthenticationSucceeded(UserAuthenticationSucceeded $event): void
            {
                $this->auth_succeded_called = true;
            }
        };
        $this->addListeners($plugin);

        $this->expectException(\User_InvalidPasswordWithUserException::class);
        $this->expectExceptionMessage('nope');

        $this->login_manager->authenticate('john', new ConcealedString('password'));
    }

    public function testItRaisesAnExceptionIfAPluginForbidLoginOfAnotherPlugin(): void
    {
        $plugin1 = new class extends \Plugin {
            public function beforeLogin(BeforeStandardLogin $event): void
            {
                $event->setUser(UserTestBuilder::aUser()->withStatus(PFUser::STATUS_ACTIVE)->build());
            }
        };
        $this->event_manager->addListener(BeforeStandardLogin::NAME, $plugin1, BeforeStandardLogin::NAME, false);

        $plugin2 = new class extends \Plugin {
            public function userAuthenticationSucceeded(UserAuthenticationSucceeded $event): void
            {
                $event->refuseLogin();
            }
        };
        $this->event_manager->addListener(UserAuthenticationSucceeded::NAME, $plugin2, UserAuthenticationSucceeded::NAME, false);

        $user = $this->buildUser(PFUser::STATUS_ACTIVE);
        $this->user_manager->method('getUserByUserName')->willReturn($user);
        $this->password_verifier->method('verifyPassword')->willReturn(true);
        $this->user_manager->method('isPasswordlessOnly')->willReturn(false);
        $this->password_handler->method('isPasswordNeedRehash')->willReturn(false);

        $this->expectException(\User_InvalidPasswordWithUserException::class);

        $this->login_manager->authenticate('john', new ConcealedString('password'));
    }

    public function testItRaisesAnExceptionIfUserHasEnablePasswordlessOnly(): void
    {
        $this->user_manager->expects(self::once())->method('getUserByUserName')->willReturn(UserTestBuilder::aUser()->build());
        $this->user_manager->method('isPasswordlessOnly')->willReturn(true);
        self::expectException(User_InvalidPasswordWithUserException::class);

        $this->login_manager->authenticate('john', new ConcealedString('password'));
    }

    private function buildUser(string $status): PFUser
    {
        return new PFUser(['status' => $status, 'password' => 'password', 'user_id' => 852]);
    }

    private function getCatchEventsPlugin(): \Plugin
    {
        return $this->addListeners(
            new class extends \Plugin {
                public $before_called        = false;
                public $after_called         = false;
                public $auth_succeded_called = false;

                public function beforeLogin(BeforeStandardLogin $event): void
                {
                    $this->before_called = true;
                }

                public function afterLocalLogin(AfterLocalStandardLogin $event): void
                {
                    $this->after_called = true;
                }

                public function userAuthenticationSucceeded(UserAuthenticationSucceeded $event): void
                {
                    $this->auth_succeded_called = true;
                }
            }
        );
    }

    private function addListeners(\Plugin $plugin): \Plugin
    {
        $this->event_manager->addListener(BeforeStandardLogin::NAME, $plugin, BeforeStandardLogin::NAME, false);
        $this->event_manager->addListener(AfterLocalStandardLogin::NAME, $plugin, AfterLocalStandardLogin::NAME, false);
        $this->event_manager->addListener(UserAuthenticationSucceeded::NAME, $plugin, UserAuthenticationSucceeded::NAME, false);
        return $plugin;
    }
}
