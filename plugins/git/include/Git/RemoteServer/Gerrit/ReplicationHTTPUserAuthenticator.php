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

use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use Git_RemoteServer_NotFoundException;
use GitRepository;
use PasswordHandler;
use Tuleap\Git\RemoteServer\Gerrit\HttpUserValidator;
use User_InvalidPasswordException;

class ReplicationHTTPUserAuthenticator
{

    /**
     * @var Git_RemoteServer_GerritServerFactory
     */
    private $server_factory;

    /**
     * @var PasswordHandler
     */
    private $password_handler;

    /**
     * @var HttpUserValidator
     */
    private $user_validator;

    public function __construct(
        PasswordHandler $password_handler,
        Git_RemoteServer_GerritServerFactory $server_factory,
        HttpUserValidator $user_validator
    ) {
        $this->password_handler = $password_handler;
        $this->server_factory   = $server_factory;
        $this->user_validator   = $user_validator;
    }

    /**
     * @throws User_InvalidPasswordException
     * @throws Git_RemoteServer_NotFoundException
     */
    public function authenticate(GitRepository $repository, $login, $password)
    {
        if (! $this->user_validator->isLoginAnHTTPUserLogin($login)) {
            throw new User_InvalidPasswordException();
        }

        $gerrit_server_id = $repository->getRemoteServerId();
        $gerrit_server    = $this->server_factory->getServerById($gerrit_server_id);
        if (
            hash_equals($gerrit_server->getGenericUserName(), $login) &&
            $this->password_handler->verifyHashPassword($password, $gerrit_server->getReplicationPassword())
        ) {
            $this->checkPasswordStorageConformity($gerrit_server, $password);
            return new ReplicationHTTPUser($gerrit_server);
        }

        throw new User_InvalidPasswordException();
    }

    private function checkPasswordStorageConformity(Git_RemoteServer_GerritServer $gerrit_server, $password)
    {
        if ($this->password_handler->isPasswordNeedRehash($gerrit_server->getReplicationPassword())) {
            $gerrit_server->setReplicationPassword($password);
            $this->server_factory->updateReplicationPassword($gerrit_server);
        }
    }
}
