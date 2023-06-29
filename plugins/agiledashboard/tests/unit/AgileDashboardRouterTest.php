<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\ExplicitBacklog\ArtifactsInExplicitBacklogDao;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\Kanban\NewDropdownCurrentContextSectionForKanbanProvider;
use Tuleap\AgileDashboard\Planning\BacklogTrackersUpdateChecker;
use Tuleap\AgileDashboard\Planning\PlanningUpdater;
use Tuleap\AgileDashboard\Scrum\ScrumPresenterBuilder;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Kanban\KanbanFactory;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;

final class AgileDashboardRouterTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\ForgeConfigSandbox;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_MilestoneControllerFactory
     */
    private $milestone_controller_factory;
    /**
     * @var \Mockery\Mock | AgileDashboardRouter
     */
    private $router;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_MilestoneFactory
     */
    private $planning_milestone_factory;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Planning_Controller
     */
    private $planning_controller;

    protected function setUp(): void
    {
        parent::setUp();
        ForgeConfig::set('codendi_dir', AGILEDASHBOARD_BASE_DIR . '/../../..');

        $this->milestone_controller_factory = Mockery::mock(Planning_MilestoneControllerFactory::class);
        $this->planning_controller          = Mockery::mock(Planning_Controller::class);
        $plugin                             = Mockery::mock(Plugin::class);
        $plugin->shouldReceive('getThemePath');
        $this->planning_milestone_factory = Mockery::mock(Planning_MilestoneFactory::class);

        $this->router = Mockery::mock(
            AgileDashboardRouter::class,
            [
                $plugin,
                $this->planning_milestone_factory,
                Mockery::mock(PlanningFactory::class),
                $this->milestone_controller_factory,
                Mockery::mock(ProjectManager::class),
                Mockery::mock(AgileDashboard_XMLFullStructureExporter::class),
                Mockery::mock(AgileDashboard_KanbanManager::class),
                Mockery::mock(AgileDashboard_ConfigurationManager::class),
                Mockery::mock(KanbanFactory::class),
                Mockery::mock(PlanningPermissionsManager::class),
                Mockery::mock(Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker::class),
                Mockery::mock(Tuleap\AgileDashboard\Planning\ScrumPlanningFilter::class),
                Mockery::mock(Tuleap\AgileDashboard\PermissionsPerGroup\AgileDashboardJSONPermissionsRetriever::class),
                Mockery::mock(Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder::class),
                Mockery::mock(Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder::class),
                Mockery::mock(SemanticTimeframeBuilder::class),
                Mockery::mock(CountElementsModeChecker::class),
                Mockery::mock(DBTransactionExecutor::class),
                Mockery::mock(ArtifactsInExplicitBacklogDao::class),
                Mockery::mock(ScrumPresenterBuilder::class),
                Mockery::mock(EventManager::class),
                Mockery::mock(PlanningUpdater::class),
                Mockery::mock(Planning_RequestValidator::class),
                Mockery::mock(AgileDashboard_XMLExporter::class),
                Mockery::mock(\Tuleap\AgileDashboard\Planning\RootPlanning\UpdateIsAllowedChecker::class),
                Mockery::mock(\Tuleap\AgileDashboard\Planning\Admin\PlanningEditionPresenterBuilder::class),
                new \Tuleap\AgileDashboard\Planning\Admin\UpdateRequestValidator(),
                Mockery::mock(NewDropdownCurrentContextSectionForKanbanProvider::class),
                $this->createMock(BacklogTrackersUpdateChecker::class),
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();

        $this->router->shouldReceive('buildPlanningController')->andReturn($this->planning_controller);
    }

    public function testItRoutesPlanningEditionRequests(): void
    {
        $request = Mockery::mock(Codendi_Request::class);
        $request->shouldReceive('get')->withArgs(['planning_id'])->andReturn(1);
        $request->shouldReceive('get')->withArgs(['action'])->andReturn('edit');
        $request->shouldReceive('getValidated')->andReturn(0);

        $this->router->shouldReceive('renderAction')->withArgs([$this->planning_controller, 'edit', $request, [], ['body_class' => ['agiledashboard-body']]])->once();
        $this->router->route($request);
    }

    public function testItRoutesPlanningUpdateRequests(): void
    {
        $request = Mockery::mock(Codendi_Request::class);
        $request->shouldReceive('get')->withArgs(['planning_id'])->andReturn(1);
        $request->shouldReceive('get')->withArgs(['action'])->andReturn('update');
        $request->shouldReceive('getValidated')->andReturn(0);

        $this->router->shouldReceive('executeAction')->withArgs([$this->planning_controller, 'update'])->atLeast()->once();
        $this->router->route($request);
    }

    public function testItRoutesToTheArtifactPlannificationByDefault(): void
    {
        $request = Mockery::mock(Codendi_Request::class);
        $request->shouldReceive('getCurrentUser')->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('getValidated')->andReturn(0);

        $last_milestone = Mockery::mock(Planning_Milestone::class);
        $last_milestone->shouldReceive('getArtifact');
        $this->planning_milestone_factory->shouldReceive('getLastMilestoneCreated')->andReturn($last_milestone);

        $this->milestone_controller_factory->shouldReceive('getMilestoneController')
            ->andReturn(Mockery::mock(Planning_MilestoneSelectorController::class))
            ->once();

        $this->router->shouldReceive('renderAction')->once();
        $this->router->shouldReceive('executeAction')->withArgs([Mockery::any(), 'createArtifact'])->never();
        $this->router->shouldReceive('executeAction')->withArgs([Mockery::any(), 'show'])->once();

        $this->router->routeShowPlanning($request);
    }

    public function testItRoutesToTheArtifactPlannificationWhenTheAidIsSetToAPositiveNumber(): void
    {
        $request = Mockery::mock(Codendi_Request::class);
        $request->shouldReceive('getCurrentUser')->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('getValidated')->andReturn(1234);

        $last_milestone = Mockery::mock(Planning_Milestone::class);
        $last_milestone->shouldReceive('getArtifact');
        $this->planning_milestone_factory->shouldReceive('getLastMilestoneCreated')->andReturn($last_milestone);

        $this->milestone_controller_factory->shouldReceive('getMilestoneController')
            ->andReturn(Mockery::mock(Planning_MilestoneSelectorController::class))
            ->once();

        $this->router->shouldReceive('renderAction')->once();
        $this->router->shouldReceive('executeAction')->withArgs([Mockery::any(), 'createArtifact'])->never();
        $this->router->shouldReceive('executeAction')->withArgs([Mockery::any(), 'show'])->never();

        $this->router->routeShowPlanning($request);
    }
}
