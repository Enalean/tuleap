<?php
/**
 * Copyright (c) Enalean, 2013-2017. All Rights Reserved.
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

use Tuleap\Markdown\ContentInterpretor;
use Tuleap\Git\History\GitPhpAccessLogger;

class GitViews_ShowRepo_Content {

    const PAGE_TYPE       = 'a';
    const PAGE_TYPE_TREE  = 'tree';
    const FOLDER_TREE     = 'f';
    const OLD_COMMIT_TREE = 'hb';

    /**
     * @var Codendi_Request
     */
    private $request;

    /**
     * @var GitRepository
     */
    protected $repository;

    /**
     * @var GitViews_GitPhpViewer
     */
    private $gitphp_viewer;

    /**
     * @var PFUser
     */
    private $current_user;

    /**
     * @var Git
     */
    private $controller;

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

    public function __construct(
        GitRepository $repository,
        GitViews_GitPhpViewer $gitphp_viewer,
        Codendi_Request $request,
        PFUser $current_user,
        Git_GitRepositoryUrlManager $url_manager,
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        Git_Driver_Gerrit_UserAccountManager $gerrit_usermanager,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        GitPhpAccessLogger $access_loger,
        GitPermissionsManager $permissions_manager,
        array $gerrit_servers,
        $master_location_name
    ) {
        $this->repository         = $repository;
        $this->gitphp_viewer      = $gitphp_viewer;
        $this->request            = $request;
        $this->current_user       = $current_user;
        $this->driver_factory     = $driver_factory;
        $this->gerrit_usermanager = $gerrit_usermanager;
        $this->gerrit_servers     = $gerrit_servers;
        $this->mirror_data_mapper = $mirror_data_mapper;
        $this->url_manager        = $url_manager;
        $this->access_loger       = $access_loger;
        $this->permissions_manager  = $permissions_manager;
        $this->master_location_name = $master_location_name;
    }

    public function display() {
        $this->displayHeader();
        $this->displayContent();
    }

    private function displayContent() {
        $additional_view = $this->getAdditionalView();

        if ($additional_view) {
            echo $additional_view;
        } else {
            $this->displayRepositoryContent();
        }
    }

    private function getAdditionalView() {
        $view   = null;
        $params = array(
            'repository' => $this->repository,
            'user'       => $this->request->getCurrentUser(),
            'request'    => $this->request,
            'view'       => &$view
        );

        EventManager::instance()->processEvent(GIT_VIEW, $params);

        return $view;
    }

    private function displayRepositoryContent() {
        $html = '';

        if ($this->repository->isCreated()) {
            $is_download = false;
            $html       .= $this->gitphp_viewer->getContent($is_download);

            $this->access_loger->logAccess($this->repository, $this->request->getCurrentUser());
        } else {
            $html .= $this->getWaitingForRepositoryCreationInfo();
        }
        if ($this->isATreePage()) {
            $html .= $this->getMarkdownFilesDiv();
        }

        echo $html;
    }

    private function displayHeader() {
        $gerrit_status = new GitViews_ShowRepo_ContentGerritStatus(
            $this->driver_factory,
            $this->gerrit_servers,
            $this->repository,
            new Git_Driver_Gerrit_ProjectCreatorStatus(
                new Git_Driver_Gerrit_ProjectCreatorStatusDao()
            )
        );

        $html  = '';
        $html .= '<div id="plugin_git_reference" class="plugin_git_repo_type_'. $this->repository->getBackendType() .'">';
        $html .= $this->getHeader();
        $html .= $gerrit_status->getContent();
        $html .= $this->getCloneUrl();
        $html .= '</div>';

        echo $html;
    }

    private function isATreePage() {
        return $this->request->get(self::PAGE_TYPE) === self::PAGE_TYPE_TREE;
    }

    private function getMarkdownFilesDiv() {
        $commit_sha1       = $this->getCurrentCommitSha1();
        $node              = $this->getCurrentNode();
        $repository_path   = ForgeConfig::get('sys_data_dir') . '/gitolite/repositories/' . $this->repository->getPath();
        $git_markdown_file = new GitMarkdownFile(
            new Git_Exec($repository_path, $repository_path),
            new ContentInterpretor()
        );

        $readme_file = $git_markdown_file->getReadmeFileContent($node, $commit_sha1);

        if ($readme_file) {
            $presenter = new ReadmeMarkdownPresenter($readme_file['file_name'], $readme_file['file_content']);
            $renderer  = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');

            return $renderer->renderToString('readme_markdown', $presenter);
        }
    }

    private function getCurrentNode() {
        if ($this->request->exist(self::FOLDER_TREE)) {
            return $this->request->get(self::FOLDER_TREE).'/';
        }

        return '';
    }

    private function getCurrentCommitSha1() {
        if ($this->request->exist(self::OLD_COMMIT_TREE)) {
            return $this->request->get(self::OLD_COMMIT_TREE);
        }

        return 'HEAD';
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

        // Access type
        $accessType      = $this->getAccessType($access, $this->repository->getBackend() instanceof Git_Backend_Gitolite);
        $additional_info = $this->getAdditionalHeaderInfo();
        $index_url       = $this->url_manager->getRepositoryBaseUrl($this->repository);

        $html .= '<h1><a class="git-repo-name" href="'.$index_url.'">'.$accessType.$this->repository->getFullName().'</a> ' . $additional_info . '</h1>';

        if ( !empty($parent) ) {
            $html .= '<div id="plugin_git_repo_parent">';
            $html .= $GLOBALS['Language']->getText('plugin_git', 'view_repo_parent_'. $this->repository->getBackendType(), $parent->getHTMLLink($this->url_manager));
            $html .= '</div>';
        }
        return $html;
    }

    private function getAdditionalHeaderInfo() {
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
    private function getAccessType($access, $backendIsGitolite) {
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

    private function getCloneUrl() {
        $mirrors              = $this->mirror_data_mapper->fetchAllRepositoryMirrors($this->repository);
        $additional_actions   = $this->getAdditionalActions();
        $additional_help_text = $this->getAdditionalHelpText();

        $presenter = new RepositoryClonePresenter(
            $this->repository,
            $this->getAccessURLs(),
            $mirrors,
            $this->permissions_manager->userIsGitAdmin($this->request->getCurrentUser(), $this->repository->getProject()),
            $this->getMasterLocationName(),
            $additional_actions,
            $additional_help_text
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(GIT_TEMPLATE_DIR);

        return $renderer->renderToString($presenter->getTemplateName(), $presenter);
    }

    private function getAdditionalActions() {
        $actions = '';
        $params  = array(
            'repository' => $this->repository,
            'user'       => $this->current_user,
            'actions'    => &$actions
        );

        EventManager::instance()->processEvent(GIT_ADDITIONAL_ACTIONS, $params);

        return $actions;
    }

    private function getAdditionalHelpText() {
        $html   = '';
        $params = array(
            'repository' => $this->repository,
            'html'       => &$html
        );

        EventManager::instance()->processEvent(GIT_ADDITIONAL_HELP_TEXT, $params);

        return $html;
    }

    private function getMasterLocationName() {
        $name = $this->master_location_name;
        if (! $name) {
            $name = $GLOBALS['Language']->getText('plugin_git', 'default_location');
        }

        return $name;
    }

    private function getAccessURLs() {
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

    private function prependGerritCloneURL(array &$urls, $gerrit_clone_url) {
        $gerrit = array('gerrit' => $gerrit_clone_url);
        $urls = array_merge($gerrit, $urls);
    }

    private function getWaitingForRepositoryCreationInfo() {
        $html = '<div class="alert alert-info wait_creation">';
        $html .= $GLOBALS['Language']->getText('plugin_git', 'waiting_for_repo_creation');

        $default_mirrors = $this->mirror_data_mapper->fetchAllRepositoryMirrors($this->repository);

        if ($default_mirrors) {
            $default_mirrors_names = array_map(
                array($this, 'extractMirrorName'),
                $default_mirrors
            );

            $html .= '<br/>';
            $html .= $GLOBALS['Language']->getText(
                'plugin_git',
                'waiting_for_repo_creation_default_mirrors',
                implode(', ', $default_mirrors_names)
            );
        }

        $html .= '</div>';
        return $html;
    }

    private function extractMirrorName(Git_Mirror_Mirror $mirror) {
        $purifier = Codendi_HTMLPurifier::instance();

        return $purifier->purify($mirror->name);
    }

}
