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
use program_managementPlugin;
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\ProgramManagement\Adapter\Program\Admin\CanPrioritizeItems\ProjectUGroupCanPrioritizeItemsPresentersBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Admin\Configuration\ConfigurationErrorPresenterBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Admin\PlannableTrackersConfiguration\PotentialPlannableTrackersConfigurationPresentersBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Admin\PotentialTeam\PotentialTeamsPresenterBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Admin\ProgramAdminPresenter;
use Tuleap\ProgramManagement\Adapter\Program\Admin\Team\TeamsPresenterBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Admin\TimeboxTrackerConfiguration\PotentialTimeboxTrackerConfigurationPresenterCollection;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\RetrieveFullTracker;
use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\TrackerReferenceProxy;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationTrackerIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramIterationTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\RetrieveProjectReference;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTeam\PotentialTeamsCollection;
use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramCannotBeATeamException;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\IterationLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveIterationLabels;
use Tuleap\ProgramManagement\Domain\Program\AllProgramSearcher;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveVisibleIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveVisibleProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramAccessException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProgramHasNoProgramIncrementTrackerException;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\ProgramManagement\Domain\Workspace\SearchProjectsUserIsAdmin;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\SearchTrackersOfProgram;
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
        private PotentialPlannableTrackersConfigurationPresentersBuilder $plannable_tracker_presenters_builder,
        private ProjectUGroupCanPrioritizeItemsPresentersBuilder $ugroups_can_prioritize_builder,
        private VerifyProjectPermission $permission_verifier,
        private RetrieveProgramIncrementLabels $program_increment_labels_retriever,
        private SearchTrackersOfProgram $trackers_searcher,
        private RetrieveIterationLabels $iteration_labels_retriever,
        private AllProgramSearcher $all_program_searcher,
        private ConfigurationErrorPresenterBuilder $error_presenter_builder,
        private \ProjectManager $project_manager,
        private RetrieveFullTracker $tracker_retriever,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($variables);

        if (! $project->usesService(program_managementPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(
                dgettext('tuleap-program_management', 'Program management service is disabled.')
            );
        }

        $user                      = $request->getCurrentUser();
        $user_identifier           = UserProxy::buildFromPFUser($user);
        $project_identifier        = ProjectProxy::buildFromProject($project);
        $increment_error_presenter = null;
        $iteration_error_presenter = null;
        $program_increment_tracker = null;
        $iteration_tracker         = null;
        $plannable_error_presenter = null;
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

        try {
            $program = ProgramIdentifier::fromId(
                $this->build_program,
                (int) $project->getID(),
                $user_identifier,
                null
            );

            $program_increment_error_collector = new ConfigurationErrorsCollector(true);
            $iteration_error_collector         = new ConfigurationErrorsCollector(true);
            $plannable_error_collector         = new ConfigurationErrorsCollector(true);

            $program_increment_tracker = $this->program_increment_tracker_retriever->retrieveVisibleProgramIncrementTracker(
                $program,
                $user_identifier
            );

            $iteration_tracker = IterationTrackerIdentifier::fromProgram(
                $this->iteration_tracker_retriever,
                $program,
                $user_identifier
            );
            if (! $iteration_tracker) {
                throw new ProgramIterationTrackerNotFoundException($program);
            }

            $increment_error_presenter = $this->error_presenter_builder->buildProgramIncrementErrorPresenter(
                $program_increment_tracker,
                $program,
                $user_identifier,
                $program_increment_error_collector
            );
            $iteration_error_presenter = $this->error_presenter_builder->buildIterationErrorPresenter(
                TrackerReferenceProxy::fromIterationTracker($this->tracker_retriever, $iteration_tracker),
                $user_identifier,
                $iteration_error_collector
            );
            $plannable_error_presenter = $this->error_presenter_builder->buildPlannableErrorPresenter(
                $program,
                $plannable_error_collector
            );
        } catch (ProgramAccessException $e) {
            throw new ForbiddenException(
                dgettext(
                    'tuleap-program_management',
                    'You need to be project administrator to access to program administration.'
                )
            );
        } catch (
            ProjectIsNotAProgramException
            | ProgramHasNoProgramIncrementTrackerException
            | ProgramTrackerNotFoundException
            | ProgramIterationTrackerNotFoundException
        ) {
            // ignore for not configured program
        }

        \Tuleap\Project\ServiceInstrumentation::increment('program_management');

        $assets = $this->getAssets();
        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($assets, 'program-management-style'));

        $layout->addBreadcrumbs(
            $this->breadcrumbs_builder->build($project, $user)
        );

        $this->includeHeaderAndNavigationBar($layout, $project);
        $layout->includeFooterJavascriptFile($assets->getFileURL('program_management_admin.js'));

        $program_increment_labels = ProgramIncrementLabels::fromProgramIncrementTracker(
            $this->program_increment_labels_retriever,
            $program_increment_tracker
        );

        $iteration_labels = IterationLabels::fromIterationTracker(
            $this->iteration_labels_retriever,
            $iteration_tracker
        );

        $all_potential_trackers = PotentialTrackerCollection::fromProgram(
            $this->trackers_searcher,
            $admin_program
        );

        $this->template_renderer->renderToPage(
            'admin',
            new ProgramAdminPresenter(
                $admin_program,
                PotentialTeamsPresenterBuilder::buildPotentialTeamsPresenter(
                    PotentialTeamsCollection::buildPotentialTeams(
                        $this->teams_searcher,
                        $this->all_program_searcher,
                        $this->search_project_user_is_admin,
                        $admin_program,
                        $user_identifier
                    )->getPotentialTeams()
                ),
                TeamsPresenterBuilder::buildTeamsPresenter(
                    TeamProjectsCollection::fromProgramForAdministration(
                        $this->teams_searcher,
                        $this->project_reference_retriever,
                        $admin_program
                    )
                ),
                PotentialTimeboxTrackerConfigurationPresenterCollection::fromTimeboxTracker(
                    $all_potential_trackers,
                    $program_increment_tracker
                )->presenters,
                $this->plannable_tracker_presenters_builder->buildPotentialPlannableTrackerPresenters(
                    $admin_program,
                    $all_potential_trackers
                ),
                $this->ugroups_can_prioritize_builder->buildProjectUgroupCanPrioritizeItemsPresenters($admin_program),
                $program_increment_labels->label,
                $program_increment_labels->sub_label,
                PotentialTimeboxTrackerConfigurationPresenterCollection::fromTimeboxTracker(
                    $all_potential_trackers,
                    $iteration_tracker
                )->presenters,
                $iteration_labels->label,
                $iteration_labels->sub_label,
                $increment_error_presenter,
                $iteration_error_presenter,
                $plannable_error_presenter
            )
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

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../frontend-assets',
            '/assets/program_management'
        );
    }

    private function includeHeaderAndNavigationBar(BaseLayout $layout, Project $project): void
    {
        $layout->header(
            [
                'title'                          => dgettext('tuleap-program_management', 'Program'),
                'group'                          => $project->getID(),
                'toptab'                         => 'plugin_program_management',
                'body_class'                     => ['has-sidebar-with-pinned-header'],
                'main_classes'                   => [],
                'without-project-in-breadcrumbs' => false,
            ]
        );
    }
}
