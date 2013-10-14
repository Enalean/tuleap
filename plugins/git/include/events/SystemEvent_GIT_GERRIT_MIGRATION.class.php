<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/backend/BackendLogger.class.php';

class SystemEvent_GIT_GERRIT_MIGRATION extends SystemEvent {

    const NAME = "GIT_GERRIT_MIGRATION";

    /** @var GitDao */
    private $dao;

    /** @var GitRepositoryFactory */
    private $repository_factory;

    /** @var Git_RemoteServer_GerritServerFactory */
    private $server_factory;

    /** @var Logger */
    private $logger;

    /** @var Git_Driver_Gerrit_ProjectCreator */
    private $project_creator;

    public function process() {
        $repo_id           = (int)$this->getParameter(0);
        $remote_server_id  = (int)$this->getParameter(1);
        $this->dao->switchToGerrit($repo_id, $remote_server_id);

        $repository = $this->repository_factory->getRepositoryById($repo_id);
        if (! $repository) {
            $this->warning('Unable to find repository, perhaps it was deleted in the mean time?');
            return;
        }

        try {
            $server                = $this->server_factory->getServer($repository);
            $migrate_access_rights = $this->getParameter(2);
            $gerrit_project        = $this->project_creator->createGerritProject($server, $repository, $migrate_access_rights);
            $this->project_creator->removeTemporaryDirectory();
            $repository->getBackend()->updateRepoConf($repository);

            $this->done("Created project $gerrit_project on ". $server->getHost());
            return true;
        } catch (Git_Driver_Gerrit_ProjectCreator_ProjectAlreadyExistsException $e) {
            $this->logError("gerrit: ", "Gerrit failure: ", $e);
        } catch (Git_Driver_Gerrit_Exception $e) {
            $this->logError("gerrit: ", "Gerrit failure: ", $e);
        } catch (Exception $e) {
            $this->logError("", "An error occured while processing event: ", $e);
        }
    }

    private function logError($sysevent_prefix, $log_prefix, Exception $e) {
        $this->error($sysevent_prefix . $e->getMessage());
        $this->logger->error($log_prefix . $this->verbalizeParameters(null), $e);
    }

    /**
     * @return string a human readable representation of parameters
     */
    public function verbalizeParameters($with_link) {
        $txt = '';

        $repo_id          = (int)$this->getParameter(0);
        $remote_server_id = (int)$this->getParameter(1);
        $txt .= 'repo: '. $this->verbalizeRepoId($repo_id, $with_link) .', remote server: '. $this->verbalizeRemoteServerId($remote_server_id, $with_link).$this->verbalizeAccessRightMigration();
        return $txt;
    }

    private function verbalizeAccessRightMigration() {
        $migrate_access_rights = $this->getParameter(2);
        if (!$migrate_access_rights) {
            return ', without access rights';
        }
    }

    private function verbalizeRepoId($repo_id, $with_link) {
        $txt = '#'. $repo_id;
        if ($with_link) {
            $hp = Codendi_HTMLPurifier::instance();
            $repo = $this->repository_factory->getRepositoryById($repo_id);
            if ($repo) {
                $txt = '<a href="/plugins/git/index.php/'. $repo->getProjectId() .'/view/'. $repo_id .'/" title="'. $hp->purify($repo->getFullName()) .'">'. $txt .'</a>';
            }
        }
        return $txt;
    }

    private function verbalizeRemoteServerId($remote_server_id, $with_link) {
        $txt = '#'. $remote_server_id;
        if ($with_link) {
            $hp = Codendi_HTMLPurifier::instance();
            $server = $this->server_factory->getServerById($remote_server_id);
            $txt = $server->getHost();
        }
        return $txt;
    }

    public function injectDependencies(
        GitDao $dao,
        GitRepositoryFactory $repository_factory,
        Git_RemoteServer_GerritServerFactory  $server_factory,
        Logger  $logger,
        Git_Driver_Gerrit_ProjectCreator $project_creator
    ) {
        $this->dao                = $dao;
        $this->repository_factory = $repository_factory;
        $this->server_factory     = $server_factory;
        $this->logger             = $logger;
        $this->project_creator    = $project_creator;
    }
}

?>