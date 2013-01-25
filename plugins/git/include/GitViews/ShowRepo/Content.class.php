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
     * @var User
     */
    private $current_user;

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
            User $current_user,
            Git $controller,
            Git_Driver_Gerrit $driver,
            array $gerrit_servers,
            $theme_path
    ) {
        $this->repository     = $repository;
        $this->gitphp_viewer  = $gitphp_viewer;
        $this->current_user   = $current_user;
        $this->controller     = $controller;
        $this->driver         = $driver;
        $this->gerrit_servers = $gerrit_servers;
        $this->theme_path     = $theme_path;
    }

    public function display() {
        $html  = '';
        $html .= '<div id="plugin_git_reference">';
        $html .= $this->getHeader();
        if ($this->repository->getRemoteServerId()) {
            $html .= $this->getRemoteRepositoryInfo();
        }
        $html .= $this->getCloneUrl();
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
        $admin_link = '';
        if ($this->controller->isAPermittedAction('repo_management')) {
            $admin_link .= ' - <a href="/plugins/git/?action=repo_management&group_id='.$this->repository->getProjectId().'&repo_id='.$repoId.'" class="repo_admin">';
            $admin_link .= $GLOBALS['Language']->getText('plugin_git', 'admin_repo_management');
            $admin_link .= '</a>';
        }

        $html .= '<h1>'.$accessType.$this->repository->getFullName().'</h1>';
        $html .= '<p id="plugin_git_reference_author">'.
            $GLOBALS['Language']->getText('plugin_git', 'view_repo_creator')
            .' '.
            $creatorName
            .' '.
            $creationDate
            .' '.
            $admin_link
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
        $html = '<div id="plugin_git_clone_url">';
        $html .= '<span class="input-prepend input-append">';
        $html .= '<span class="btn-group gitclone_urls_protocols" data-toggle="buttons-radio">';
        $hp = Codendi_HTMLPurifier::instance();
        $urls = $this->getAccessURLs();
        list(,$first_url) = each($urls);
        if (count($urls) > 1) {
            $selected = 'active';
            foreach ($urls as $transport => $url) {
                $html .= '<button type="button" class="btn '.$selected.' plugin_git_transport" name="plugin_git_transport" value="'. $hp->purify($url) .'" >';
                $html .= $transport;
                $html .= '</button>';
                $selected = '';
            }
        }
        $html .= '</span>';

        $html .= '<span class="add-on">(read-only)</span>';
        $html .= '<input id="plugin_git_clone_field" type="text" value="'.$first_url.'" class="span6" />';
        $html .= '<button class="btn" type="button" id="plugin_git_example-handle" data-toggle="button">?</button>';
        $html .= '</span>';
        $html .= '<div>';
        $html .= '<div id="plugin_git_example">
Cloning this repository:

<pre>
    git clone <span class="plugin_git_example_url">'. $first_url .'</span> '. $this->repository->getName() .'
    cd '. $this->repository->getName() .'
</pre>

Add this repository as a remote to an existing local repository:

<pre>
    git remote add '. $this->repository->getName() .' <span class="plugin_git_example_url">'. $first_url .'</span>
    git fetch '. $this->repository->getName() .'
    git checkout -b my-local-tracking-branch '. $this->repository->getName() .'/master_or_other_branch
</pre>
</div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    private function getAccessURLs() {
        $urls = $this->repository->getAccessURL();
        if ($this->repository->getRemoteServerId()) {
            $gerrit_server  = $this->gerrit_servers[$this->repository->getRemoteServerId()];
            $gerrit_project = $this->driver->getGerritProjectName($this->repository);

            $clone_url = $gerrit_server->getEndUserCloneUrl($gerrit_project, $this->current_user);
            $this->prependGerritCloneURL($urls, $clone_url);
        }
        return $urls;
    }

    private function prependGerritCloneURL(array &$urls, $gerrit_clone_url) {
        $gerrit = array('gerrit' => $gerrit_clone_url);
        $urls = array_merge($gerrit, $urls);
    }

    private function getRemoteRepositoryInfo() {
        /** @var $gerrit_server Git_RemoteServer_GerritServer */
        $gerrit_server  = $this->gerrit_servers[$this->repository->getRemoteServerId()];
        $gerrit_project = $this->driver->getGerritProjectName($this->repository);
        $link = $gerrit_server->getProjectUrl($gerrit_project);

        $html  = '';
        $html .= '<div class="alert alert-info gerrit_url">';
        $html .= $GLOBALS['Language']->getText('plugin_git', 'delegated_to_gerrit');
        $html .= '<br />';
        $html .= '<a href="'.$link.'">'.$gerrit_project.'</a>';
        $html .= '</div>';
        return $html;
    }

}

?>
