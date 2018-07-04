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
 */

namespace Tuleap\Git\RepositoryList;

use HTTPRequest;
use Project;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request;

class GitRepositoryListController implements Request\DispatchableWithRequest, Request\DispatchableWithProject
{

    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var \GitRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var \GitPermissionsManager
     */
    private $git_permissions_manager;
    /**
     * @var Project
     */
    private $project;

    public function __construct(
        \ProjectManager $project_manager,
        \GitRepositoryFactory $repository_factory,
        \GitPermissionsManager $git_permissions_manager
    ) {
        $this->project_manager         = $project_manager;
        $this->repository_factory      = $repository_factory;
        $this->git_permissions_manager = $git_permissions_manager;
    }

    /**
     * @param HTTPRequest $request
     * @param array $variables
     *
     * @throws Request\NotFoundException
     *
     * @return null|Project
     */
    public function getProject(HTTPRequest $request, array $variables)
    {
        $this->project = $this->project_manager->getProjectByCaseInsensitiveUnixName($variables['project_name']);
        if (! $this->project || $this->project->isError()) {
            throw new Request\NotFoundException(dgettext("tuleap-git", "Project not found."));
        }
        return $this->project;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param HTTPRequest $request
     * @param BaseLayout  $layout
     * @param array       $variables
     *
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('git');

        $this->displayHeader(dgettext('tuleap-git', 'Git repositories'), $this->project);
        $renderer = TemplateRendererFactory::build()->getRenderer(GIT_TEMPLATE_DIR);

        $renderer->renderToPage(
            'repositories/repository-list',
            new GitRepositoryListPresenter(
                $this->project,
                $this->git_permissions_manager->userIsGitAdmin($request->getCurrentUser(), $this->project)
            )
        );

        site_project_footer([]);
    }

    private function displayHeader($title, Project $project)
    {
        $params = [
            'title'      => $title . ' - ' . $project->getUnconvertedPublicName(),
            'toptab'     => 'git',
            'group'      => $project->getID(),
            'body_class' => []
        ];

        site_project_header($params);
    }
}
