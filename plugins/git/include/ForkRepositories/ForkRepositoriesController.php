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

namespace Tuleap\Git\ForkRepositories;

use CSRFSynchronizerToken;
use GitPlugin;
use HTTPRequest;
use Project;
use TemplateRenderer;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\Project\ProjectByUnixNameFactory;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

final readonly class ForkRepositoriesController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    public function __construct(
        private ProjectByUnixNameFactory $project_manager,
        private HeaderRenderer $header_renderer,
        private JavascriptAssetGeneric $assets,
        private ForkRepositoriesPresenterBuilder $presenter_builder,
        private TemplateRenderer $template_renderer,
    ) {
    }

    /**
     *
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

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);
        if (! $project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext('tuleap-git', 'Git service is disabled.'));
        }

        $user = $request->getCurrentUser();

        $layout->addJavascriptAsset($this->assets);
        $this->header_renderer->renderDefaultHeader($request, $user, $project);
        $this->template_renderer->renderToPage(
            'fork-repositories',
            $this->presenter_builder->build(
                $user,
                $project,
                new CSRFSynchronizerToken(ForkRepositoriesPOSTUrlBuilder::buildForksAndDestinationSelectionURL($project)),
            ),
        );
        $layout->footer([]);
    }
}
