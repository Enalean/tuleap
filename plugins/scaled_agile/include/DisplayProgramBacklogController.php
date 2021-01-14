<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile;

use HTTPRequest;
use Project;
use scaled_agilePlugin;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\ScaledAgile\Adapter\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ScaledAgile\Program\Plan\BuildProgram;

final class DisplayProgramBacklogController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var ProjectFlagsBuilder
     */
    private $project_flags_builder;
    /**
     * @var BuildProgram
     */
    private $build_program;
    /**
     * @var \TemplateRenderer
     */
    private $template_renderer;

    public function __construct(
        \ProjectManager $project_manager,
        ProjectFlagsBuilder $project_flags_builder,
        BuildProgram $build_program,
        \TemplateRenderer $template_renderer
    ) {
        $this->project_manager       = $project_manager;
        $this->project_flags_builder = $project_flags_builder;
        $this->build_program         = $build_program;
        $this->template_renderer     = $template_renderer;
    }

    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProjectByUnixName($variables['project_name']);
        if (! $project) {
            throw new NotFoundException();
        }

        return $project;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($variables);
        if (! $project->usesService(scaled_agilePlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext("tuleap-scaled_agile", "Scaled agile service is disabled."));
        }

        try {
            $this->build_program->buildExistingProgramProject((int) $project->getID(), $request->getCurrentUser());
        } catch (ProjectIsNotAProgramException $exception) {
            throw new ForbiddenException(dgettext('tuleap-scaled_agile', 'The scaled agile service can only be used in a project defined as a program.'));
        }

        \Tuleap\Project\ServiceInstrumentation::increment('scaled_agile');

        $assets = new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/scaled_agile',
            '/assets/scaled_agile'
        );
        $layout->addCssAsset(new CssAsset($assets, 'scaled_agile'));

        $this->includeHeaderAndNavigationBar($layout, $project);
        $layout->includeFooterJavascriptFile($this->getAssets()->getFileURL('scaled_agile.js'));

        $this->template_renderer->renderToPage(
            'program-backlog',
            new ProgramBacklogPresenter($project, $this->project_flags_builder->buildProjectFlags($project))
        );

        $layout->footer([]);
    }

    private function includeHeaderAndNavigationBar(BaseLayout $layout, Project $project): void
    {
        $layout->header(
            [
                'title'                          => dgettext('tuleap-scaled_agile', "Scaled Agile Program"),
                'group'                          => $project->getID(),
                'toptab'                         => 'plugin_scaled_agile',
                'main_classes'                   => [],
                'without-project-in-breadcrumbs' => true,
            ]
        );
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/scaled_agile',
            '/assets/scaled_agile'
        );
    }
}
