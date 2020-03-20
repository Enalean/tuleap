<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Repository;

use DefaultProjectMirrorDao;
use EventManager;
use Git_Driver_Gerrit_GerritDriverFactory;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Driver_Gerrit_ProjectCreatorStatusDao;
use Git_Driver_Gerrit_UserAccountManager;
use Git_Gitolite_GitoliteRCReader;
use Git_GitRepositoryUrlManager;
use Git_Mirror_MirrorDao;
use Git_Mirror_MirrorDataMapper;
use Git_PermissionsDao;
use Git_RemoteServer_Dao;
use Git_RemoteServer_GerritServerFactory;
use Git_SystemEventManager;
use GitDao;
use GitPermissionsManager;
use GitRepositoryFactory;
use Plugin;
use ProjectManager;
use SystemEventManager;
use Tuleap\Git\BreadCrumbDropdown\GitCrumbBuilder;
use Tuleap\Git\BreadCrumbDropdown\RepositoryCrumbBuilder;
use Tuleap\Git\BreadCrumbDropdown\RepositorySettingsCrumbBuilder;
use Tuleap\Git\BreadCrumbDropdown\ServiceAdministrationCrumbBuilder;
use Tuleap\Git\Gitolite\VersionDetector;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Repository\View\DefaultCloneURLSelector;
use Tuleap\Git\Repository\View\RepositoryHeaderPresenterBuilder;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Layout\IncludeAssets;
use UserManager;

class GitRepositoryHeaderDisplayerBuilder
{
    public function build($selected_tab)
    {
        $git_plugin = \PluginManager::instance()->getPluginByName('git');
        return new GitRepositoryHeaderDisplayer(
            $this->getHeaderRenderer($git_plugin),
            $this->getRepositoryHeaderPresenterBuilder($git_plugin, $selected_tab),
            $this->getIncludeAssets(),
            EventManager::instance()
        );
    }

    private function getHeaderRenderer(Plugin $git_plugin)
    {
        $service_crumb_builder        = new GitCrumbBuilder($this->getGitPermissionsManager(), $git_plugin->getPluginPath());
        $settings_crumb_builder       = new RepositorySettingsCrumbBuilder($git_plugin->getPluginPath());
        $administration_crumb_builder = new ServiceAdministrationCrumbBuilder($git_plugin->getPluginPath());

        $repository_crumb_builder = new RepositoryCrumbBuilder(
            $this->getGitRepositoryUrlManager($git_plugin),
            $this->getGitPermissionsManager(),
            $git_plugin->getPluginPath()
        );

        return new HeaderRenderer(
            EventManager::instance(),
            $service_crumb_builder,
            $administration_crumb_builder,
            $repository_crumb_builder,
            $settings_crumb_builder
        );
    }

    private function getGitPermissionsManager()
    {
        return new GitPermissionsManager(
            new Git_PermissionsDao(),
            $this->getGitSystemEventManager(),
            $this->getFineGrainedDao(),
            $this->getFineGrainedRetriever()
        );
    }

    private function getGitSystemEventManager()
    {
        return new Git_SystemEventManager(SystemEventManager::instance(), $this->getRepositoryFactory());
    }

    private function getRepositoryFactory()
    {
        return new GitRepositoryFactory($this->getGitDao(), ProjectManager::instance());
    }

    private function getGitDao()
    {
        return new GitDao();
    }

    private function getFineGrainedDao()
    {
        return new FineGrainedDao();
    }

    private function getFineGrainedRetriever()
    {
        $dao = $this->getFineGrainedDao();
        return new FineGrainedRetriever($dao);
    }

    private function getGitRepositoryUrlManager(Plugin $git_plugin)
    {
        return new Git_GitRepositoryUrlManager($git_plugin, new \Tuleap\InstanceBaseURLBuilder());
    }

    private function getRepositoryHeaderPresenterBuilder(Plugin $git_plugin, $selected_tab)
    {
        return new RepositoryHeaderPresenterBuilder(
            $this->getGitDao(),
            $this->getGitRepositoryUrlManager($git_plugin),
            $this->getGerritDriverFactory(),
            $this->getProjectCreatorStatus(),
            new Git_Driver_Gerrit_UserAccountManager($this->getGerritDriverFactory(), $this->getGerritServerFactory()),
            $this->getGitPermissionsManager(),
            $this->getGerritServerFactory()->getServers(),
            $this->getMirrorDataMapper(),
            $selected_tab,
            EventManager::instance(),
            new DefaultCloneURLSelector()
        );
    }

    private function getGerritDriverFactory()
    {
        return new Git_Driver_Gerrit_GerritDriverFactory(
            new \Tuleap\Git\Driver\GerritHTTPClientFactory(HttpClientFactory::createClient()),
            \Tuleap\Http\HTTPFactoryBuilder::requestFactory(),
            \Tuleap\Http\HTTPFactoryBuilder::streamFactory(),
            \BackendLogger::getDefaultLogger(\GitPlugin::LOG_IDENTIFIER),
        );
    }

    private function getProjectCreatorStatus()
    {
        $dao = new Git_Driver_Gerrit_ProjectCreatorStatusDao();

        return new Git_Driver_Gerrit_ProjectCreatorStatus($dao);
    }

    private function getGerritServerFactory()
    {
        return new Git_RemoteServer_GerritServerFactory(
            new Git_RemoteServer_Dao(),
            $this->getGitDao(),
            $this->getGitSystemEventManager(),
            ProjectManager::instance()
        );
    }

    private function getIncludeAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../../../src/www/assets/git',
            '/assets/git'
        );
    }

    private function getMirrorDataMapper()
    {
        return new Git_Mirror_MirrorDataMapper(
            new Git_Mirror_MirrorDao(),
            UserManager::instance(),
            new GitRepositoryFactory(
                new GitDao(),
                ProjectManager::instance()
            ),
            ProjectManager::instance(),
            $this->getGitSystemEventManager(),
            new Git_Gitolite_GitoliteRCReader(new VersionDetector()),
            new DefaultProjectMirrorDao()
        );
    }
}
