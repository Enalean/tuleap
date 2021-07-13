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
use Tuleap\ProgramManagement\Adapter\Program\Admin\Configuration\ConfigurationChecker;
use Tuleap\ProgramManagement\Domain\BuildProject;
use Tuleap\ProgramManagement\Domain\Program\Admin\PlannableTrackersConfiguration\BuildPotentialPlannableTrackersConfigurationPresenters;
use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTeam\BuildPotentialTeams;
use Tuleap\ProgramManagement\Domain\Program\Admin\PotentialTeam\PotentialTeamsPresenterBuilder;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramAdminPresenter;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramIncrementTrackerConfiguration\BuildPotentialProgramIncrementTrackerConfigurationPresenters;
use Tuleap\ProgramManagement\Domain\Program\Admin\Team\TeamsPresenterBuilder;
use Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker\RetrieveVisibleIterationTracker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveVisibleProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class DisplayAdminProgramManagementController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    private \ProjectManager $project_manager;
    private \TemplateRenderer $template_renderer;
    private ProgramManagementBreadCrumbsBuilder $breadcrumbs_builder;
    private BuildPotentialTeams $potential_teams_builder;
    private SearchTeamsOfProgram $teams_searcher;
    private BuildProject $project_data_adapter;
    private VerifyIsTeam $verify_is_team;
    private BuildProgram $build_program;
    private RetrieveVisibleProgramIncrementTracker $program_increment_tracker_retriever;
    private \EventManager $event_manager;
    private RetrieveVisibleIterationTracker $iteration_tracker_retriever;
    private BuildPotentialProgramIncrementTrackerConfigurationPresenters $program_increment_presenters_builder;
    private BuildPotentialPlannableTrackersConfigurationPresenters $plannable_tracker_presenters_builder;

    public function __construct(
        \ProjectManager $project_manager,
        \TemplateRenderer $template_renderer,
        ProgramManagementBreadCrumbsBuilder $breadcrumbs_builder,
        BuildPotentialTeams $potential_teams_builder,
        SearchTeamsOfProgram $teams_searcher,
        BuildProject $project_data_adapter,
        VerifyIsTeam $verify_is_team,
        BuildProgram $build_program,
        RetrieveVisibleProgramIncrementTracker $program_increment_tracker_retriever,
        \EventManager $event_manager,
        RetrieveVisibleIterationTracker $iteration_tracker_retriever,
        BuildPotentialProgramIncrementTrackerConfigurationPresenters $program_increment_presenters_builder,
        BuildPotentialPlannableTrackersConfigurationPresenters $plannable_tracker_presenters_builder
    ) {
        $this->project_manager                      = $project_manager;
        $this->template_renderer                    = $template_renderer;
        $this->breadcrumbs_builder                  = $breadcrumbs_builder;
        $this->potential_teams_builder              = $potential_teams_builder;
        $this->teams_searcher                       = $teams_searcher;
        $this->project_data_adapter                 = $project_data_adapter;
        $this->verify_is_team                       = $verify_is_team;
        $this->build_program                        = $build_program;
        $this->program_increment_tracker_retriever  = $program_increment_tracker_retriever;
        $this->event_manager                        = $event_manager;
        $this->iteration_tracker_retriever          = $iteration_tracker_retriever;
        $this->program_increment_presenters_builder = $program_increment_presenters_builder;
        $this->plannable_tracker_presenters_builder = $plannable_tracker_presenters_builder;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project    = $this->getProject($variables);
        $project_id = (int) $project->getID();

        if (! $project->usesService(program_managementPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(
                dgettext('tuleap-program_management', 'Program management service is disabled.')
            );
        }

        if ($this->verify_is_team->isATeam($project_id)) {
            throw new ForbiddenException(
                dgettext(
                    "tuleap-program_management",
                    "Project is defined as a Team project. It can not be used as a Program"
                )
            );
        }

        $user = $request->getCurrentUser();

        if (! $user->isAdmin($project_id)) {
            throw new ForbiddenException(
                dgettext(
                    'tuleap-program_management',
                    'You need to be project administrator to access to program administration.'
                )
            );
        }

        try {
            $error_presenters = ConfigurationChecker::buildErrorsPresenter(
                $this->build_program,
                $this->program_increment_tracker_retriever,
                $this->iteration_tracker_retriever,
                $this->event_manager,
                $project_id,
                $user
            );
        } catch (Domain\Program\Plan\ProgramAccessException $e) {
            throw new \LogicException(
                'You need to be project administrator to access to program administration.'
            );
        }

        \Tuleap\Project\ServiceInstrumentation::increment('program_management');

        $assets = $this->getAssets();
        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($assets, 'program-management-style'));

        $layout->addBreadcrumbs(
            $this->breadcrumbs_builder->build($project, $user)
        );

        $this->includeHeaderAndNavigationBar($layout, $project);
        $layout->includeFooterJavascriptFile($assets->getFileURL('program_management_admin.js'));

        $this->template_renderer->renderToPage(
            'admin',
            new ProgramAdminPresenter(
                $project_id,
                PotentialTeamsPresenterBuilder::buildPotentialTeamsPresenter(
                    $this->potential_teams_builder->buildPotentialTeams($project_id, $user)
                ),
                TeamsPresenterBuilder::buildTeamsPresenter(
                    TeamProjectsCollection::fromProjectId(
                        $this->teams_searcher,
                        $this->project_data_adapter,
                        $project_id
                    )
                ),
                $error_presenters,
                $this->program_increment_presenters_builder->buildPotentialProgramIncrementTrackerPresenters($project_id),
                $this->plannable_tracker_presenters_builder->buildPotentialPlannableTrackerPresenters($project_id)
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
            __DIR__ . '/../../../src/www/assets/program_management',
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
