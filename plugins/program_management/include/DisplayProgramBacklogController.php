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

namespace Tuleap\ProgramManagement;

use HTTPRequest;
use PFUser;
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\ProgramManagement\Adapter\Program\Admin\ProgramBacklogPresenter;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveVisibleIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramBacklogConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveVisibleProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyPrioritizeFeaturesPermission;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyUserCanSubmit;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class DisplayProgramBacklogController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    public function __construct(
        private \ProjectManager $project_manager,
        private ProjectFlagsBuilder $project_flags_builder,
        private BuildProgram $build_program,
        private \TemplateRenderer $template_renderer,
        private RetrieveVisibleProgramIncrementTracker $program_increment_tracker_retriever,
        private RetrieveProgramIncrementLabels $labels_retriever,
        private VerifyIsTeam $verify_is_team,
        private VerifyPrioritizeFeaturesPermission $prioritize_features_permission,
        private VerifyUserCanSubmit $user_can_submit_in_tracker_verifier,
        private RetrieveIterationLabels $label_retriever,
        private RetrieveVisibleIterationTracker $retrieve_visible_iteration_tracker,
    ) {
    }

    /**
     * @throws NotFoundException
     */
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
        if (! $project->usesService(ProgramService::SERVICE_SHORTNAME)) {
            throw new NotFoundException(
                dgettext("tuleap-program_management", "Program management service is disabled.")
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

        $user            = $request->getCurrentUser();
        $user_identifier = UserProxy::buildFromPFUser($user);
        try {
            $configuration = $this->buildConfigurationForExistingProgram($project, $user_identifier);
        } catch (ProjectIsNotAProgramException | Domain\Program\Plan\ProgramHasNoProgramIncrementTrackerException $exception) {
            $configuration = ProgramBacklogConfiguration::buildForPotentialProgram();
        } catch (ProgramTrackerNotFoundException $e) {
            throw new NotFoundException();
        }

        \Tuleap\Project\ServiceInstrumentation::increment('program_management');

        $layout->addJavascriptAsset(new JavascriptViteAsset($this->getAssets(), 'src/index.ts'));
        $this->includeHeaderAndNavigationBar($layout, $project);

        $this->template_renderer->renderToPage(
            'program-backlog',
            new ProgramBacklogPresenter(
                $project,
                $this->project_flags_builder->buildProjectFlags($project),
                (bool) $user->getPreference(PFUser::ACCESSIBILITY_MODE),
                $configuration,
                $user->isAdmin((int) $project->getId())
            )
        );

        $layout->footer([]);
    }

    private function includeHeaderAndNavigationBar(BaseLayout $layout, Project $project): void
    {
        $layout->header(
            [
                'title'                          => dgettext('tuleap-program_management', "Program"),
                'project'                        => $project,
                'toptab'                         => 'plugin_program_management',
                'body_class'                     => ['has-sidebar-with-pinned-header'],
                'main_classes'                   => [],
                'without-project-in-breadcrumbs' => true,
            ]
        );
    }

    private function getAssets(): IncludeViteAssets
    {
        return new IncludeViteAssets(
            __DIR__ . '/../scripts/program_management/frontend-assets',
            '/assets/program_management/program_management'
        );
    }

    /**
     * @throws Domain\Program\Plan\ProgramAccessException
     * @throws Domain\Program\Plan\ProgramHasNoProgramIncrementTrackerException
     * @throws ProgramTrackerNotFoundException
     * @throws ProjectIsNotAProgramException
     */
    private function buildConfigurationForExistingProgram(
        Project $project,
        UserIdentifier $user,
    ): ProgramBacklogConfiguration {
        $program = ProgramIdentifier::fromId($this->build_program, (int) $project->getID(), $user, null);

        $program_increment_configuration = ProgramIncrementTrackerConfiguration::fromProgram(
            $this->program_increment_tracker_retriever,
            $this->labels_retriever,
            $this->prioritize_features_permission,
            $this->user_can_submit_in_tracker_verifier,
            $program,
            $user
        );

        $iteration_configuration = IterationTrackerConfiguration::fromProgram(
            $this->retrieve_visible_iteration_tracker,
            $this->label_retriever,
            $program,
            $user
        );
        return ProgramBacklogConfiguration::fromProgramIncrementAndIterationConfiguration(
            $program_increment_configuration,
            $iteration_configuration
        );
    }
}
