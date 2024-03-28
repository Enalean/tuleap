<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc;

use DocmanPlugin;
use HTTPRequest;
use Project;
use Tuleap\Docman\ServiceDocman;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

final readonly class ArtidocController implements DispatchableWithRequest, DispatchableWithProject
{
    public function __construct(
        private \ProjectManager $project_manager,
        private \ArtidocPlugin $plugin,
    ) {
    }

    public function getProject(array $variables): Project
    {
        try {
            $project = $this->project_manager->getValidProjectByShortNameOrId($variables['project_name']);
        } catch (\Project_NotFoundException) {
            throw new NotFoundException();
        }

        if (! $this->plugin->isAllowed((int) $project->getID())) {
            throw new NotFoundException();
        }

        return $project;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);

        $service = $this->getService($project);

        $layout->addJavascriptAsset(
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../scripts/artidoc/frontend-assets',
                    '/assets/artidoc/artidoc'
                ),
                'src/index.ts'
            )
        );

        $service->displayHeader(dgettext('tuleap-artidoc', 'Artifacts as Documents'), [], []);
        \TemplateRendererFactory::build()->getRenderer(__DIR__)->renderToPage('artidoc', [
            'project_id' => $project->getID(),
        ]);
        $service->displayFooter();
    }

    private function getService(Project $project): ServiceDocman
    {
        $service = $project->getService(DocmanPlugin::SERVICE_SHORTNAME);
        if ($service instanceof ServiceDocman) {
            return $service;
        }

        throw new NotFoundException();
    }
}
