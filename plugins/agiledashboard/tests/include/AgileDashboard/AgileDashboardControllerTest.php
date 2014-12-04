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
        Config::store();
        Config::set('codendi_dir', AGILEDASHBOARD_BASE_DIR .'/../../..');
    }

    public function tearDown() {
        Config::restore();
        parent::tearDown();
    }

    public function itDoesNothingIfNoServiceSelected() {
        $request          = mock('Codendi_Request');
        $planning_factory = mock('PlanningFactory');
        $kanban_manager   = mock('AgileDashboard_KanbanManager');
        $config_manager   = mock('AgileDashboard_ConfigurationManager');
        $tracker_factory  = mock('TrackerFactory');
        $kanban_factory   = mock('AgileDashboard_KanbanFactory');

        stub($request)->exist('activate-ad-service')->returns(true);
        stub($request)->get('activate-ad-service')->returns('');
        stub($request)->get('group_id')->returns(123);

        $controller = new AgileDashboard_Controller(
            $request,
            $planning_factory,
            $kanban_manager,
            $kanban_factory,
            $config_manager,
            $tracker_factory
        );

        expect($config_manager)->updateConfiguration()->never();

        $controller->updateConfiguration();
    }

}