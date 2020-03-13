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
use PHPUnit\Framework\TestCase;
use Tuleap\Webdav\Authentication\HeadersSender;

require_once __DIR__ . '/bootstrap.php';

/**
 * This is the unit test of WebDAVAuthentication
 */
class WebDAVAuthenticationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|HeadersSender
     */
    private $headers_sender;

    protected function setUp(): void
    {
        parent::setUp();
        ForgeConfig::store();

        $this->headers_sender = Mockery::mock(HeadersSender::class);
    }

    protected function tearDown(): void
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    /**
     * Testing when user gives only the username
     */
    public function testAuthenticateFailureWithOnlyUsername(): void
    {
        $webDAVAuthentication = \Mockery::mock(\WebDAVAuthentication::class, [$this->headers_sender])->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVAuthentication->shouldReceive('issetUsername')->andReturns(true);
        $webDAVAuthentication->shouldReceive('getUsername')->andReturns('username');
        $webDAVAuthentication->shouldReceive('getPassword')->andReturns(null);
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturns(true);
        $webDAVAuthentication->shouldReceive('getUser')->andReturns($user);

        $this->headers_sender->shouldReceive('sendHeaders')->once();

        $webDAVAuthentication->authenticate();
    }

    /**
     * Testing when the user gives only the password
     */
    public function testAuthenticateFailureWithOnlyPassword(): void
    {
        $webDAVAuthentication = \Mockery::mock(\WebDAVAuthentication::class, [$this->headers_sender])->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVAuthentication->shouldReceive('issetUsername')->andReturns(true);
        $webDAVAuthentication->shouldReceive('getUsername')->andReturns(null);
        $webDAVAuthentication->shouldReceive('getPassword')->andReturns('password');
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturns(true);
        $webDAVAuthentication->shouldReceive('getUser')->andReturns($user);

        $this->headers_sender->shouldReceive('sendHeaders')->once();

        $webDAVAuthentication->authenticate();
    }

    /**
     * Testing when the user gives a wrong username or password
     */
    public function testAuthenticateFailureWithWrongUsernameAndPassword(): void
    {
        $webDAVAuthentication = \Mockery::mock(\WebDAVAuthentication::class, [$this->headers_sender])->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVAuthentication->shouldReceive('issetUsername')->andReturns(true);
        $webDAVAuthentication->shouldReceive('getUsername')->andReturns('username');
        $webDAVAuthentication->shouldReceive('getPassword')->andReturns('password');
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturns(true);
        $webDAVAuthentication->shouldReceive('getUser')->andReturns($user);

        $this->headers_sender->shouldReceive('sendHeaders')->once();

        $webDAVAuthentication->authenticate();
    }

    /**
     * Testing when the user is authenticated as anonymous
     */
    public function testAuthenticateFailsWithAnonymousUserNotAllowed(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $webDAVAuthentication = \Mockery::mock(\WebDAVAuthentication::class, [$this->headers_sender])->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVAuthentication->shouldReceive('issetUsername')->andReturns(true);
        $webDAVAuthentication->shouldReceive('getUsername')->andReturns(null);
        $webDAVAuthentication->shouldReceive('getPassword')->andReturns(null);
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturns(true);
        $webDAVAuthentication->shouldReceive('getUser')->andReturns($user);

        $this->headers_sender->shouldReceive('sendHeaders')->once();

        $webDAVAuthentication->authenticate();
    }

    /**
     * Testing when the user is authenticated as anonymous
     */
    public function testAuthenticateSuccessWithAnonymousUserAllowed(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $webDAVAuthentication = \Mockery::mock(\WebDAVAuthentication::class, [$this->headers_sender])->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVAuthentication->shouldReceive('issetUsername')->andReturns(true);
        $webDAVAuthentication->shouldReceive('getUsername')->andReturns(null);
        $webDAVAuthentication->shouldReceive('getPassword')->andReturns(null);
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturns(true);
        $webDAVAuthentication->shouldReceive('getUser')->andReturns($user);

        $this->assertEquals($user, $webDAVAuthentication->authenticate());
    }

    /**
     * Testing when the user is authenticated as a registered user
     */
    public function testAuthenticateSuccessWithNotAnonymousUser(): void
    {
        $webDAVAuthentication = \Mockery::mock(\WebDAVAuthentication::class, [$this->headers_sender])->makePartial()->shouldAllowMockingProtectedMethods();
        $webDAVAuthentication->shouldReceive('issetUsername')->andReturns(true);
        $webDAVAuthentication->shouldReceive('getUsername')->andReturns('username');
        $webDAVAuthentication->shouldReceive('getPassword')->andReturns('password');
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('isAnonymous')->andReturns(false);
        $webDAVAuthentication->shouldReceive('getUser')->andReturns($user);

        $this->assertEquals($user, $webDAVAuthentication->authenticate());
    }
}
