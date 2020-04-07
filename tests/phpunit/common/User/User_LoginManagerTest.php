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

use Tuleap\User\UserAuthenticationSucceeded;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class User_LoginManagerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalLanguageMock;

    /**
     * @var EventManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
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
        parent::setUp();
        $this->event_manager     = Mockery::mock(EventManager::class);
        $this->user_manager      = \Mockery::spy(\UserManager::class);
        $this->password_verifier = \Mockery::spy(\Tuleap\User\PasswordVerifier::class);
        $this->password_expiration_checker = Mockery::spy(\User_PasswordExpirationChecker::class);
        $this->password_handler = \Mockery::spy(\PasswordHandler::class);
        $this->login_manager     = new User_LoginManager(
            $this->event_manager,
            $this->user_manager,
            $this->password_verifier,
            $this->password_expiration_checker,
            $this->password_handler
        );
    }

    public function testItDelegatesAuthenticationToPlugin(): void
    {
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->buildUser(PFUser::STATUS_ACTIVE));
        $this->password_verifier->shouldReceive('verifyPassword')->andReturns(true);

        $this->event_manager->shouldReceive('processEvent')->with(
            Event::SESSION_BEFORE_LOGIN,
            [
                'loginname' => 'john',
                'passwd'  => 'password',
                'auth_success' => false,
                'auth_user_id' => null,
                'auth_user_status' => null
            ]
        )->once();
        $this->event_manager->shouldReceive('processEvent')->with(Event::SESSION_AFTER_LOGIN, Mockery::any())->once();
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function ($hook) {
            return $hook instanceof \Tuleap\User\UserAuthenticationSucceeded;
        }))->once();

        $this->login_manager->authenticate('john', 'password');
    }

    public function testItUsesDbAuthIfPluginDoesntAnswer(): void
    {
        $this->event_manager->shouldReceive('processEvent')->times(3);
        $this->password_verifier->shouldReceive('verifyPassword')->andReturns(true);
        $this->user_manager->shouldReceive('getUserByUserName')->with('john')->once()->andReturns($this->buildUser(PFUser::STATUS_ACTIVE));

        $this->login_manager->authenticate('john', 'password');
    }

    public function testItThrowsAnExceptionWhenUserIsNotFound(): void
    {
        $this->event_manager->shouldReceive('processEvent')->once();
        $this->expectException(\User_InvalidPasswordException::class);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns(null);
        $this->login_manager->authenticate('john', 'password');
    }

    public function testItThrowsAnExceptionWhenPasswordIsWrong(): void
    {
        $this->event_manager->shouldReceive('processEvent')->once();
        $this->expectException(\User_InvalidPasswordWithUserException::class);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($this->buildUser(PFUser::STATUS_ACTIVE));
        $this->login_manager->authenticate('john', 'wrong_password');
    }

    public function testItThrowsAnExceptionWithUserWhenPasswordIsWrong(): void
    {
        $this->event_manager->shouldReceive('processEvent')->once();
        $exception_catched = false;
        $user = $this->buildUser(PFUser::STATUS_ACTIVE);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($user);
        try {
            $this->login_manager->authenticate('john', 'wrong_password');
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

        $this->event_manager->shouldReceive('processEvent')->with(Event::SESSION_BEFORE_LOGIN, Mockery::any())->once();
        $this->event_manager->shouldReceive('processEvent')->with(
            Event::SESSION_AFTER_LOGIN,
            [
                'user' => $user,
                'allow_codendi_login'  => true
            ]
        )->once();
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function ($hook) {
            return $hook instanceof \Tuleap\User\UserAuthenticationSucceeded;
        }))->once();

        $this->login_manager->authenticate('john', 'password');
    }

    public function testItReturnsTheUserOnSuccess(): void
    {
        $this->event_manager->shouldReceive('processEvent')->times(3);
        $this->password_verifier->shouldReceive('verifyPassword')->andReturns(true);
        $user = $this->buildUser(PFUser::STATUS_ACTIVE);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($user);
        $this->assertEquals(
            $this->login_manager->authenticate('john', 'password'),
            $user
        );
    }

    public function testItPersistsValidUser(): void
    {
        $user = $this->buildUser(PFUser::STATUS_ACTIVE);

        $this->user_manager->shouldReceive('setCurrentUser')->with($user)->once();

        $this->login_manager->validateAndSetCurrentUser($user);
    }

    public function testItDoesntPersistUserWithInvalidStatus(): void
    {
        $user = $this->buildUser(PFUser::STATUS_DELETED);

        $this->user_manager->shouldReceive('setCurrentUser')->with($user)->never();

        $this->expectException(User_StatusDeletedException::class);
        $this->login_manager->validateAndSetCurrentUser($user);
    }

    public function testItVerifiesUserPasswordLifetime(): void
    {
        $user = $this->buildUser(PFUser::STATUS_ACTIVE);

        $this->password_expiration_checker->shouldReceive('checkPasswordLifetime')->with($user)->once();

        $this->login_manager->validateAndSetCurrentUser($user);
    }

    public function testItDoesntUseDbAuthIfPluginAuthenticate(): void
    {
        $this->user_manager->shouldReceive('getUserById')->with(105)->andReturns($this->buildUser(PFUser::STATUS_ACTIVE))->once();
        $this->event_manager->shouldReceive('processEvent')->with(
            Event::SESSION_BEFORE_LOGIN,
            Mockery::on(
                static function (array $params): bool {
                    $params['auth_success'] = true;
                    $params['auth_user_id'] = 105;

                    return true;
                }
            )
        )->once();
        $this->event_manager->shouldReceive('processEvent')
            ->with(Mockery::type(UserAuthenticationSucceeded::class))
            ->once();


        $this->user_manager->shouldReceive('getUserByUserName')->never();
        $this->login_manager->authenticate('john', 'password');
    }

    public function testItRaisesAnExceptionIfPluginForbidLogin(): void
    {
        $this->expectException(\User_InvalidPasswordWithUserException::class);
        $user = $this->buildUser(PFUser::STATUS_ACTIVE);
        $this->user_manager->shouldReceive('getUserByUserName')->andReturns($user);

        $this->event_manager->shouldReceive('processEvent')->with(
            Event::SESSION_BEFORE_LOGIN,
            Mockery::on(
                static function (array $params): bool {
                    $params['allow_codendi_login'] = false;

                    return true;
                }
            )
        )->once();

        $this->login_manager->authenticate('john', 'password');
    }

    private function buildUser(string $status): PFUser
    {
        return new PFUser(['status' => $status, 'password' => 'password']);
    }
}
