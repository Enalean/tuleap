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
use GitPermissionsManager;
use GitPlugin;
use HTTPRequest;
use Project;
use ProjectManager;
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRenderer;
use Tuleap\Git\Events\GitAdminGetExternalPanePresenters;
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
    public function __construct(
        private ProjectManager $project_manager,
        private GitPermissionsManager $git_permissions_manager,
        private JenkinsServerFactory $jenkins_server_factory,
        private LogFactory $log_factory,
        private HeaderRenderer $header_renderer,
        private TemplateRenderer $renderer,
        private IncludeAssets $include_assets,
        private EventDispatcherInterface $event_manager,
    ) {
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);

        if (! $project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext('tuleap-git', 'Git service is disabled.'));
        }

        $user = $request->getCurrentUser();
        if (! $this->git_permissions_manager->userIsGitAdmin($user, $project)) {
            throw new ForbiddenException(dgettext('tuleap-hudson_git', 'User is not Git administrator.'));
        }

        $layout->includeFooterJavascriptFile(
            $this->include_assets->getFileURL('git-administration.js')
        );

        $this->header_renderer->renderServiceAdministrationHeader(
            $request,
            $user,
            $project
        );

        $event = new GitAdminGetExternalPanePresenters($project, AdministrationPaneBuilder::PANE_NAME);
        $this->event_manager->dispatch($event);

        $this->renderer->renderToPage(
            'git-administration-jenkins',
            new AdministrationPresenter(
                (int) $project->getID(),
                $event->getExternalPanePresenters(),
                $this->buildServerPresenters($project),
                new CSRFSynchronizerToken(
                    URLBuilder::buildAddUrl()
                )
            )
        );

        $layout->footer([]);
    }

    /**
     * @throws NotFoundException
     */
    #[\Override]
    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProjectByCaseInsensitiveUnixName($variables['project_name']);
        if (! $project || $project->isError()) {
            throw new NotFoundException(dgettext('tuleap-git', 'Project not found.'));
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
