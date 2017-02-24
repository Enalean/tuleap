<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once dirname(__FILE__).'/../../bootstrap.php';

class AgileDashboardControllerTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set('codendi_dir', AGILEDASHBOARD_BASE_DIR .'/../../..');

        $this->user_manager     = mock('UserManager');
        $this->request          = mock('Codendi_Request');
        $this->planning_factory = mock('PlanningFactory');
        $this->kanban_manager   = mock('AgileDashboard_KanbanManager');
        $this->config_manager   = mock('AgileDashboard_ConfigurationManager');
        $this->tracker_factory  = mock('TrackerFactory');
        $this->kanban_factory   = mock('AgileDashboard_KanbanFactory');

        UserManager::setInstance($this->user_manager);
    }

    public function tearDown() {
        ForgeConfig::restore();
        UserManager::clearInstance();
        parent::tearDown();
    }

    public function itDoesNothingIfIsUserNotAdmin() {
        $user = stub('PFUser')->isAdmin(123)->returns(false);

        stub($this->request)->exist('activate-ad-service')->returns(true);
        stub($this->request)->get('activate-ad-service')->returns('');
        stub($this->request)->get('group_id')->returns(123);
        stub($this->request)->getCurrentUser()->returns($user);
        stub($this->user_manager)->getCurrentUser()->returns($user);

        $controller = new AgileDashboard_Controller(
            $this->request,
            $this->planning_factory,
            $this->kanban_manager,
            $this->kanban_factory,
            $this->config_manager,
            $this->tracker_factory,
            mock('AgileDashboard_PermissionsManager'),
            mock('AgileDashboard_HierarchyChecker'),
            mock('Tuleap\AgileDashboard\ScrumForMonoMilestoneChecker')
        );

        expect($this->config_manager)->updateConfiguration()->never();

        $controller->updateConfiguration();
    }

    public function itDoesNotCreateAKanbanIfTrackerIsUsedInScrumBacklog() {
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

        $controller = new AgileDashboard_Controller(
            $this->request,
            $this->planning_factory,
            $this->kanban_manager,
            $this->kanban_factory,
            $this->config_manager,
            $this->tracker_factory,
            mock('AgileDashboard_PermissionsManager'),
            mock('AgileDashboard_HierarchyChecker'),
            mock('Tuleap\AgileDashboard\ScrumForMonoMilestoneChecker')
        );

        expect($this->kanban_manager)->createKanban()->never();

        $controller->createKanban();
    }

    public function itDoesNotCreateAKanbanIfTrackerIsUsedInScrumPlanning() {
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

        $controller = new AgileDashboard_Controller(
            $this->request,
            $this->planning_factory,
            $this->kanban_manager,
            $this->kanban_factory,
            $this->config_manager,
            $this->tracker_factory,
            mock('AgileDashboard_PermissionsManager'),
            mock('AgileDashboard_HierarchyChecker'),
            mock('Tuleap\AgileDashboard\ScrumForMonoMilestoneChecker')
        );

        expect($this->kanban_manager)->createKanban()->never();

        $controller->createKanban();
    }

}
