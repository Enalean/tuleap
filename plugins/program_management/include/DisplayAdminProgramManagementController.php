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
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\ProgramManagement\Adapter\Program\Admin\ProgramAdminPresenter;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\PotentialPlannableTrackersConfigurationBuilder;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ProgramAdmin;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ProjectUGroupCanPrioritizeItemsBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\VerifyIsSynchronizationPending;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ConfigurationErrorsGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\SearchOpenProgramIncrements;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TeamSynchronization\VerifyTeamSynchronizationHasError;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsAProgramOrUsedInPlanChecker;
use Tuleap\ProgramManagement\Domain\Program\Plan\RetrievePlannableTrackers;
use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyIsProjectUsedInPlan;
use Tuleap\ProgramManagement\Domain\RetrieveProjectReference;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramCannotBeATeamException;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationLabels;
use Tuleap\ProgramManagement\Domain\Program\AllProgramSearcher;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveVisibleIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveVisibleProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\MirroredTimebox\SearchMirrorTimeboxesFromProgram;
use Tuleap\ProgramManagement\Domain\Team\SearchVisibleTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\ProgramManagement\Domain\Workspace\SearchProjectsUserIsAdmin;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\SearchTrackersOfProgram;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\VerifyTrackerSemantics;
use Tuleap\ProgramManagement\Domain\Workspace\VerifyProjectPermission;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class DisplayAdminProgramManagementController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    public function __construct(
        private SearchProjectsUserIsAdmin $search_project_user_is_admin,
        private \TemplateRenderer $template_renderer,
        private ProgramManagementBreadCrumbsBuilder $breadcrumbs_builder,
        private SearchTeamsOfProgram $teams_searcher,
        private RetrieveProjectReference $project_reference_retriever,
        private VerifyIsTeam $verify_is_team,
        private BuildProgram $build_program,
        private RetrieveVisibleProgramIncrementTracker $program_increment_tracker_retriever,
        private RetrieveVisibleIterationTracker $iteration_tracker_retriever,
        private PotentialPlannableTrackersConfigurationBuilder $plannable_tracker_presenters_builder,
        private ProjectUGroupCanPrioritizeItemsBuilder $ugroups_can_prioritize_builder,
        private VerifyProjectPermission $permission_verifier,
        private RetrieveProgramIncrementLabels $program_increment_labels_retriever,
        private SearchTrackersOfProgram $trackers_searcher,
        private RetrieveIterationLabels $iteration_labels_retriever,
        private AllProgramSearcher $all_program_searcher,
        private ConfigurationErrorsGatherer $errors_gatherer,
        private \ProjectManager $project_manager,
        private SearchOpenProgramIncrements $search_open_program_increments,
        private SearchMirrorTimeboxesFromProgram $timebox_searcher,
        private VerifyIsSynchronizationPending $verify_is_synchronization_pending,
        private SearchVisibleTeamsOfProgram $team_searcher,
        private VerifyTeamSynchronizationHasError $verify_team_synchronization_has_error,
        private RetrievePlannableTrackers $plannable_trackers_retriever,
        private VerifyTrackerSemantics $verify_tracker_semantics,
        private ProjectIsAProgramOrUsedInPlanChecker $build_program_for_administration,
        private VerifyIsProjectUsedInPlan $verify_is_project_used_in_plan,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($variables);

        if (! $project->usesService(ProgramService::SERVICE_SHORTNAME)) {
            throw new NotFoundException(
                dgettext('tuleap-program_management', 'Program management service is disabled.')
            );
        }

        $user               = $request->getCurrentUser();
        $user_identifier    = UserProxy::buildFromPFUser($user);
        $project_identifier = ProjectProxy::buildFromProject($project);

        try {
            $admin_program = ProgramForAdministrationIdentifier::fromProject(
                $this->verify_is_team,
                $this->permission_verifier,
                $user_identifier,
                $project_identifier
            );
        } catch (ProgramCannotBeATeamException $e) {
            throw new ForbiddenException($e->getI18NExceptionMessage());
        } catch (ProgramAccessException $e) {
            throw new ForbiddenException(
                dgettext(
                    'tuleap-program_management',
                    'You need to be project administrator to access to program administration.'
                )
            );
        }

        \Tuleap\Project\ServiceInstrumentation::increment('program_management');

        $layout->addJavascriptAsset(
            new JavascriptViteAsset(
                new IncludeViteAssets(__DIR__ . '/../scripts/admin/frontend-assets', '/assets/program_management/admin'),
                'src/index.ts'
            )
        );

        $layout->addBreadcrumbs(
            $this->breadcrumbs_builder->build($project, $user)
        );

        $this->includeHeaderAndNavigationBar($layout, $project);


        try {
            $aggregated_teams = TeamProjectsCollection::fromProgramForAdministration(
                $this->teams_searcher,
                $this->project_reference_retriever,
                $admin_program
            );

            $program_admin = ProgramAdmin::build(
                $this->search_project_user_is_admin,
                $this->teams_searcher,
                $this->verify_is_team,
                $this->build_program,
                $this->program_increment_tracker_retriever,
                $this->iteration_tracker_retriever,
                $this->plannable_tracker_presenters_builder,
                $this->ugroups_can_prioritize_builder,
                $this->program_increment_labels_retriever,
                $this->trackers_searcher,
                $this->iteration_labels_retriever,
                $this->all_program_searcher,
                $this->errors_gatherer,
                $this->search_open_program_increments,
                $this->timebox_searcher,
                $this->verify_is_synchronization_pending,
                $this->team_searcher,
                $this->verify_team_synchronization_has_error,
                $this->plannable_trackers_retriever,
                $this->verify_tracker_semantics,
                $admin_program,
                $user_identifier,
                $project_identifier,
                $aggregated_teams,
                $this->build_program_for_administration,
                $this->verify_is_project_used_in_plan
            );
        } catch (ProgramAccessException $e) {
            throw new ForbiddenException(
                dgettext(
                    'tuleap-program_management',
                    'You need to be project administrator to access to program administration.'
                )
            );
        }
        $this->template_renderer->renderToPage(
            'admin',
            ProgramAdminPresenter::build($program_admin)
        );

        $layout->footer([]);
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

    private function includeHeaderAndNavigationBar(BaseLayout $layout, Project $project): void
    {
        $layout->header(
            HeaderConfigurationBuilder::get(dgettext('tuleap-program_management', 'Program'))
                ->inProject($project, ProgramService::SERVICE_SHORTNAME)
                ->withBodyClass(['has-sidebar-with-pinned-header'])
                ->build()
        );
    }
}
