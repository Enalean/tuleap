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
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox\TitleValueRetriever;
use Tuleap\ProgramManagement\Adapter\Program\IterationView\DisplayPlanIterationsPresenter;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveVisibleIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\BuildProgramIncrementInfo;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\PlannedIterations;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyIsProgramIncrement;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramIterationTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\VerifyIsVisibleArtifact;
use Tuleap\ProgramManagement\Domain\Workspace\BuildProgramBaseInfo;
use Tuleap\ProgramManagement\Domain\Workspace\BuildProgramFlags;
use Tuleap\ProgramManagement\Domain\Workspace\BuildProgramPrivacy;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUserPreference;
use Tuleap\ProgramManagement\Domain\Workspace\UserPreference;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyUserIsProgramAdmin;
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
        private VerifyUserIsProgramAdmin $verify_user_is_program_admin,
        private RetrieveVisibleIterationTracker $retrieve_visible_iteration_tracker,
        private RetrieveIterationLabels $retrieve_iteration_labels,
        private RetrieveUserPreference $retrieve_user_preference,
        private TitleValueRetriever $title_value_retriever,
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

        if (! $project->usesService(ProgramService::SERVICE_SHORTNAME)) {
            throw new NotFoundException(
                dgettext('tuleap-program_management', 'Program management service is disabled.')
            );
        }

        \Tuleap\Project\ServiceInstrumentation::increment('program_management');

        $user            = $request->getCurrentUser();
        $user_identifier = UserProxy::buildFromPFUser($user);

        try {
            $program_identifier   = ProgramIdentifier::fromId(
                $this->program_adapter,
                (int) $project->getID(),
                $user_identifier,
                null
            );
            $increment_identifier = ProgramIncrementIdentifier::fromId(
                $this->verify_is_program_increment,
                $this->verify_is_visible_artifact,
                (int) $variables['increment_id'],
                $user_identifier
            );

            $iteration_configuration = IterationTrackerConfiguration::fromProgram(
                $this->retrieve_visible_iteration_tracker,
                $this->retrieve_iteration_labels,
                $program_identifier,
                $user_identifier
            );
            if ($iteration_configuration === null) {
                throw new ProgramIterationTrackerNotFoundException($program_identifier);
            }

            $planned_iterations = PlannedIterations::build(
                $this->build_program_flags,
                $this->build_program_privacy,
                $this->build_program_base_info,
                $this->build_program_increment_info,
                $this->verify_user_is_program_admin,
                $program_identifier,
                $user_identifier,
                $increment_identifier,
                $iteration_configuration,
                UserPreference::fromUserIdentifierAndPreferenceName(
                    $this->retrieve_user_preference,
                    $user_identifier,
                    \PFUser::ACCESSIBILITY_MODE
                )
            );
        } catch (
            Domain\Program\Backlog\ProgramIncrement\ProgramIncrementNotFoundException |
            Domain\Program\Plan\ProjectIsNotAProgramException |
            Domain\Program\ProgramIterationTrackerNotFoundException |
            Domain\Program\ProgramTrackerNotFoundException $e
        ) {
            throw new NotFoundException($e->getI18NExceptionMessage());
        } catch (Domain\Program\Plan\ProgramAccessException $e) {
            throw new ForbiddenException($e->getI18NExceptionMessage());
        }

        $layout->addJavascriptAsset(new JavascriptViteAsset($this->getAssets(), 'src/index.ts'));
        $this->includeHeaderAndNavigationBar($layout, $project, $increment_identifier);

        $this->template_renderer->renderToPage(
            'plan-iterations',
            DisplayPlanIterationsPresenter::fromPlannedIterations($planned_iterations)
        );

        $layout->footer([]);
    }

    private function includeHeaderAndNavigationBar(
        BaseLayout $layout,
        Project $project,
        ProgramIncrementIdentifier $increment_identifier,
    ): void {
        $program_increment_title = (string) $this->title_value_retriever->getTitle($increment_identifier);
        $project_title           = $project->getPublicName();
        $title                   = sprintf(
            dgettext(
                'tuleap-program_management',
                "%s - Iterations - %s"
            ),
            $program_increment_title,
            $project_title
        );
        $layout->header(
            HeaderConfigurationBuilder::get($title)
                ->inProjectNotInBreadcrumbs($project, ProgramService::SERVICE_SHORTNAME)
                ->withBodyClass(['has-sidebar-with-pinned-header'])
                ->build()
        );
    }

    private function getAssets(): IncludeViteAssets
    {
        return new IncludeViteAssets(
            __DIR__ . '/../scripts/planned-iterations/frontend-assets/',
            '/assets/program_management/planned-iterations'
        );
    }
}
