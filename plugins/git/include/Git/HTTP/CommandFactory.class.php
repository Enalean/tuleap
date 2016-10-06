<?php
/**
 * Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
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

use Tuleap\Git\Gerrit\ReplicationHTTPUserAuthenticator;
use Tuleap\Git\Gitolite\VersionDetector;

class Git_HTTP_CommandFactory {

    /**
     * @var ReplicationHTTPUserAuthenticator
     */
    private $authenticator;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var PermissionsManager
     */
    private $permissions_manager;

    /**
     * @var User_LoginManager
     */
    private $login_manager;

    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;

    /**
     * @var URLVerification
     */
    private $url_verification;

    /**
     * @var Git_RemoteServer_GerritServerFactory
     */
    private $gerrit_server_factory;

    /**
     * @var VersionDetector
     */
    private $detector;

    public function __construct(
        GitRepositoryFactory $repository_factory,
        User_LoginManager $login_manager,
        PermissionsManager $permissions_manager,
        URLVerification $url_verification,
        Logger $logger,
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        ReplicationHTTPUserAuthenticator $authenticator,
        VersionDetector $detector
    ) {
        $this->repository_factory    = $repository_factory;
        $this->login_manager         = $login_manager;
        $this->permissions_manager   = $permissions_manager;
        $this->logger                = $logger;
        $this->url_verification      = $url_verification;
        $this->gerrit_server_factory = $gerrit_server_factory;
        $this->authenticator         = $authenticator;
        $this->detector              = $detector;
    }

    public function getCommandForRepository(GitRepository $repository, Git_URL $url) {
        $command = $this->getGitHttpBackendCommand();
        if ($this->needAuthentication($repository, $url)) {
            $this->logger->debug('Repository '.$repository->getFullName().' need authentication');
            $command = $this->authenticate($repository, $command);
        }
        $command->setPathInfo($url->getPathInfo());
        $command->setQueryString($url->getQueryString());
        return $command;
    }


    private function getGitHttpBackendCommand() {
        $command = new Git_HTTP_CommandCentos5GitHttpBackend();
        if (Git_Exec::isGit19Installed()) {
            $command = new Git_HTTP_CommandSCL19GitHttpBackend();
        } elseif (is_file('/usr/libexec/git-core/git-http-backend')) {
            $command = new Git_HTTP_CommandCentos6GitHttpBackend();
        }
        return $command;
    }

    private function needAuthentication(GitRepository $repository, Git_URL $url)
    {
        return $this->url_verification->doesPlatformRequireLogin() ||
            $this->isGitPush($url) ||
            ! $this->canBeReadByAnonymous($repository) ||
            $this->isInPrivateProject($repository);
    }

    private function isGitPush(Git_URL $url) {
        return $url->isGitPush();
    }

    private function isInPrivateProject(GitRepository $repository) {
        return $repository->getProject()->isPublic() == false;
    }

    private function canBeReadByAnonymous(GitRepository $repository) {
        $ugroup_ids = $this->permissions_manager->getAuthorizedUgroupIds($repository->getId(), Git::PERM_READ);
        foreach ($ugroup_ids as $ugroup_id) {
            if ($ugroup_id == ProjectUGroup::ANONYMOUS) {
                return true;
            }
        }
        return false;
    }

    private function basicAuthenticationChallenge() {
        header('WWW-Authenticate: Basic realm="'.ForgeConfig::get('sys_name').' git authentication"');
        header('HTTP/1.0 401 Unauthorized');
        exit;
    }

    private function authenticate(GitRepository $repository, Git_HTTP_Command $command)
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
            } catch (Exception $exception) {
                $this->logger->debug('Replication user not recognized ' . $exception->getMessage());
            }

            if ($user === null) {
                try {
                    $user = $this->login_manager->authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
                } catch (Exception $exception) {
                    $this->logger->debug('LOGIN ERROR ' . $exception->getMessage());
                    $this->basicAuthenticationChallenge();
                }
            }

            return $this->getGitoliteCommand($user, $command);
        }
    }

    private function getGitoliteCommand(PFO_User $user, Git_HTTP_Command $command) {
        if ($this->detector->isGitolite3()) {
            return new Git_HTTP_CommandGitolite3($user, $command);
        }
        return new Git_HTTP_CommandGitolite($user, $command);
    }
}
