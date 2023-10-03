<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Git\RepositoryList;

use EventManager;
use GitPlugin;
use HTTPRequest;
use Project;
use TemplateRendererFactory;
use Tuleap\Event\Events\ProjectProviderEvent;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Request;

class GitRepositoryListController implements Request\DispatchableWithRequest, Request\DispatchableWithProject, Request\DispatchableWithBurningParrot
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var Project
     */
    private $project;
    private JavascriptViteAsset $asset;
    /**
     * @var ListPresenterBuilder
     */
    private $list_presenter_builder;

    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        \ProjectManager $project_manager,
        ListPresenterBuilder $list_presenter_builder,
        JavascriptViteAsset $asset,
        EventManager $event_manager,
    ) {
        $this->project_manager        = $project_manager;
        $this->list_presenter_builder = $list_presenter_builder;
        $this->asset                  = $asset;
        $this->event_manager          = $event_manager;
    }

    /**
     * @param array       $variables
     *
     * @throws Request\NotFoundException
     */
    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProjectByCaseInsensitiveUnixName($variables['project_name']);
        if (! $project || $project->isError()) {
            throw new Request\NotFoundException(dgettext("tuleap-git", "Project not found."));
        }

        $this->project = $project;

        return $this->project;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array       $variables
     *
     * @return void
     * @throws Request\NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $this->project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            throw new Request\NotFoundException(dgettext("tuleap-git", "Git service is disabled."));
        }

        \Tuleap\Project\ServiceInstrumentation::increment('git');

        $event = new ProjectProviderEvent($this->project);
        $this->event_manager->processEvent($event);

        $layout->addJavascriptAsset($this->asset);
        $this->displayHeader(dgettext('tuleap-git', 'Git repositories'), $this->project);
        $renderer = TemplateRendererFactory::build()->getRenderer(GIT_TEMPLATE_DIR);

        $renderer->renderToPage(
            'repositories/repository-list',
            $this->list_presenter_builder->build($this->project, $request->getCurrentUser())
        );

        site_project_footer([]);
    }

    private function displayHeader(string $title, Project $project): void
    {
        $params = HeaderConfigurationBuilder::get($title . ' - ' . $project->getPublicName())
            ->inProjectNotInBreadcrumbs($project, GitPlugin::SERVICE_SHORTNAME)
            ->build();

        site_project_header($project, $params);
    }
}
