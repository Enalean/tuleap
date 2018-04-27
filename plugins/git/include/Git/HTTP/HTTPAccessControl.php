<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Logger;
use User_LoginManager;
use Tuleap\Git\Gerrit\ReplicationHTTPUserAuthenticator;

class HTTPAccessControl
{
    /**
     * @var Logger
     */
    private $logger;
    /**
     * @var User_LoginManager
     */
    private $login_manager;
    /**
     * @var ReplicationHTTPUserAuthenticator
     */
    private $authenticator;
    /**
     * @var \PermissionsManager
     */
    private $permissions_manager;

    public function __construct(Logger $logger, User_LoginManager $login_manager, ReplicationHTTPUserAuthenticator $authenticator, \PermissionsManager $permissions_manager)
    {
        $this->logger              = $logger;
        $this->login_manager       = $login_manager;
        $this->authenticator       = $authenticator;
        $this->permissions_manager = $permissions_manager;
    }

    public function getUser(\URLVerification $url_verification, \GitRepository $repository, \Git_URL $url)
    {
        $user = null;
        if ($this->needAuthentication($url_verification, $repository, $url)) {
            $this->logger->debug('Repository '.$repository->getFullName().' need authentication');
            $user = $this->authenticate($repository);
        }
        return $user;
    }

    private function needAuthentication(\URLVerification $url_verification, \GitRepository $repository, \Git_URL $url)
    {
        return $url_verification->doesPlatformRequireLogin() ||
            $this->isGitPush($url) ||
            ! $this->canBeReadByAnonymous($repository) ||
            $this->isInPrivateProject($repository);
    }

    private function isGitPush(\Git_URL $url)
    {
        return $url->isGitPush();
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

    private function basicAuthenticationChallenge()
    {
        header('WWW-Authenticate: Basic realm="'.\ForgeConfig::get('sys_name').' git authentication"');
        header('HTTP/1.0 401 Unauthorized');
        exit;
    }

    /**
     * @param \GitRepository $repository
     * @return null|false|\PFO_User
     */
    private function authenticate(\GitRepository $repository)
    {
        if (! isset($_SERVER['PHP_AUTH_USER']) ||
            $_SERVER['PHP_AUTH_USER'] == '' ||
            ! isset($_SERVER['PHP_AUTH_PW']) ||
            $_SERVER['PHP_AUTH_PW'] == ''
        ) {
            $this->basicAuthenticationChallenge();
        } else {
            $user = null;
            try {
                $user = $this->authenticator->authenticate(
                    $repository,
                    $_SERVER['PHP_AUTH_USER'],
                    $_SERVER['PHP_AUTH_PW']
                );

                $this->logger->debug('LOGGED AS ' . $user->getUnixName());
            } catch (\Exception $exception) {
                $this->logger->debug('Replication user not recognized ' . $exception->getMessage());
            }

            if ($user === null) {
                try {
                    $user = $this->login_manager->authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
                    $this->logger->debug("LOGGED AS ".$user->getUnixName());
                    return $user;
                } catch (\Exception $exception) {
                    $this->logger->debug('LOGIN ERROR ' . $exception->getMessage());
                    $this->basicAuthenticationChallenge();
                }
            }

            return false;
        }
    }
}
