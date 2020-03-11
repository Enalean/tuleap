<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\HudsonGit\Git\Administration;

use CSRFSynchronizerToken;
use Git_Mirror_MirrorDataMapper;
use GitPermissionsManager;
use GitPlugin;
use HTTPRequest;
use Project;
use ProjectManager;
use TemplateRenderer;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\HudsonGit\Log\LogFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class AdministrationController implements DispatchableWithRequest, DispatchableWithProject
{
    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var GitPermissionsManager
     */
    private $git_permissions_manager;

    /**
     * @var TemplateRenderer
     */
    private $renderer;

    /**
     * @var HeaderRenderer
     */
    private $header_renderer;

    /**
     * @var Git_Mirror_MirrorDataMapper
     */
    private $mirror_data_mapper;

    /**
     * @var JenkinsServerFactory
     */
    private $jenkins_server_factory;

    /**
     * @var IncludeAssets
     */
    private $include_assets;

    /**
     * @var LogFactory
     */
    private $log_factory;

    public function __construct(
        ProjectManager $project_manager,
        GitPermissionsManager $git_permissions_manager,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        JenkinsServerFactory $jenkins_server_factory,
        LogFactory $log_factory,
        HeaderRenderer $header_renderer,
        TemplateRenderer $renderer,
        IncludeAssets $include_assets
    ) {
        $this->project_manager         = $project_manager;
        $this->git_permissions_manager = $git_permissions_manager;
        $this->renderer                = $renderer;
        $this->header_renderer         = $header_renderer;
        $this->mirror_data_mapper      = $mirror_data_mapper;
        $this->jenkins_server_factory  = $jenkins_server_factory;
        $this->include_assets          = $include_assets;
        $this->log_factory             = $log_factory;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);

        if (! $project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext("tuleap-git", "Git service is disabled."));
        }

        $user = $request->getCurrentUser();
        if (! $this->git_permissions_manager->userIsGitAdmin($user, $project)) {
            throw new ForbiddenException(dgettext("tuleap-hudson_git", 'User is not Git administrator.'));
        }

        $layout->includeFooterJavascriptFile(
            $this->include_assets->getFileURL('git-administration.js')
        );

        $this->header_renderer->renderServiceAdministrationHeader(
            $request,
            $user,
            $project
        );

        $this->renderer->renderToPage(
            'git-administration-jenkins',
            new AdministrationPresenter(
                (int) $project->getID(),
                count($this->mirror_data_mapper->fetchAllForProject($project)) > 0,
                [
                    AdministrationPaneBuilder::buildActivePane($project)
                ],
                $this->buildServerPresenters($project),
                new CSRFSynchronizerToken(
                    URLBuilder::buildAddUrl()
                )
            )
        );

        $layout->footer(array());
    }

    /**
     * @throws NotFoundException
     */
    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProjectByCaseInsensitiveUnixName($variables['project_name']);
        if (! $project || $project->isError()) {
            throw new NotFoundException(dgettext("tuleap-git", "Project not found."));
        }

        return $project;
    }

    private function buildServerPresenters(Project $project): array
    {
        $presenters = [];
        foreach ($this->jenkins_server_factory->getJenkinsServerOfProject($project) as $jenkins_server) {
            $presenters[] = new JenkinsServerPresenter(
                $jenkins_server,
                $this->buildServerLogsPresenters($jenkins_server)
            );
        }

        return $presenters;
    }

    private function buildServerLogsPresenters(JenkinsServer $jenkins_server): array
    {
        $presenters = [];
        foreach ($this->log_factory->getLastJobLogsByProjectServer($jenkins_server) as $log) {
            $presenters[] = JenkinsServerLogsPresenter::buildFromLog($log);
        }

        return $presenters;
    }
}
