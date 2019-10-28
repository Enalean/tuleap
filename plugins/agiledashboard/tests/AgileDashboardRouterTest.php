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
use Tuleap\AgileDashboard\Planning\PlanningUpdater;
use Tuleap\AgileDashboard\Scrum\ScrumPresenterBuilder;
use Tuleap\DB\DBTransactionExecutor;

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';

class AgileDashboardRouter_RouteShowPlanningTest extends TuleapTestCase
{
    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('codendi_dir', AGILEDASHBOARD_BASE_DIR .'/../../..');

        $milestone_controller_factory = mock('Planning_MilestoneControllerFactory');
        $this->planning_controller = mock('Planning_Controller');
        $this->router = TestHelper::getPartialMock(
            'AgileDashboardRouter',
            array('renderAction',
                                                   'executeAction',
                                                   'buildController',
                                                   'buildPlanningController',
            'getArtifactFactory',
            )
        );

        $this->router->__construct(
            mock('Plugin'),
            mock('Planning_MilestoneFactory'),
            mock('PlanningFactory'),
            $milestone_controller_factory,
            mock('ProjectManager'),
            mock('AgileDashboard_XMLFullStructureExporter'),
            mock('AgileDashboard_KanbanManager'),
            mock('AgileDashboard_ConfigurationManager'),
            mock('AgileDashboard_KanbanFactory'),
            mock('PlanningPermissionsManager'),
            mock('Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker'),
            mock('Tuleap\AgileDashboard\Planning\ScrumPlanningFilter'),
            mock('Tuleap\AgileDashboard\PermissionsPerGroup\AgileDashboardJSONPermissionsRetriever'),
            mock(\Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder::class),
            mock(\Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder::class),
            Mockery::mock(\Tuleap\Tracker\Semantic\Timeframe\TimeframeChecker::class),
            Mockery::mock(CountElementsModeChecker::class),
            Mockery::mock(DBTransactionExecutor::class),
            Mockery::mock(ArtifactsInExplicitBacklogDao::class),
            Mockery::mock(ScrumPresenterBuilder::class),
            Mockery::mock(EventManager::class),
            Mockery::mock(PlanningUpdater::class),
            Mockery::mock(Planning_RequestValidator::class)
        );

        stub($this->router)->buildPlanningController()->returns($this->planning_controller);
        stub($milestone_controller_factory)->getMilestoneController()->returns(mock('Planning_MilestoneController'));
        stub($this->router)->buildController()->returns(mock('Tuleap\AgileDashboard\AdminController'));
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itRoutesPlanningEditionRequests()
    {
        $request = aRequest()->with('planning_id', 1)
                             ->with('action', 'edit')->build();
        $this->router->expectOnce('renderAction', array($this->planning_controller, 'edit', $request));
        $this->router->route($request);
    }

    public function itRoutesPlanningUpdateRequests()
    {
        $request = aRequest()->with('planning_id', 1)
                             ->with('action', 'update')->build();
        $this->router->expectOnce('executeAction', array($this->planning_controller, 'update'));
        $this->router->route($request);
    }

    public function itRoutesToTheArtifactPlannificationByDefault()
    {
        $request = aRequest()->withUri('someurl')->build();
        $this->router->expectOnce('executeAction', array(new IsAExpectation('Planning_MilestoneSelectorController'), 'show'));
        $this->router->expectOnce('renderAction', array(new IsAExpectation('Planning_MilestoneController'), 'show', $request, '*', '*'));
        $this->router->routeShowPlanning($request);
    }

    public function itRoutesToTheArtifactPlannificationWhenTheAidIsSetToAPositiveNumber()
    {
        $request = aRequest()->with('aid', '732')->withUri('someurl')->build();
        $this->router->expectOnce('renderAction', array(new IsAExpectation('Planning_MilestoneController'), 'show', $request, '*', '*'));
        $this->router->routeShowPlanning($request);
    }

    public function itRoutesToArtifactCreationWhenAidIsSetToMinusOne()
    {
        $request = new Codendi_Request(array('aid' => '-1'));
        $this->router->expectOnce('executeAction', array(new IsAExpectation('Planning_ArtifactCreationController'), 'createArtifact'));
        $this->router->routeShowPlanning($request);
    }
}
