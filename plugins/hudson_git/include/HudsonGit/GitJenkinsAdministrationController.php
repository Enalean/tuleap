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

namespace Tuleap\HudsonGit;

use Git_Mirror_MirrorDataMapper;
use GitPermissionsManager;
use GitPlugin;
use HTTPRequest;
use Project;
use ProjectManager;
use TemplateRenderer;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class GitJenkinsAdministrationController implements DispatchableWithRequest, DispatchableWithProject
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

    public function __construct(
        ProjectManager $project_manager,
        GitPermissionsManager $git_permissions_manager,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        HeaderRenderer $header_renderer,
        TemplateRenderer $renderer
    ) {
        $this->project_manager         = $project_manager;
        $this->git_permissions_manager = $git_permissions_manager;
        $this->renderer                = $renderer;
        $this->header_renderer         = $header_renderer;
        $this->mirror_data_mapper      = $mirror_data_mapper;
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

        $this->header_renderer->renderServiceAdministrationHeader(
            $request,
            $user,
            $project
        );

        $this->renderer->renderToPage(
            'git-administration-jenkins',
            new GitJenkinsAdministrationPresenter(
                (int) $project->getID(),
                count($this->mirror_data_mapper->fetchAllForProject($project)) > 0,
                [
                    GitJenkinsAdministrationPaneBuilder::buildActivePane($project)
                ]
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
}
