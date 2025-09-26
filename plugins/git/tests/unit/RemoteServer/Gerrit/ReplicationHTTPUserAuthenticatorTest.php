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

declare(strict_types=1);

namespace Tuleap\Git\RemoteServer\Gerrit;

use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use PasswordHandler;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Git\Gerrit\ReplicationHTTPUserAuthenticator;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ReplicationHTTPUserAuthenticatorTest extends TestCase
{
    use GlobalLanguageMock;

    private PasswordHandler&MockObject $password_handler;
    private Git_RemoteServer_GerritServerFactory&MockObject $server_factory;
    private HttpUserValidator&MockObject $user_validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->password_handler = $this->createMock(PasswordHandler::class);
        $this->server_factory   = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $this->user_validator   = $this->createMock(HttpUserValidator::class);
    }

    public function testItRejectsNonSpecificLogin(): void
    {
        $repository                     = GitRepositoryTestBuilder::aProjectRepository()->build();
        $replication_user_authenticator = new ReplicationHTTPUserAuthenticator(
            $this->password_handler,
            $this->server_factory,
            $this->user_validator
        );
        $this->user_validator->method('isLoginAnHTTPUserLogin');

        $this->expectException('User_InvalidPasswordException');
        $replication_user_authenticator->authenticate($repository, 'login', new ConcealedString('password'));
    }

    public function testItAcceptsSpecificLogin(): void
    {
        $user_login                     = 'forge__gerrit_1';
        $repository                     = GitRepositoryTestBuilder::aProjectRepository()->build();
        $gerrit_server                  = $this->createMock(Git_RemoteServer_GerritServer::class);
        $replication_user_authenticator = new ReplicationHTTPUserAuthenticator(
            $this->password_handler,
            $this->server_factory,
            $this->user_validator
        );
        $gerrit_server->method('getGenericUserName')->willReturn($user_login);
        $gerrit_server->method('getReplicationPassword')->willReturn('hashpassword');
        $this->server_factory->method('getServerById')->willReturn($gerrit_server);
        $this->password_handler->method('verifyHashPassword')->willReturn(true);
        $this->password_handler->method('isPasswordNeedRehash');
        $this->user_validator->method('isLoginAnHTTPUserLogin')->willReturn(true);

        $replication_http_user = $replication_user_authenticator->authenticate($repository, $user_login, new ConcealedString('password'));
        self::assertEquals($user_login, $replication_http_user->getUserName());
    }

    public function testItRejectsInvalidPassword(): void
    {
        $user_login                     = 'forge__gerrit_1';
        $user_password                  = new ConcealedString('password');
        $repository                     = GitRepositoryTestBuilder::aProjectRepository()->build();
        $gerrit_server                  = $this->createMock(Git_RemoteServer_GerritServer::class);
        $replication_user_authenticator = new ReplicationHTTPUserAuthenticator(
            $this->password_handler,
            $this->server_factory,
            $this->user_validator
        );
        $gerrit_server->method('getGenericUserName')->willReturn($user_login);
        $this->server_factory->method('getServerById')->willReturn($gerrit_server);
        $this->password_handler->method('verifyHashPassword')->willReturn(false);
        $this->user_validator->method('isLoginAnHTTPUserLogin');

        $this->expectException('User_InvalidPasswordException');
        $replication_user_authenticator->authenticate($repository, $user_login, $user_password);
    }
}
