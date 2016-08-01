<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use TuleapTestCase;

require_once dirname(__FILE__).'/../../../bootstrap.php';

class ReplicationHTTPUserAuthenticatorTest extends TuleapTestCase
{
    /**
     * @var \PasswordHandler
     */
    private $password_handler;

    /**
     * @var \Git_RemoteServer_GerritServerFactory
     */
    private $server_factory;

    public function setUp()
    {
        parent::setUp();
        $this->password_handler = mock('PasswordHandler');
        $this->server_factory = mock('Git_RemoteServer_GerritServerFactory');
    }

    public function itRejectsNonSpecificLogin()
    {
        $replication_user_authenticator = new ReplicationHTTPUserAuthenticator(
            $this->password_handler,
            $this->server_factory
        );
        $repository = mock('GitRepository');

        $this->expectException('User_InvalidPasswordException');
        $replication_user_authenticator->authenticate($repository, 'login', 'password');
    }

    public function itAcceptsSpecificLogin()
    {
        $user_login = 'forge__gerrit_1';
        $replication_user_authenticator = new ReplicationHTTPUserAuthenticator(
            $this->password_handler,
            $this->server_factory
        );
        $repository    = mock('GitRepository');
        $gerrit_server = mock('Git_RemoteServer_GerritServer');
        stub($gerrit_server)->getGenericUserName()->returns($user_login);
        stub($this->server_factory)->getServerById()->returns($gerrit_server);
        stub($this->password_handler)->verifyHashPassword()->returns(true);

        $replication_http_user = $replication_user_authenticator->authenticate($repository, $user_login, 'password');
        $this->assertEqual($replication_http_user->getUnixName(), $user_login);
    }

    public function itRejectsInvalidPassword()
    {
        $user_login = 'forge__gerrit_1';
        $user_password = 'password';
        $replication_user_authenticator = new ReplicationHTTPUserAuthenticator(
            $this->password_handler,
            $this->server_factory
        );
        $repository    = mock('GitRepository');
        $gerrit_server = mock('Git_RemoteServer_GerritServer');
        stub($gerrit_server)->getGenericUserName()->returns($user_login);
        stub($this->server_factory)->getServerById()->returns($gerrit_server);
        stub($this->password_handler)->verifyHashPassword()->returns(false);

        $this->expectException('User_InvalidPasswordException');
        $replication_user_authenticator->authenticate($repository, $user_login, $user_password);
    }
}
