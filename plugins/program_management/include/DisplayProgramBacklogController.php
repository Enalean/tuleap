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
use program_managementPlugin;
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramBacklogConfigurationPresenter;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramBacklogPresenter;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\ProgramIncrementTrackerConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveProgramIncrementLabels;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker\RetrieveVisibleProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsNotAProgramException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Program\ProgramTrackerNotFoundException;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class DisplayProgramBacklogController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    private \ProjectManager $project_manager;
    private ProjectFlagsBuilder $project_flags_builder;
    private \TemplateRenderer $template_renderer;
    private BuildProgram $build_program;
    private RetrieveVisibleProgramIncrementTracker $program_increment_tracker_retriever;
    private RetrieveProgramIncrementLabels $labels_retriever;
    private VerifyIsTeam $verify_is_team;

    public function __construct(
        \ProjectManager $project_manager,
        ProjectFlagsBuilder $project_flags_builder,
        BuildProgram $build_program,
        \TemplateRenderer $template_renderer,
        RetrieveVisibleProgramIncrementTracker $program_increment_tracker_retriever,
        RetrieveProgramIncrementLabels $labels_retriever,
        VerifyIsTeam $verify_is_team
    ) {
        $this->project_manager                     = $project_manager;
        $this->project_flags_builder               = $project_flags_builder;
        $this->build_program                       = $build_program;
        $this->template_renderer                   = $template_renderer;
        $this->program_increment_tracker_retriever = $program_increment_tracker_retriever;
        $this->labels_retriever                    = $labels_retriever;
        $this->verify_is_team                      = $verify_is_team;
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
        if (! $project->usesService(program_managementPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext("tuleap-program_management", "Program management service is disabled."));
        }

        if ($this->verify_is_team->isATeam((int) $project->getID())) {
            throw new ForbiddenException(dgettext("tuleap-program_management", "Project is defined as a Team project. It can not be used as a Program."));
        }

        $user = $request->getCurrentUser();
        try {
            $configuration = $this->buildConfigurationForExistingProgram($project, $user);
        } catch (ProjectIsNotAProgramException | Domain\Program\Plan\ProgramHasNoProgramIncrementTrackerException $exception) {
            $configuration = $this->buildConfigurationForPotentialProgram();
        } catch (ProgramTrackerNotFoundException $e) {
            throw new NotFoundException();
        }

        \Tuleap\Project\ServiceInstrumentation::increment('program_management');

        $assets = $this->getAssets();
        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($assets, 'program-management-style'));

        $this->includeHeaderAndNavigationBar($layout, $project);
        $layout->includeFooterJavascriptFile($assets->getFileURL('program_management.js'));

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
                'title' => dgettext('tuleap-program_management', "Program"),
                'group' => $project->getID(),
                'toptab' => 'plugin_program_management',
                'body_class' => ['has-sidebar-with-pinned-header'],
                'main_classes' => [],
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

    /**
     * @throws Domain\Program\Plan\ProgramAccessException
     * @throws Domain\Program\Plan\ProgramHasNoProgramIncrementTrackerException
     * @throws ProgramTrackerNotFoundException
     * @throws ProjectIsNotAProgramException
     */
    private function buildConfigurationForExistingProgram(Project $project, PFUser $user): ProgramBacklogConfigurationPresenter
    {
        $user_identifier = UserProxy::buildFromPFUser($user);
        $program         = ProgramIdentifier::fromId($this->build_program, (int) $project->getID(), $user_identifier);

        $plan_configuration = ProgramIncrementTrackerConfiguration::fromProgram(
            $this->program_increment_tracker_retriever,
            $this->labels_retriever,
            $program,
            $user
        );
        return new ProgramBacklogConfigurationPresenter(
            $plan_configuration->canCreateProgramIncrement(),
            $plan_configuration->getProgramIncrementTrackerId(),
            $plan_configuration->getProgramIncrementLabel(),
            $plan_configuration->getProgramIncrementSubLabel(),
            true
        );
    }

    private function buildConfigurationForPotentialProgram(): ProgramBacklogConfigurationPresenter
    {
        return new ProgramBacklogConfigurationPresenter(false, 0, "", "", false);
    }
}
