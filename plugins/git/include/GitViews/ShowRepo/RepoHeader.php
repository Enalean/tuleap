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

namespace Tuleap\Git\GitViews\ShowRepo;

use EventManager;
use Git_Backend_Gitolite;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Driver_Gerrit_ProjectCreatorStatusDao;
use Git_Driver_Gerrit_UserAccountManager;
use Git_GitRepositoryUrlManager;
use Git_Mirror_MirrorDataMapper;
use GitPermissionsManager;
use GitRepository;
use GitViews_ShowRepo_ContentGerritStatus;
use HTTPRequest;
use PFUser;
use RepositoryClonePresenter;
use TemplateRendererFactory;
use Tuleap\Git\GitViews\GitViewHeader;
use Tuleap\Layout\BaseLayout;

class RepoHeader
{
    /**
     * @var HTTPRequest
     */
    private $request;

    /**
     * @var GitRepository
     */
    protected $repository;

    /**
     * @var PFUser
     */
    private $current_user;

    /**
     * @var Git_Driver_Gerrit_GerritDriverFactory
     */
    private $driver_factory;

    /**
     * @var Git_Driver_Gerrit_UserAccountManager
     */
    private $gerrit_usermanager;

    /**
     * @var array
     */
    private $gerrit_servers;

    /**
     * @var string
     */
    private $master_location_name;

    /** @var Git_GitRepositoryUrlManager */
    private $url_manager;

    /** @var Git_Mirror_MirrorDataMapper */
    private $mirror_data_mapper;
    /**
     * @var GitPermissionsManager
     */
    private $permissions_manager;
    /**
     * @var BaseLayout
     */
    private $layout;

    public function __construct(
        Git_GitRepositoryUrlManager $url_manager,
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        Git_Driver_Gerrit_UserAccountManager $gerrit_usermanager,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        GitPermissionsManager $permissions_manager,
        array $gerrit_servers,
        $master_location_name
    ) {
        $this->driver_factory       = $driver_factory;
        $this->gerrit_usermanager   = $gerrit_usermanager;
        $this->gerrit_servers       = $gerrit_servers;
        $this->mirror_data_mapper   = $mirror_data_mapper;
        $this->url_manager          = $url_manager;
        $this->permissions_manager  = $permissions_manager;
        $this->master_location_name = $master_location_name;
    }

    public function display(HTTPRequest $request, BaseLayout $layout, GitRepository $repository)
    {
        $this->layout               = $layout;
        $this->repository           = $repository;
        $this->request              = $request;
        $this->current_user         = $this->request->getCurrentUser();

        $gerrit_status = new GitViews_ShowRepo_ContentGerritStatus(
            $this->driver_factory,
            $this->gerrit_servers,
            $this->repository,
            new Git_Driver_Gerrit_ProjectCreatorStatus(
                new Git_Driver_Gerrit_ProjectCreatorStatusDao()
            )
        );

        $header = new GitViewHeader(
            EventManager::instance(),
            $this->permissions_manager
        );

        $header->header($this->request, $this->request->getCurrentUser(), $this->layout, $this->repository->getProject());

        $html  = '';
        $html .= '<div id="plugin_git_reference" class="plugin_git_repo_type_'. $this->repository->getBackendType() .'">';
        $html .= $this->getHeader();
        $html .= $gerrit_status->getContent();
        $html .= $this->getCloneUrl();
        $html .= '</div>';

        echo $html;
    }

    private function getHeader()
    {
        $html         = '';
        $parent       = $this->repository->getParent();
        $access       = $this->repository->getAccess();

        // Access type
        $accessType      = $this->getAccessType($access, $this->repository->getBackend() instanceof Git_Backend_Gitolite);
        $additional_info = $this->getAdditionalHeaderInfo();
        $index_url       = $this->url_manager->getRepositoryBaseUrl($this->repository);

        $html .= '<h1><a class="git-repo-name" href="'.$index_url.'">'.$accessType.$this->repository->getFullName().'</a> ' . $additional_info . '</h1>';

        if (!empty($parent)) {
            $html .= '<div id="plugin_git_repo_parent">';
            $html .= $GLOBALS['Language']->getText('plugin_git', 'view_repo_parent_'. $this->repository->getBackendType(), $parent->getHTMLLink($this->url_manager));
            $html .= '</div>';
        }
        return $html;
    }

    private function getAdditionalHeaderInfo()
    {
        $info   = '';
        $params = array(
            'repository' => $this->repository,
            'info'       => &$info
        );

        EventManager::instance()->processEvent(GIT_ADDITIONAL_INFO, $params);

        return $info;
    }

    /**
     * Fetch the html code to display the icon of a repository (depends on type of project)
     *
     * @param $access
     * @param $backend_type
     */
    private function getAccessType($access, $backendIsGitolite)
    {
        if ($backendIsGitolite) {
            return '';
        }
        $html = '<span class="plugin_git_repo_privacy" title=';
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
        $html .= '</span>';
        return $html;
    }

    private function getCloneUrl()
    {
        $mirrors              = $this->mirror_data_mapper->fetchAllRepositoryMirrors($this->repository);
        $additional_actions   = $this->getAdditionalActions();
        $additional_help_text = $this->getAdditionalHelpText();

        $is_admin = $this->permissions_manager->userIsGitAdmin($this->current_user, $this->repository->getProject()) ||
            $this->repository->belongsTo($this->current_user);

        $presenter     = new RepositoryClonePresenter(
            $this->repository,
            $this->getAccessURLs(),
            $mirrors,
            $is_admin,
            $this->getMasterLocationName(),
            $additional_actions,
            $additional_help_text
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(GIT_TEMPLATE_DIR);

        return $renderer->renderToString($presenter->getTemplateName(), $presenter);
    }

    private function getAdditionalActions()
    {
        $actions = '';
        $params  = array(
            'repository' => $this->repository,
            'user'       => $this->current_user,
            'actions'    => &$actions
        );

        EventManager::instance()->processEvent(GIT_ADDITIONAL_ACTIONS, $params);

        return $actions;
    }

    private function getAdditionalHelpText()
    {
        $html   = '';
        $params = array(
            'repository' => $this->repository,
            'html'       => &$html
        );

        EventManager::instance()->processEvent(GIT_ADDITIONAL_HELP_TEXT, $params);

        return $html;
    }

    private function getMasterLocationName()
    {
        $name = $this->master_location_name;
        if (! $name) {
            $name = $GLOBALS['Language']->getText('plugin_git', 'default_location');
        }

        return $name;
    }

    private function getAccessURLs()
    {
        $urls = $this->repository->getAccessURL();
        if ($this->repository->isMigratedToGerrit()) {
            $gerrit_user    = $this->gerrit_usermanager->getGerritUser($this->current_user);
            $gerrit_server  = $this->gerrit_servers[$this->repository->getRemoteServerId()];
            $driver         = $this->driver_factory->getDriver($gerrit_server);
            $gerrit_project = $driver->getGerritProjectName($this->repository);

            $clone_url = $gerrit_server->getEndUserCloneUrl($gerrit_project, $gerrit_user);
            $this->prependGerritCloneURL($urls, $clone_url);
        }
        return $urls;
    }

    private function prependGerritCloneURL(array &$urls, $gerrit_clone_url)
    {
        $gerrit = array('gerrit' => $gerrit_clone_url);
        $urls = array_merge($gerrit, $urls);
    }
}
