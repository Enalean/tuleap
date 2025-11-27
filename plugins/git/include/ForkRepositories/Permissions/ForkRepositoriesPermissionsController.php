<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Git\ForkRepositories\Permissions;

use CSRFSynchronizerToken;
use Feedback;
use GitPlugin;
use Project;
use TemplateRenderer;
use Tuleap\Git\ForkRepositories\ForkRepositoriesUrlsBuilder;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetGeneric;
use Tuleap\NeverThrow\Fault;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Project\ProjectByUnixNameFactory;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

final readonly class ForkRepositoriesPermissionsController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    public function __construct(
        private ProjectByUnixNameFactory $retrieve_project_by_unix_name,
        private ProjectByIDFactory $retrieve_projects_by_id,
        private HeaderRenderer $header_renderer,
        private CssAssetGeneric $css_assets,
        private ForkRepositoriesFormInputsBuilder $inputs_builder,
        private TemplateRenderer $template_renderer,
        private ForkRepositoriesPermissionsPresenterBuilder $presenter_builder,
    ) {
    }

    #[\Override]
    public function getProject(array $variables): Project
    {
        $project = $this->retrieve_project_by_unix_name->getProjectByCaseInsensitiveUnixName($variables['project_name']);
        if (! $project || $project->isError()) {
            throw new NotFoundException(dgettext('tuleap-git', 'Project not found.'));
        }

        return $project;
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);
        if (! $project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext('tuleap-git', 'Git service is disabled.'));
        }
        $user = $request->getCurrentUser();

        $this->inputs_builder->fromRequest($request, $user)->match(
            function ($inputs) use ($layout, $request, $project, $user) {
                $destination_project = $inputs->destination_project_id !== null
                    ? $this->retrieve_projects_by_id->getProjectById((int) $inputs->destination_project_id)
                    : $project;

                $layout->addCssAsset($this->css_assets);
                $this->header_renderer->renderDefaultHeader($request, $user, $project);
                $this->template_renderer->renderToPage(
                    'fork-repositories-permissions',
                    $this->presenter_builder->build(
                        $destination_project,
                        $user,
                        $inputs,
                        new CSRFSynchronizerToken(ForkRepositoriesUrlsBuilder::buildGETForksAndDestinationSelectionURL($destination_project)),
                    ),
                );
                $layout->footer([]);
            },
            function (Fault $fault) use ($layout, $project) {
                $layout->addFeedback(Feedback::ERROR, (string) $fault);
                $layout->redirect(ForkRepositoriesUrlsBuilder::buildGETForksAndDestinationSelectionURL($project));
            }
        );
    }
}
