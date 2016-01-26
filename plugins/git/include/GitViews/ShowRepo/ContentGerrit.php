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

class GitViews_ShowRepo_ContentGerritStatus {

    /**
     * @var GitRepository
     */
    private $repository;

    /**
     * @var Git_Driver_Gerrit_ProjectCreatorStatus
     */
    private $project_creator_status;

    /** @var Git_Driver_Gerrit_GerritDriverFactory */
    private $driver_factory;

    /** @var array */
    private $gerrit_servers;


    public function __construct(Git_Driver_Gerrit_GerritDriverFactory $driver_factory, array $gerrit_servers, GitRepository $repository, Git_Driver_Gerrit_ProjectCreatorStatus $project_creator_status) {
        $this->gerrit_servers         = $gerrit_servers;
        $this->driver_factory         = $driver_factory;
        $this->repository             = $repository;
        $this->project_creator_status = $project_creator_status;
    }

    public function getContent() {
        switch ($this->project_creator_status->getStatus($this->repository)) {
            case Git_Driver_Gerrit_ProjectCreatorStatus::QUEUE:
                return $this->getRemoteRepositoryInfoOnGoing();

            case Git_Driver_Gerrit_ProjectCreatorStatus::DONE:
                return $this->getRemoteRepositoryInfoDone();

            case Git_Driver_Gerrit_ProjectCreatorStatus::ERROR:
                return $this->getRemoteRepositoryInfoError();

            default:
                if ($this->repository->isMigratedToGerrit()) {
                    return $this->getRemoteRepositoryInfoDone();
                }
                return '';
        }
    }

    private function getRemoteRepositoryInfoDone() {
        /** @var $gerrit_server Git_RemoteServer_GerritServer */
        $gerrit_server  = $this->gerrit_servers[$this->repository->getRemoteServerId()];
        $driver         = $this->driver_factory->getDriver($gerrit_server);
        $gerrit_project = $driver->getGerritProjectName($this->repository);
        $link           = $gerrit_server->getProjectUrl($gerrit_project);

        $html  = '';
        $html .= '<div class="alert alert-info gerrit_url">';
        $html .= $GLOBALS['Language']->getText('plugin_git', 'delegated_to_gerrit');
        $html .= ' <a href="'.$link.'">'.$gerrit_project.'</a>';
        $html .= '</div>';
        return $html;
    }

    private function getRemoteRepositoryInfoOnGoing() {
        $html  = '';
        $html .= '<div class="alert alert-info gerrit_url">';
        $html .= $GLOBALS['Language']->getText('plugin_git', 'delegated_to_gerrit_queue');
        $html .= '</div>';
        return $html;
    }

    private function getRemoteRepositoryInfoError() {
        $date = DateHelper::timeAgoInWords($this->project_creator_status->getEventDate($this->repository), false, true);
        $url = GIT_BASE_URL . '/?action=repo_management&group_id='.$this->repository->getProjectId().'&repo_id='.$this->repository->getId().'&pane=gerrit';
        $html  = '';
        $html .= '<div class="alert alert-error gerrit_url">';
        $html .= $GLOBALS['Language']->getText('plugin_git', 'delegated_to_gerrit_error', array($date, $url), CODENDI_PURIFIER_DISABLED);
        $html .= '</div>';
        return $html;
    }
}
