<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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


namespace Tuleap\ProgramManagement;

use HTTPRequest;
use program_managementPlugin;
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class DisplayPlanIterationsController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    public function __construct(
        private \ProjectManager $project_manager,
        private \TemplateRenderer $template_renderer,
        private VerifyIsTeam $verify_is_team,
    ) {
    }

    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProjectByUnixName($variables['project_name']);
        if (! $project) {
            throw new NotFoundException();
        }

        return $project;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);

        if (! $project->usesService(program_managementPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(
                dgettext('tuleap-program_management', 'Program management service is disabled.')
            );
        }

        if ($this->verify_is_team->isATeam((int) $project->getID())) {
            throw new ForbiddenException(
                dgettext(
                    "tuleap-program_management",
                    "Project is defined as a Team project. It can not be used as a Program."
                )
            );
        }

        \Tuleap\Project\ServiceInstrumentation::increment('program_management');

        $this->includeHeaderAndNavigationBar($layout, $project);

        $this->template_renderer->renderToPage('plan-iterations', []);

        $layout->footer([]);
    }

    private function includeHeaderAndNavigationBar(BaseLayout $layout, Project $project): void
    {
        $layout->header(
            [
                'title'                          => dgettext('tuleap-program_management', "Plan iterations"),
                'group'                          => $project->getID(),
                'toptab'                         => 'plugin_program_management',
                'body_class'                     => ['has-sidebar-with-pinned-header'],
                'main_classes'                   => [],
                'without-project-in-breadcrumbs' => true,
            ]
        );
    }
}
