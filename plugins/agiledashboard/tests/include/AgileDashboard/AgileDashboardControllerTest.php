<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\AgileDashboard\AdminController;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\Scrum\ScrumPresenterBuilder;

require_once dirname(__FILE__).'/../../bootstrap.php';

class AgileDashboardControllerTest extends TuleapTestCase
{
    /** @var UserManager */
    private $user_manager;

    /** @var Codendi_Request */
    private $request;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var AgileDashboard_KanbanManager */
    private $kanban_manager;

    /** @var AgileDashboard_ConfigurationManager */
    private $config_manager;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var AgileDashboard_KanbanFactory */
    private $kanban_factory;

    /** @var EventManager */
    private $event_manager;

    /** @var AgileDashboardCrumbBuilder */
    private $service_crumb_builder;

    /** @var AdministrationCrumbBuilder */
    private $admin_crumb_builder;

    /**
     * @var \Mockery\MockInterface|CountElementsModeChecker
     */
    private $count_element_mode_checker;

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('codendi_dir', AGILEDASHBOARD_BASE_DIR .'/../../..');

        $this->user_manager                 = mock('UserManager');
        $this->request                      = mock('Codendi_Request');
        $this->planning_factory             = mock('PlanningFactory');
        $this->kanban_manager               = mock('AgileDashboard_KanbanManager');
        $this->config_manager               = mock('AgileDashboard_ConfigurationManager');
        $this->tracker_factory              = mock('TrackerFactory');
        $this->kanban_factory               = mock('AgileDashboard_KanbanFactory');
        $this->event_manager                = \Mockery::spy(\EventManager::class);
        $this->service_crumb_builder        = mock(AgileDashboardCrumbBuilder::class);
        $this->admin_crumb_builder          = mock(AdministrationCrumbBuilder::class);
        $this->count_element_mode_checker   = Mockery::mock(CountElementsModeChecker::class);

        UserManager::setInstance($this->user_manager);

        $this->count_element_mode_checker->shouldReceive('burnupMustUseCountElementsMode')->andReturnFalse();
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        UserManager::clearInstance();
        parent::tearDown();
    }

    public function itDoesNothingIfIsUserNotAdmin()
    {
        $user = stub('PFUser')->isAdmin(123)->returns(false);

        stub($this->request)->exist('activate-ad-service')->returns(true);
        stub($this->request)->get('activate-ad-service')->returns('');
        stub($this->request)->get('group_id')->returns(123);
        stub($this->request)->getCurrentUser()->returns($user);
        stub($this->user_manager)->getCurrentUser()->returns($user);

        $controller = new AdminController(
            $this->request,
            $this->planning_factory,
            $this->kanban_manager,
            $this->kanban_factory,
            $this->config_manager,
            $this->tracker_factory,
            $this->event_manager,
            $this->service_crumb_builder,
            $this->admin_crumb_builder,
            $this->count_element_mode_checker,
            Mockery::mock(ScrumPresenterBuilder::class)
        );

        expect($this->config_manager)->updateConfiguration()->never();

        $controller->updateConfiguration();
    }

    public function itDoesNotCreateAKanbanIfTrackerIsUsedInScrumBacklog()
    {
        $tracker    = mock('Tracker');
        $admin_user = stub('PFUser')->isAdmin(789)->returns(true);

        stub($this->request)->get('kanban-name')->returns('My Kanban');
        stub($this->request)->get('group_id')->returns(789);
        stub($this->request)->get('tracker-kanban')->returns(123);
        stub($this->request)->getCurrentUser()->returns($admin_user);
        stub($this->tracker_factory)->getTrackerById(123)->returns($tracker);
        stub($this->kanban_manager)->doesKanbanExistForTracker($tracker)->returns(true);
        stub($this->planning_factory)->getPlanningsByBacklogTracker($tracker)->returns(array());
        stub($this->user_manager)->getCurrentUser()->returns($admin_user);

        $controller = new AdminController(
            $this->request,
            $this->planning_factory,
            $this->kanban_manager,
            $this->kanban_factory,
            $this->config_manager,
            $this->tracker_factory,
            $this->event_manager,
            $this->service_crumb_builder,
            $this->admin_crumb_builder,
            $this->count_element_mode_checker,
            Mockery::mock(ScrumPresenterBuilder::class)
        );

        expect($this->kanban_manager)->createKanban()->never();

        $controller->createKanban();
    }

    public function itDoesNotCreateAKanbanIfTrackerIsUsedInScrumPlanning()
    {
        $tracker    = mock('Tracker');
        $admin_user = stub('PFUser')->isAdmin(789)->returns(true);

        stub($this->request)->get('kanban-name')->returns('My Kanban');
        stub($this->request)->get('group_id')->returns(789);
        stub($this->request)->get('tracker-kanban')->returns(123);
        stub($this->request)->getCurrentUser()->returns($admin_user);
        stub($this->tracker_factory)->getTrackerById(123)->returns($tracker);
        stub($this->kanban_manager)->doesKanbanExistForTracker($tracker)->returns(true);
        stub($this->planning_factory)->getPlanningByPlanningTracker($tracker)->returns(array());
        stub($this->user_manager)->getCurrentUser()->returns($admin_user);

        $controller = new AdminController(
            $this->request,
            $this->planning_factory,
            $this->kanban_manager,
            $this->kanban_factory,
            $this->config_manager,
            $this->tracker_factory,
            $this->event_manager,
            $this->service_crumb_builder,
            $this->admin_crumb_builder,
            $this->count_element_mode_checker,
            Mockery::mock(ScrumPresenterBuilder::class)
        );

        expect($this->kanban_manager)->createKanban()->never();

        $controller->createKanban();
    }
}
