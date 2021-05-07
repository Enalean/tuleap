<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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

namespace Tuleap\Webdav;

use ForgeAccess;
use ForgeConfig;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\AccessKey\HTTPBasicAuth\HTTPBasicAuthUserAccessKeyAuthenticator;
use Tuleap\User\AccessKey\HTTPBasicAuth\HTTPBasicAuthUserAccessKeyMisusageException;
use Tuleap\Webdav\Authentication\HeadersSender;

final class WebDAVAuthenticationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|HeadersSender
     */
    private $headers_sender;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|HTTPBasicAuthUserAccessKeyAuthenticator
     */
    private $access_key_authenticator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headers_sender           = Mockery::mock(HeadersSender::class);
        $this->user_manager             = Mockery::mock(\UserManager::class);
        $this->access_key_authenticator = Mockery::mock(HTTPBasicAuthUserAccessKeyAuthenticator::class);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], $_SERVER['REMOTE_ADDR']);
    }

    /**
     * Testing when user gives only the username
     */
    public function testAuthenticateFailureWithOnlyUsername(): void
    {
        $webDAVAuthentication     = \Mockery::mock(\WebDAVAuthentication::class, [$this->user_manager, $this->headers_sender, $this->access_key_authenticator])->makePartial()->shouldAllowMockingProtectedMethods();
        $_SERVER['PHP_AUTH_USER'] = 'username';
        $user                     = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturns(true);
        $webDAVAuthentication->shouldReceive('getUser')->andReturns($user);

        $end_of_execution_exception = new \Exception();
        $this->headers_sender->shouldReceive('sendHeaders')->once()->andThrow($end_of_execution_exception);

        $this->expectExceptionObject($end_of_execution_exception);
        $webDAVAuthentication->authenticate();
    }

    /**
     * Testing when the user gives only the password
     */
    public function testAuthenticateFailureWithOnlyPassword(): void
    {
        $webDAVAuthentication   = \Mockery::mock(\WebDAVAuthentication::class, [$this->user_manager, $this->headers_sender, $this->access_key_authenticator])->makePartial()->shouldAllowMockingProtectedMethods();
        $_SERVER['PHP_AUTH_PW'] = 'password';
        $user                   = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturns(true);
        $webDAVAuthentication->shouldReceive('getUser')->andReturns($user);

        $end_of_execution_exception = new \Exception();
        $this->headers_sender->shouldReceive('sendHeaders')->once()->andThrow($end_of_execution_exception);

        $this->expectExceptionObject($end_of_execution_exception);

        $webDAVAuthentication->authenticate();
    }

    /**
     * Testing when the user gives a wrong username or password
     */
    public function testAuthenticateFailureWithWrongUsernameAndPassword(): void
    {
        $webDAVAuthentication     = \Mockery::mock(\WebDAVAuthentication::class, [$this->user_manager, $this->headers_sender, $this->access_key_authenticator])->makePartial()->shouldAllowMockingProtectedMethods();
        $_SERVER['PHP_AUTH_USER'] = 'username';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $user                     = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturns(true);
        $webDAVAuthentication->shouldReceive('getUser')->andReturns($user);

        $end_of_execution_exception = new \Exception();
        $this->headers_sender->shouldReceive('sendHeaders')->once()->andThrow($end_of_execution_exception);

        $this->expectExceptionObject($end_of_execution_exception);

        $webDAVAuthentication->authenticate();
    }

    /**
     * Testing when the user is authenticated as anonymous
     */
    public function testAuthenticateFailsWithAnonymousUserNotAllowed(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $webDAVAuthentication     = \Mockery::mock(\WebDAVAuthentication::class, [$this->user_manager, $this->headers_sender, $this->access_key_authenticator])->makePartial()->shouldAllowMockingProtectedMethods();
        $_SERVER['PHP_AUTH_USER'] = 'username';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $user                     = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturns(true);
        $webDAVAuthentication->shouldReceive('getUser')->andReturns($user);

        $end_of_execution_exception = new \Exception();
        $this->headers_sender->shouldReceive('sendHeaders')->once()->andThrow($end_of_execution_exception);

        $this->expectExceptionObject($end_of_execution_exception);

        $webDAVAuthentication->authenticate();
    }

    /**
     * Testing when the user is authenticated as anonymous
     */
    public function testAuthenticateFailsWithAnonymousUserAllowed(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $webDAVAuthentication = \Mockery::mock(\WebDAVAuthentication::class, [$this->user_manager, $this->headers_sender, $this->access_key_authenticator])->makePartial()->shouldAllowMockingProtectedMethods();
        $user                 = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturns(true);
        $webDAVAuthentication->shouldReceive('getUser')->andReturns($user);

        $end_of_execution_exception = new \Exception();
        $this->headers_sender->shouldReceive('sendHeaders')->once()->andThrow($end_of_execution_exception);

        $this->expectExceptionObject($end_of_execution_exception);

        $webDAVAuthentication->authenticate();
    }

    /**
     * Testing when the user is authenticated as a registered user
     */
    public function testAuthenticateSuccessWithNotAnonymousUser(): void
    {
        $webDAVAuthentication     = \Mockery::mock(\WebDAVAuthentication::class, [$this->user_manager, $this->headers_sender, $this->access_key_authenticator])->makePartial()->shouldAllowMockingProtectedMethods();
        $_SERVER['PHP_AUTH_USER'] = 'username';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $user                     = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturns(false);
        $webDAVAuthentication->shouldReceive('getUser')->andReturns($user);

        self::assertEquals($user, $webDAVAuthentication->authenticate());
    }

    public function testCanAuthenticateUserWithAnAccessKey(): void
    {
        $webdav_authentication = new \WebDAVAuthentication($this->user_manager, $this->headers_sender, $this->access_key_authenticator);

        $_SERVER['REMOTE_ADDR']   = '2001:db8::3';
        $_SERVER['PHP_AUTH_USER'] = 'username';
        $_SERVER['PHP_AUTH_PW']   = 'tlp.k1.aaaaaa';

        $expected_user = UserTestBuilder::aUser()->withId(102)->withUserName('username')->build();

        $this->access_key_authenticator->shouldReceive('getUser')->andReturn($expected_user);

        $authenticated_user = $webdav_authentication->authenticate();

        self::assertSame($expected_user, $authenticated_user);
    }

    public function testAskForAuthenticationAgainWhenUsernameDoesNotMatchTheUserAssociatedWithTheAccessKey(): void
    {
        $webdav_authentication = new \WebDAVAuthentication($this->user_manager, $this->headers_sender, $this->access_key_authenticator);

        $_SERVER['REMOTE_ADDR']   = '2001:db8::3';
        $_SERVER['PHP_AUTH_USER'] = 'wrong_username';
        $_SERVER['PHP_AUTH_PW']   = 'tlp.k1.aaaaaa';

        $user = UserTestBuilder::aUser()->withId(102)->withUserName('username')->build();

        $this->access_key_authenticator->shouldReceive('getUser')->andThrow(new HTTPBasicAuthUserAccessKeyMisusageException('wrong_username', $user));

        $end_of_execution_exception = new \Exception();
        $this->headers_sender->shouldReceive('sendHeaders')->once()->andThrow($end_of_execution_exception);

        $this->expectExceptionObject($end_of_execution_exception);

        $webdav_authentication->authenticate();
    }
}
