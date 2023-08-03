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
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalLanguageMock;

    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tuleap\User\PasswordVerifier
     */
    private $password_verifier;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|User_PasswordExpirationChecker
     */
    private $password_expiration_checker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|PasswordHandler
     */
    private $password_handler;
    private $login_manager;

    protected function setUp(): void
    {
        $this->event_manager               = new EventManager();
        $this->user_manager                = \Mockery::spy(\UserManager::class);
        $this->password_verifier           = \Mockery::spy(\Tuleap\User\PasswordVerifier::class);
        $this->password_expiration_checker = Mockery::spy(\User_PasswordExpirationChecker::class);
        $this->password_handler            = \Mockery::spy(\PasswordHandler::class);
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

        $this->user_manager->shouldReceive('getUserByUserName')->once();

        $this->login_manager->authenticate('john', new ConcealedString('password'));

        self::assertTrue($plugin->before_called);
        self::assertFalse($plugin->after_called);
        self::assertTrue($plugin->auth_succeded_called);
    }

    public function testItUsesDbAuthIfPluginDoesntAnswer(): void
    {
        $plugin = $this->getCatchEventsPlugin();

        $this->password_verifier->shouldReceive('verifyPassword')->andReturns(true);
        $this->user_manager->shouldReceive('getUserByUserName')->with('john')->times(2)->andReturns($this->buildUser(PFUser::STATUS_ACTIVE));

        $this->login_manager->authenticate('john', new ConcealedString('password'));

        self::assertTrue($plugin->before_called);
        self::assertTrue($plugin->after_called);
        self::assertTrue($plugin->auth_succeded_called);
    }

    public function testItThrowsAnExceptionWhenUserIsNotFound(): void
    {
        $this->getCatchEventsPlugin();

        $this->expectException(\User_InvalidPasswordException::class);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns(null);
        $this->login_manager->authenticate('john', new ConcealedString('password'));
    }

    public function testItThrowsAnExceptionWhenPasswordIsWrong(): void
    {
        $this->getCatchEventsPlugin();

        $this->expectException(\User_InvalidPasswordWithUserException::class);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->buildUser(PFUser::STATUS_ACTIVE));
        $this->login_manager->authenticate('john', new ConcealedString('wrong_password'));
    }

    public function testItThrowsAnExceptionWithUserWhenPasswordIsWrong(): void
    {
        $this->getCatchEventsPlugin();
        $exception_catched = false;
        $user              = $this->buildUser(PFUser::STATUS_ACTIVE);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($user);
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
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($user);
        $this->password_verifier->shouldReceive('verifyPassword')->andReturns(true);

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
        $this->password_verifier->shouldReceive('verifyPassword')->andReturns(true);
        $user = $this->buildUser(PFUser::STATUS_ACTIVE);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($user);
        self::assertSame(
            $user,
            $this->login_manager->authenticate('john', new ConcealedString('password')),
        );
    }

    public function testItPersistsValidUser(): void
    {
        $user = $this->buildUser(PFUser::STATUS_ACTIVE);

        $this->user_manager->shouldReceive('setCurrentUser')->once();

        $this->login_manager->validateAndSetCurrentUser($user);
    }

    public function testItDoesntPersistUserWithInvalidStatus(): void
    {
        $user = $this->buildUser(PFUser::STATUS_DELETED);

        $this->user_manager->shouldReceive('setCurrentUser')->never();

        $this->expectException(User_StatusDeletedException::class);
        $this->login_manager->validateAndSetCurrentUser($user);
    }

    public function testItVerifiesUserPasswordLifetime(): void
    {
        $user = $this->buildUser(PFUser::STATUS_ACTIVE);

        $this->password_expiration_checker->shouldReceive('checkPasswordLifetime')->with($user)->once();

        $this->login_manager->validateAndSetCurrentUser($user);
    }

    public function testItRaisesAnExceptionIfPluginForbidLogin(): void
    {
        $user = $this->buildUser(PFUser::STATUS_ACTIVE);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($user);
        $this->password_verifier->shouldReceive('verifyPassword')->andReturns(true);

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

        $this->expectException(\User_InvalidPasswordWithUserException::class);

        $this->login_manager->authenticate('john', new ConcealedString('password'));
    }

    public function testItRaisesAnExceptionIfUserHasEnablePasswordlessOnly(): void
    {
        $this->user_manager->shouldReceive('getUserByUserName')->once()->andReturns(UserTestBuilder::aUser()->build());
        $this->user_manager->shouldReceive('isPasswordlessOnly')->andReturns(true);
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
