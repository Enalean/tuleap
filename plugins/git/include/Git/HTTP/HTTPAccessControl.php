<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Git\HTTP;

use Psr\Log\LoggerInterface;
use PermissionsManager;
use PFUser;
use Tuleap\Cryptography\ConcealedString;
use User_LoginManager;
use Tuleap\Git\Gerrit\ReplicationHTTPUserAuthenticator;
use UserDao;

class HTTPAccessControl
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \ForgeAccess
     */
    private $forge_access;

    /**
     * @var User_LoginManager
     */
    private $login_manager;

    /**
     * @var ReplicationHTTPUserAuthenticator
     */
    private $replication_http_user_authenticator;

    /**
     * @var HTTPUserAccessKeyAuthenticator
     */
    private $access_key_authenticator;
    /**
     * @var PermissionsManager
     */
    private $permissions_manager;
    /**
     * @var UserDao
     */
    private $user_dao;
    /**
     * @var GitHTTPAskBasicAuthenticationChallenge
     */
    private $ask_basic_authentication_challenge;

    public function __construct(
        LoggerInterface $logger,
        \ForgeAccess $forge_access,
        User_LoginManager $login_manager,
        ReplicationHTTPUserAuthenticator $replication_http_user_authenticator,
        HTTPUserAccessKeyAuthenticator $access_key_authenticator,
        PermissionsManager $permissions_manager,
        UserDao $user_dao,
        GitHTTPAskBasicAuthenticationChallenge $ask_basic_authentication_challenge
    ) {
        $this->logger                              = $logger;
        $this->forge_access                        = $forge_access;
        $this->login_manager                       = $login_manager;
        $this->replication_http_user_authenticator = $replication_http_user_authenticator;
        $this->access_key_authenticator            = $access_key_authenticator;
        $this->permissions_manager                 = $permissions_manager;
        $this->user_dao                            = $user_dao;
        $this->ask_basic_authentication_challenge  = $ask_basic_authentication_challenge;
    }

    /**
     * @return null|\PFO_User
     */
    public function getUser(\GitRepository $repository, GitHTTPOperation $git_operation)
    {
        $user = null;
        if ($this->needAuthentication($repository, $git_operation)) {
            $this->logger->debug('Repository ' . $repository->getFullName() . ' need authentication');
            $user = $this->authenticate($repository);
        }
        return $user;
    }

    private function needAuthentication(\GitRepository $repository, GitHTTPOperation $git_operation) : bool
    {
        return $this->forge_access->doesPlatformRequireLogin() ||
            $git_operation->isWrite() ||
            ! $this->canBeReadByAnonymous($repository) ||
            $this->isInPrivateProject($repository);
    }

    private function isInPrivateProject(\GitRepository $repository)
    {
        return $repository->getProject()->isPublic() === false;
    }

    private function canBeReadByAnonymous(\GitRepository $repository)
    {
        $ugroup_ids = $this->permissions_manager->getAuthorizedUgroupIds($repository->getId(), \Git::PERM_READ);
        foreach ($ugroup_ids as $ugroup_id) {
            if ($ugroup_id == \ProjectUGroup::ANONYMOUS) {
                return true;
            }
        }
        return false;
    }

    /**
     * @psalm-return never-return
     */
    private function basicAuthenticationChallenge(): void
    {
        $this->ask_basic_authentication_challenge->askBasicAuthenticationChallenge();
    }

    /**
     * @return \PFO_User
     */
    private function authenticate(\GitRepository $repository)
    {
        if (! isset($_SERVER['PHP_AUTH_USER']) ||
            $_SERVER['PHP_AUTH_USER'] == '' ||
            ! isset($_SERVER['PHP_AUTH_PW']) ||
            $_SERVER['PHP_AUTH_PW'] == ''
        ) {
            $this->basicAuthenticationChallenge();
        }

        try {
            $user = $this->replication_http_user_authenticator->authenticate(
                $repository,
                $_SERVER['PHP_AUTH_USER'],
                $_SERVER['PHP_AUTH_PW']
            );

            $this->logger->debug('LOGGED AS ' . $user->getUnixName());
            return $user;
        } catch (\User_InvalidPasswordException $exception) {
            $this->logger->debug('Replication user not recognized ' . $exception->getMessage());
        } catch (\Git_RemoteServer_NotFoundException $exception) {
            $this->logger->debug($exception->getMessage());
        }

        try {
            $user = $this->access_key_authenticator->getUser(
                $_SERVER['PHP_AUTH_USER'],
                new ConcealedString($_SERVER['PHP_AUTH_PW']),
                \HTTPRequest::instance()->getIPAddress()
            );
        } catch (HTTPUserAccessKeyMisusageException $ex) {
            $this->logger->debug('LOGIN ERROR ' . $exception->getMessage());
            $this->basicAuthenticationChallenge();
        }
        if ($user !== null) {
            $this->logger->debug('LOGGED AS ' . $user->getUnixName());
            return $user;
        }

        try {
            $user = $this->login_manager->authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
            $this->logger->debug('LOGGED AS ' . $user->getUnixName());
            $this->updateLastAccessDateForUser($user);
            return $user;
        } catch (\User_LoginException $exception) {
            $this->logger->debug('LOGIN ERROR ' . $exception->getMessage());
            $this->basicAuthenticationChallenge();
        }

        throw new \RuntimeException('Requesting basic authentication for a Git HTTP operation have failed');
    }

    private function updateLastAccessDateForUser(PFUser $user)
    {
        $this->user_dao->storeLastAccessDate($user->getId(), time());
    }
}
