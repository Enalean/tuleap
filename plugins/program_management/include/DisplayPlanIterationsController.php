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
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Adapter\Program\DisplayPlanIterationsPresenter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveVisibleIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\BuildProgramIncrementInfo;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PlannedIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\BuildProgramBaseInfo;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\BuildProgramFlags;
use Tuleap\ProgramManagement\Domain\Workspace\BuildProgramPrivacy;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveProgramUserPrivileges;
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
        private BuildProgram $program_adapter,
        private BuildProgramFlags $build_program_flags,
        private BuildProgramPrivacy $build_program_privacy,
        private BuildProgramBaseInfo $build_program_base_info,
        private BuildProgramIncrementInfo $build_program_increment_info,
        private VerifyIsProgramIncrement $verify_is_program_increment,
        private VerifyIsVisibleArtifact $verify_is_visible_artifact,
        private RetrieveProgramUserPrivileges $retrieve_program_user_privileges,
        private RetrieveVisibleIterationTracker $retrieve_visible_iteration_tracker,
        private RetrieveIterationLabels $retrieve_iteration_labels
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

        \Tuleap\Project\ServiceInstrumentation::increment('program_management');

        $user            = $request->getCurrentUser();
        $user_identifier = UserProxy::buildFromPFUser($user);

        try {
            $program_identifier = ProgramIdentifier::fromId($this->program_adapter, (int) $project->getID(), $user_identifier, null);
            $planned_iterations = PlannedIterations::build(
                $this->build_program_flags,
                $this->build_program_privacy,
                $this->build_program_base_info,
                $this->build_program_increment_info,
                $this->retrieve_program_user_privileges,
                $program_identifier,
                $user_identifier,
                ProgramIncrementIdentifier::fromId(
                    $this->verify_is_program_increment,
                    $this->verify_is_visible_artifact,
                    (int) $variables['increment_id'],
                    $user_identifier
                ),
                IterationLabels::fromIterationTracker(
                    $this->retrieve_iteration_labels,
                    $this->retrieve_visible_iteration_tracker->retrieveVisibleIterationTracker($program_identifier, $user_identifier)
                )
            );
        } catch (
            Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException |
            Domain\Program\Plan\ProjectIsNotAProgramException |
            Domain\Program\ProgramTrackerNotFoundException $e
        ) {
            throw new NotFoundException($e->getI18NExceptionMessage());
        } catch (Domain\Program\Plan\ProgramAccessException $e) {
            throw new ForbiddenException($e->getI18NExceptionMessage());
        }

        $assets = $this->getAssets();

        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($assets, 'planned-iterations-style'));
        $layout->includeFooterJavascriptFile($assets->getFileURL('planned-iterations.js'));
        $this->includeHeaderAndNavigationBar($layout, $project);

        $this->template_renderer->renderToPage(
            'plan-iterations',
            DisplayPlanIterationsPresenter::fromPlannedIterations($planned_iterations)
        );

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

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/program_management',
            '/assets/program_management'
        );
    }
}
