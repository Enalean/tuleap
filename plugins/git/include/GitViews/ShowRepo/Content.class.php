<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class GitViews_ShowRepo_Content {
    /**
     * @var GitRepository
     */
    protected $repository;

    /**
     * @var GitViews_GitPhpViewer
     */
    private $gitphp_viewer;

    /**
     * @var Git
     */
    private $controller;

    /**
     * @var Git_Driver_Gerrit
     */
    private $driver;

    /**
     * @var array
     */
    private $gerrit_servers;

    /**
     * @var string
     */
    private $theme_path;

    public function __construct(
            GitRepository $repository,
            GitViews_GitPhpViewer $gitphp_viewer,
            Git $controller,
            Git_Driver_Gerrit $driver,
            array $gerrit_servers,
            $theme_path) {
        $this->repository     = $repository;
        $this->gitphp_viewer  = $gitphp_viewer;
        $this->controller     = $controller;
        $this->driver         = $driver;
        $this->gerrit_servers = $gerrit_servers;
        $this->theme_path     = $theme_path;
    }

    public function display() {
        $html  = '';
        $html .= '<div id="plugin_git_reference">';
        $html .= $this->getHeader();
        $html .= $this->getCloneUrl();
        if ($this->repository->getRemoteServerId()) {
            $html .= $this->getRemoteRepositoryInfo();
        }
        $html .= '</div>';
        $html .= $this->gitphp_viewer->getContent();
        echo $html;
    }

    private function getHeader() {
        $html         = '';
        $repoId       = $this->repository->getId();
        $creator      = $this->repository->getCreator();
        $parent       = $this->repository->getParent();
        $access       = $this->repository->getAccess();
        $creatorName  = '';
        if ( !empty($creator) ) {
            $creatorName  = UserHelper::instance()->getLinkOnUserFromUserId($creator->getId());
        }
        $creationDate = html_time_ago(strtotime($this->repository->getCreationDate()));

        // Access type
        $accessType = $this->getAccessType($access, $this->repository->getBackend() instanceof Git_Backend_Gitolite);

        // Actions
        $repoActions = '<ul id="plugin_git_repository_actions">';
        if ($this->controller->isAPermittedAction('repo_management')) {
            $repoActions .= '<li><a href="/plugins/git/?action=repo_management&group_id='.$this->repository->getProjectId().'&repo_id='.$repoId.'" class="repo_admin">'.$GLOBALS['Language']->getText('plugin_git', 'admin_repo_management').'</li>';
        }

        $repoActions .= '</ul>';

        $html .= '<h1>'.$accessType.$this->repository->getFullName().'</h1>';
        $html .= $repoActions;
        $html .= '<p id="plugin_git_reference_author">'.
            $GLOBALS['Language']->getText('plugin_git', 'view_repo_creator')
            .' '.
            $creatorName
            .' '.
            $creationDate
            .'</p>';
        if ( !empty($parent) ) {
            $html .= '<p id="plugin_git_repo_parent">'.$GLOBALS['Language']->getText('plugin_git', 'view_repo_parent').':
                  <span>'.$this->_getRepositoryPageUrl( $parent->getId(), $parent->getName() ).'</span>
                  </p>';
        }
        return $html;
    }

    /**
     * Fetch the html code to display the icon of a repository (depends on type of project)
     *
     * @param $access
     * @param $backend_type
     */
    private function getAccessType($access, $backendIsGitolite) {
        $html = '<span class="plugin_git_repo_privacy" title=';

        if ($backendIsGitolite) {
            $html .= '"custom">';
            $html .= '<img src="'.$this->theme_path.'/images/perms.png" />';
        } else {
            switch ($access) {
                case GitRepository::PRIVATE_ACCESS:
                    $html .= '"'.$GLOBALS['Language']->getText('plugin_git', 'view_repo_access_private').'">';
                    $html .= '<img src="'.util_get_image_theme('ic/lock.png').'" />';
                    break;
                case GitRepository::PUBLIC_ACCESS:
                    $html .= '"'.$GLOBALS['Language']->getText('plugin_git', 'view_repo_access_public').'">';
                    $html .= '<img src="'.util_get_image_theme('ic/lock-unlock.png').'" />';
                    break;
            }
        }
        $html .= '</span>';
        return $html;
    }

    private function getCloneUrl() {
        $html = '<p id="plugin_git_clone_url">';

        $hp = Codendi_HTMLPurifier::instance();
        $urls = $this->repository->getAccessURL();
        list(,$first_url) = each($urls);
        if (count($urls) > 1) {
            $selected = 'checked="checked"';
            foreach ($urls as $transport => $url) {
                $html .= '<label>';
                $html .= '<input type="radio" class="plugin_git_transport" name="plugin_git_transport" value="'. $hp->purify($url) .'" '.$selected.' />';
                $html .= $transport;
                $html .= '</label> ';
                $selected  = '';
            }
        }
        $html .= '<input id="plugin_git_clone_field" type="text" value="'.$first_url.'" /></label>
                  </p>';
        return $html;
    }

    private function getRemoteRepositoryInfo() {
        /** @var $gerrit_server Git_RemoteServer_GerritServer */
        $gerrit_server  = $this->gerrit_servers[$this->repository->getRemoteServerId()];
        $gerrit_project = $this->driver->getGerritProjectName($this->repository);
        $link = $gerrit_server->getProjectUrl($gerrit_project);

        $html  = '';
        $html .= '<p>Gerrit: <a href="'.$link.'">'.$gerrit_project.'</a>';
        $html .= '</p>';
        return $html;
    }

}

?>