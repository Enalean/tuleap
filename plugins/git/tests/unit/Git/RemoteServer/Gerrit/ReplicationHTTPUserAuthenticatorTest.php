<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Gerrit;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;

require_once __DIR__ . '/../../../bootstrap.php';

class ReplicationHTTPUserAuthenticatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var \PasswordHandler
     */
    private $password_handler;

    /**
     * @var \Git_RemoteServer_GerritServerFactory
     */
    private $server_factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->password_handler = \Mockery::spy(\PasswordHandler::class);
        $this->server_factory   = \Mockery::spy(\Git_RemoteServer_GerritServerFactory::class);
        $this->user_validator   = \Mockery::spy(\Tuleap\Git\RemoteServer\Gerrit\HttpUserValidator::class);
    }

    public function testItRejectsNonSpecificLogin(): void
    {
        $repository                     = \Mockery::spy(\GitRepository::class);
        $replication_user_authenticator = new ReplicationHTTPUserAuthenticator(
            $this->password_handler,
            $this->server_factory,
            $this->user_validator
        );

        $this->expectException('User_InvalidPasswordException');
        $replication_user_authenticator->authenticate($repository, 'login', 'password');
    }

    public function testItAcceptsSpecificLogin(): void
    {
        $user_login                     = 'forge__gerrit_1';
        $repository                     = \Mockery::spy(\GitRepository::class);
        $gerrit_server                  = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $replication_user_authenticator = new ReplicationHTTPUserAuthenticator(
            $this->password_handler,
            $this->server_factory,
            $this->user_validator
        );
        $gerrit_server->shouldReceive('getGenericUserName')->andReturns($user_login);
        $this->server_factory->shouldReceive('getServerById')->andReturns($gerrit_server);
        $this->password_handler->shouldReceive('verifyHashPassword')->andReturns(true);
        $this->user_validator->shouldReceive('isLoginAnHTTPUserLogin')->andReturns(true);

        $replication_http_user = $replication_user_authenticator->authenticate($repository, $user_login, 'password');
        $this->assertEquals($user_login, $replication_http_user->getUnixName());
    }

    public function testItRejectsInvalidPassword(): void
    {
        $user_login                     = 'forge__gerrit_1';
        $user_password                  = 'password';
        $repository                     = \Mockery::spy(\GitRepository::class);
        $gerrit_server                  = \Mockery::spy(\Git_RemoteServer_GerritServer::class);
        $replication_user_authenticator = new ReplicationHTTPUserAuthenticator(
            $this->password_handler,
            $this->server_factory,
            $this->user_validator
        );
        $gerrit_server->shouldReceive('getGenericUserName')->andReturns($user_login);
        $this->server_factory->shouldReceive('getServerById')->andReturns($gerrit_server);
        $this->password_handler->shouldReceive('verifyHashPassword')->andReturns(false);

        $this->expectException('User_InvalidPasswordException');
        $replication_user_authenticator->authenticate($repository, $user_login, $user_password);
    }
}
