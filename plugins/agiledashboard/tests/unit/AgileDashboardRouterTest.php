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
use Tuleap\AgileDashboard\Planning\MilestoneControllerFactory;
use Tuleap\AgileDashboard\Planning\BacklogTrackersUpdateChecker;
use Tuleap\AgileDashboard\Planning\PlanningUpdater;
use Tuleap\AgileDashboard\Scrum\ScrumPresenterBuilder;
use Tuleap\DB\DBTransactionExecutor;

final class AgileDashboardRouterTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Tuleap\ForgeConfigSandbox;

    private AgileDashboardRouter|\PHPUnit\Framework\MockObject\MockObject $router;
    private MilestoneControllerFactory|\PHPUnit\Framework\MockObject\MockObject $milestone_controller_factory;
    private Planning_Controller|\PHPUnit\Framework\MockObject\MockObject $planning_controller;
    private Planning_MilestoneFactory|\PHPUnit\Framework\MockObject\MockObject $planning_milestone_factory;

    protected function setUp(): void
    {
        parent::setUp();
        ForgeConfig::set('codendi_dir', AGILEDASHBOARD_BASE_DIR . '/../../..');

        $this->milestone_controller_factory = $this->createMock(MilestoneControllerFactory::class);
        $this->planning_controller          = $this->createMock(Planning_Controller::class);
        $plugin                             = $this->createMock(Plugin::class);
        $plugin->method('getThemePath');
        $this->planning_milestone_factory = $this->createMock(Planning_MilestoneFactory::class);

        $this->router =
            $this->getMockBuilder(AgileDashboardRouter::class)
                ->setConstructorArgs([
                    $this->planning_milestone_factory,
                    $this->createMock(PlanningFactory::class),
                    $this->milestone_controller_factory,
                    $this->createMock(ProjectManager::class),
                    $this->createMock(AgileDashboard_XMLFullStructureExporter::class),
                    $this->createMock(AgileDashboard_ConfigurationManager::class),
                    $this->createMock(PlanningPermissionsManager::class),
                    $this->createMock(Tuleap\AgileDashboard\Planning\ScrumPlanningFilter::class),
                    $this->createMock(Tuleap\AgileDashboard\PermissionsPerGroup\AgileDashboardJSONPermissionsRetriever::class),
                    $this->createMock(Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder::class),
                    $this->createMock(Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder::class),
                    $this->createMock(CountElementsModeChecker::class),
                    $this->createMock(DBTransactionExecutor::class),
                    $this->createMock(ArtifactsInExplicitBacklogDao::class),
                    $this->createMock(ScrumPresenterBuilder::class),
                    $this->createMock(EventManager::class),
                    $this->createMock(PlanningUpdater::class),
                    $this->createMock(Planning_RequestValidator::class),
                    $this->createMock(AgileDashboard_XMLExporter::class),
                    $this->createMock(\Tuleap\AgileDashboard\Planning\RootPlanning\UpdateIsAllowedChecker::class),
                    $this->createMock(\Tuleap\AgileDashboard\Planning\Admin\PlanningEditionPresenterBuilder::class),
                    new \Tuleap\AgileDashboard\Planning\Admin\UpdateRequestValidator(),
                    $this->createMock(BacklogTrackersUpdateChecker::class),
                ])
                ->onlyMethods(['buildPlanningController', 'renderAction', 'executeAction'])
                ->getMock();

        $this->router->method('buildPlanningController')->willReturn($this->planning_controller);
    }

    public function testItRoutesPlanningEditionRequests(): void
    {
        $request = \Tuleap\Test\Builders\HTTPRequestBuilder::get()
            ->withParams([
                'planning_id' => 1,
                'action' => 'edit',
            ])
            ->build();

        $this->router->expects(self::once())->method('renderAction')->with($this->planning_controller, 'edit', $request);
        $this->router->route($request);
    }

    public function testItRoutesPlanningUpdateRequests(): void
    {
        $request = \Tuleap\Test\Builders\HTTPRequestBuilder::get()
            ->withParams([
                'planning_id' => 1,
                'action' => 'update',
            ])
            ->build();

        $this->router->expects(self::atLeast(1))->method('executeAction')->with($this->planning_controller, 'update');
        $this->router->route($request);
    }

    public function testItRoutesToTheArtifactPlannificationByDefault(): void
    {
        $request = \Tuleap\Test\Builders\HTTPRequestBuilder::get()
            ->withUser($this->createMock(PFUser::class))
            ->build();

        $last_milestone = $this->createMock(Planning_Milestone::class);
        $last_milestone->method('getArtifact');
        $this->planning_milestone_factory->method('getLastMilestoneCreated')->willReturn($last_milestone);

        $this->milestone_controller_factory
            ->expects(self::once())
            ->method('getMilestoneController')
            ->willReturn($this->createMock(Planning_MilestoneController::class));

        $show_called = false;
        $this->router->expects(self::once())->method('renderAction');
        $this->router->method('executeAction')->willReturnCallback(
            function (MVC2_Controller $controller, $action) use (&$show_called) {
                if ($action !== 'show') {
                    $this->fail('Unexpected call to render action with ' . $action);
                } else {
                    $show_called = true;
                }
            }
        );

        $this->router->routeShowPlanning($request);
        self::assertTrue($show_called);
    }

    public function testItRoutesToTheArtifactPlannificationWhenTheAidIsSetToAPositiveNumber(): void
    {
        $request = \Tuleap\Test\Builders\HTTPRequestBuilder::get()
            ->withUser($this->createMock(PFUser::class))
            ->withParam('aid', 1234)
            ->build();

        $last_milestone = $this->createMock(Planning_Milestone::class);
        $last_milestone->method('getArtifact');
        $this->planning_milestone_factory->method('getLastMilestoneCreated')->willReturn($last_milestone);

        $this->milestone_controller_factory->expects(self::once())
            ->method('getMilestoneController')
            ->willReturn($this->createMock(Planning_MilestoneController::class));

        $this->router->expects(self::once())->method('renderAction');
        $this->router->expects(self::never())->method('executeAction');

        $this->router->routeShowPlanning($request);
    }
}
