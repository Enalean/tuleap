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
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\AccessKey\HTTPBasicAuth\HTTPBasicAuthUserAccessKeyAuthenticator;
use Tuleap\User\AccessKey\HTTPBasicAuth\HTTPBasicAuthUserAccessKeyMisusageException;
use Tuleap\Webdav\Authentication\HeadersSender;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class WebDAVAuthenticationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    /**
     * @var HeadersSender&\PHPUnit\Framework\MockObject\MockObject
     */
    private $headers_sender;
    /**
     * @var \UserManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\User_LoginManager
     */
    private mixed $login_manager;
    /**
     * @var HTTPBasicAuthUserAccessKeyAuthenticator&\PHPUnit\Framework\MockObject\MockObject
     */
    private $access_key_authenticator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headers_sender           = $this->createMock(HeadersSender::class);
        $this->user_manager             = $this->createMock(\UserManager::class);
        $this->login_manager            = $this->createStub(\User_LoginManager::class);
        $this->access_key_authenticator = $this->createMock(HTTPBasicAuthUserAccessKeyAuthenticator::class);
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
        $webDAVAuthentication = $this->getMockBuilder(\WebDAVAuthentication::class)->setConstructorArgs(
            [$this->user_manager, $this->login_manager, $this->headers_sender, $this->access_key_authenticator]
        )->onlyMethods(['getUser'])->getMock();

        $_SERVER['PHP_AUTH_USER'] = 'username';
        $user                     = UserTestBuilder::anAnonymousUser()->build();
        $webDAVAuthentication->method('getUser')->willReturn($user);

        $end_of_execution_exception = new \Exception();
        $this->headers_sender->expects(self::once())->method('sendHeaders')->willThrowException($end_of_execution_exception);

        $this->expectExceptionObject($end_of_execution_exception);
        $webDAVAuthentication->authenticate();
    }

    /**
     * Testing when the user gives only the password
     */
    public function testAuthenticateFailureWithOnlyPassword(): void
    {
        $webDAVAuthentication = $this->getMockBuilder(\WebDAVAuthentication::class)->setConstructorArgs(
            [$this->user_manager, $this->login_manager, $this->headers_sender, $this->access_key_authenticator]
        )->onlyMethods(['getUser'])->getMock();

        $_SERVER['PHP_AUTH_PW'] = 'password';
        $user                   = UserTestBuilder::anAnonymousUser()->build();
        $webDAVAuthentication->method('getUser')->willReturn($user);

        $end_of_execution_exception = new \Exception();
        $this->headers_sender->expects(self::once())->method('sendHeaders')->willThrowException($end_of_execution_exception);

        $this->expectExceptionObject($end_of_execution_exception);

        $webDAVAuthentication->authenticate();
    }

    /**
     * Testing when the user gives a wrong username or password
     */
    public function testAuthenticateFailureWithWrongUsernameAndPassword(): void
    {
        $webDAVAuthentication = $this->getMockBuilder(\WebDAVAuthentication::class)->setConstructorArgs(
            [$this->user_manager, $this->login_manager, $this->headers_sender, $this->access_key_authenticator]
        )->onlyMethods(['getUser'])->getMock();

        $_SERVER['PHP_AUTH_USER'] = 'username';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $user                     = UserTestBuilder::anAnonymousUser()->build();
        $webDAVAuthentication->method('getUser')->willReturn($user);

        $end_of_execution_exception = new \Exception();
        $this->headers_sender->expects(self::once())->method('sendHeaders')->willThrowException($end_of_execution_exception);

        $this->expectExceptionObject($end_of_execution_exception);

        $webDAVAuthentication->authenticate();
    }

    /**
     * Testing when the user is authenticated as anonymous
     */
    public function testAuthenticateFailsWithAnonymousUserNotAllowed(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $webDAVAuthentication = $this->getMockBuilder(\WebDAVAuthentication::class)->setConstructorArgs(
            [$this->user_manager, $this->login_manager, $this->headers_sender, $this->access_key_authenticator]
        )->onlyMethods(['getUser'])->getMock();

        $_SERVER['PHP_AUTH_USER'] = 'username';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $user                     = UserTestBuilder::anAnonymousUser()->build();
        $webDAVAuthentication->method('getUser')->willReturn($user);

        $end_of_execution_exception = new \Exception();
        $this->headers_sender->expects(self::once())->method('sendHeaders')->willThrowException($end_of_execution_exception);

        $this->expectExceptionObject($end_of_execution_exception);

        $webDAVAuthentication->authenticate();
    }

    /**
     * Testing when the user is authenticated as anonymous
     */
    public function testAuthenticateFailsWithAnonymousUserAllowed(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $webDAVAuthentication = $this->getMockBuilder(\WebDAVAuthentication::class)->setConstructorArgs(
            [$this->user_manager, $this->login_manager, $this->headers_sender, $this->access_key_authenticator]
        )->onlyMethods(['getUser'])->getMock();

        $user = UserTestBuilder::anAnonymousUser()->build();
        $webDAVAuthentication->method('getUser')->willReturn($user);

        $end_of_execution_exception = new \Exception();
        $this->headers_sender->expects(self::once())->method('sendHeaders')->willThrowException($end_of_execution_exception);

        $this->expectExceptionObject($end_of_execution_exception);

        $webDAVAuthentication->authenticate();
    }

    /**
     * Testing when the user is authenticated as a registered user
     */
    public function testAuthenticateSuccessWithNotAnonymousUser(): void
    {
        $webDAVAuthentication = $this->getMockBuilder(\WebDAVAuthentication::class)->setConstructorArgs(
            [$this->user_manager, $this->login_manager, $this->headers_sender, $this->access_key_authenticator]
        )->onlyMethods(['getUser'])->getMock();

        $_SERVER['PHP_AUTH_USER'] = 'username';
        $_SERVER['PHP_AUTH_PW']   = 'password';
        $user                     = UserTestBuilder::anActiveUser()->build();
        $webDAVAuthentication->method('getUser')->willReturn($user);

        self::assertEquals($user, $webDAVAuthentication->authenticate());
    }

    public function testCanAuthenticateUserWithAnAccessKey(): void
    {
        $webdav_authentication = new \WebDAVAuthentication($this->user_manager, $this->login_manager, $this->headers_sender, $this->access_key_authenticator);

        $_SERVER['REMOTE_ADDR']   = '2001:db8::3';
        $_SERVER['PHP_AUTH_USER'] = 'username';
        $_SERVER['PHP_AUTH_PW']   = 'tlp.k1.aaaaaa';

        $expected_user = UserTestBuilder::aUser()->withId(102)->withUserName('username')->build();

        $this->access_key_authenticator->method('getUser')->willReturn($expected_user);

        $authenticated_user = $webdav_authentication->authenticate();

        self::assertSame($expected_user, $authenticated_user);
    }

    public function testAskForAuthenticationAgainWhenUsernameDoesNotMatchTheUserAssociatedWithTheAccessKey(): void
    {
        $webdav_authentication = new \WebDAVAuthentication($this->user_manager, $this->login_manager, $this->headers_sender, $this->access_key_authenticator);

        $_SERVER['REMOTE_ADDR']   = '2001:db8::3';
        $_SERVER['PHP_AUTH_USER'] = 'wrong_username';
        $_SERVER['PHP_AUTH_PW']   = 'tlp.k1.aaaaaa';

        $user = UserTestBuilder::aUser()->withId(102)->withUserName('username')->build();

        $this->access_key_authenticator->method('getUser')->willThrowException(new HTTPBasicAuthUserAccessKeyMisusageException('wrong_username', $user));

        $end_of_execution_exception = new \Exception();
        $this->headers_sender->expects(self::once())->method('sendHeaders')->willThrowException($end_of_execution_exception);

        $this->expectExceptionObject($end_of_execution_exception);

        $webdav_authentication->authenticate();
    }
}
