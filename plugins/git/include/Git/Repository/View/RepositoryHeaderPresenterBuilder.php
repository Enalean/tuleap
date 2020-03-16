<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

namespace Tuleap\Git\Repository\View;

use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Driver_Gerrit_UserAccountManager;
use Git_GitRepositoryUrlManager;
use Git_Mirror_MirrorDataMapper;
use Git_RemoteServer_GerritServer;
use GitDao;
use GitPermissionsManager;
use GitRepository;
use PFUser;

class RepositoryHeaderPresenterBuilder
{
    public const TAB_FILES   = 'tab-files';
    public const TAB_COMMITS = 'tab-commits';

    /**
     * @var Git_GitRepositoryUrlManager
     */
    private $url_manager;

    /**
     * @var Git_Driver_Gerrit_GerritDriverFactory
     */
    private $driver_factory;

    /**
     * @var Git_Driver_Gerrit_UserAccountManager
     */
    private $gerrit_usermanager;

    /**
     * @var Git_RemoteServer_GerritServer[]
     */
    private $gerrit_servers;

    /**
     * @var GitPermissionsManager
     */
    private $permissions_manager;

    /**
     * @var Git_Driver_Gerrit_ProjectCreatorStatus
     */
    private $project_creator_status;
    /**
     * @var Git_Mirror_MirrorDataMapper
     */
    private $mirror_data_mapper;
    private $selected_tab;
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var GitDao
     */
    private $dao;
    /**
     * @var DefaultCloneURLSelector
     */
    private $default_clone_url_selector;

    public function __construct(
        GitDao $dao,
        Git_GitRepositoryUrlManager $url_manager,
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        Git_Driver_Gerrit_ProjectCreatorStatus $project_creator_status,
        Git_Driver_Gerrit_UserAccountManager $gerrit_usermanager,
        GitPermissionsManager $permissions_manager,
        array $gerrit_servers,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        $selected_tab,
        \EventManager $event_manager,
        DefaultCloneURLSelector $default_clone_url_selector
    ) {
        $this->dao                        = $dao;
        $this->url_manager                = $url_manager;
        $this->driver_factory             = $driver_factory;
        $this->project_creator_status     = $project_creator_status;
        $this->gerrit_usermanager         = $gerrit_usermanager;
        $this->permissions_manager        = $permissions_manager;
        $this->gerrit_servers             = $gerrit_servers;
        $this->mirror_data_mapper         = $mirror_data_mapper;
        $this->selected_tab               = $selected_tab;
        $this->event_manager              = $event_manager;
        $this->default_clone_url_selector = $default_clone_url_selector;
    }

    /** @return RepositoryHeaderPresenter */
    public function build(GitRepository $repository, PFUser $current_user)
    {
        $parent_repository_presenter = null;
        $parent_repository = $repository->getParent();
        if (! empty($parent_repository)) {
            $parent_repository_presenter = $this->buildParentPresenter($parent_repository);
        }

        $gerrit_status_presenter = $this->buildGerritStatusPresenter($repository);
        $clone_presenter         = $this->buildClonePresenter($repository, $current_user);

        $is_admin = $this->permissions_manager->userIsGitAdmin($current_user, $repository->getProject()) ||
            $repository->belongsTo($current_user);

        $admin_url = $this->url_manager->getRepositoryAdminUrl($repository);
        $fork_url  = $this->url_manager->getForkUrl($repository);

        return new RepositoryHeaderPresenter(
            $repository,
            $is_admin,
            $admin_url,
            $fork_url,
            $current_user,
            $clone_presenter,
            $gerrit_status_presenter,
            $this->getAlreadyForkedRepositoriesPresenters($repository, $current_user),
            $this->buildTabsPresenter($repository),
            $parent_repository_presenter
        );
    }

    private function buildParentPresenter(GitRepository $parent_repository)
    {
        return new ParentRepositoryPresenter(
            $parent_repository,
            $this->url_manager->getRepositoryBaseUrl($parent_repository)
        );
    }

    private function buildGerritStatusPresenter(GitRepository $repository)
    {
        return new GerritStatusPresenter(
            $repository,
            $this->project_creator_status,
            $this->driver_factory,
            $this->gerrit_servers
        );
    }

    private function buildClonePresenter(GitRepository $repository, PFUser $current_user)
    {
        $access_urls = $repository->getAccessURL();
        $clone_urls = new CloneURLs();
        if (isset($access_urls['ssh'])) {
            $clone_urls->setSshUrl($access_urls['ssh']);
        }
        if (isset($access_urls['http'])) {
            $clone_urls->setHttpsUrl($access_urls['http']);
        }
        if ($repository->isMigratedToGerrit()) {
            $gerrit_user    = $this->gerrit_usermanager->getGerritUser($current_user);
            $gerrit_server  = $this->gerrit_servers[$repository->getRemoteServerId()];
            $driver         = $this->driver_factory->getDriver($gerrit_server);
            $gerrit_project = $driver->getGerritProjectName($repository);

            $clone_url = $gerrit_server->getEndUserCloneUrl($gerrit_project, $gerrit_user);
            $clone_urls->setGerritUrl($clone_url);
        }

        $mirrors = $this->mirror_data_mapper->fetchAllRepositoryMirrors($repository);
        $clone_urls->setMirrors($mirrors);

        $clone_presenter = new ClonePresenter($this->default_clone_url_selector);
        $clone_presenter->build($clone_urls, $repository, $current_user);
        return $clone_presenter;
    }

    private function buildTabsPresenter(GitRepository $repository)
    {
        $tabs = [$this->getFilesTab($repository), $this->getCommitsTab($repository)];

        $external_tabs = $this->getExternalsTabs($repository);
        if (count($external_tabs) > 0) {
            $tabs = array_merge($tabs, $external_tabs);
        }

        return $tabs;
    }

    private function getFilesTab(GitRepository $repository)
    {
        $is_selected = $this->selected_tab === self::TAB_FILES;

        return new TabPresenter(
            $is_selected,
            $this->url_manager->getRepositoryBaseUrl($repository),
            dgettext("tuleap-git", "Files"),
            self::TAB_FILES,
            false,
            0
        );
    }


    private function getCommitsTab(GitRepository $repository)
    {
        $is_selected = $this->selected_tab === self::TAB_COMMITS;

        return new TabPresenter(
            $is_selected,
            $this->url_manager->getRepositoryBaseUrl($repository) . '?' . http_build_query(
                [
                    'a' => 'shortlog'
                ]
            ),
            dgettext("tuleap-git", "Commits"),
            self::TAB_COMMITS,
            false,
            0
        );
    }

    private function getExternalsTabs(GitRepository $repository)
    {
        $event = new RepositoryExternalNavigationTabsCollector($repository, $this->selected_tab);
        $this->event_manager->processEvent($event);

        return $event->getExternalTabs();
    }

    private function getAlreadyForkedRepositoriesPresenters(GitRepository $repository, PFUser $current_user)
    {
        $project_name = $repository->getProject()->getUnixName();

        return array_map(
            function ($row) use ($project_name) {
                $path = "${row['repository_namespace']}/${row['repository_name']}";
                return new ForkedRepositoryPresenter(
                    GIT_BASE_URL . "/$project_name/$path",
                    $path
                );
            },
            $this->dao->getForksOfRepositoryForUser($repository->getId(), $current_user->getId())
        );
    }
}
