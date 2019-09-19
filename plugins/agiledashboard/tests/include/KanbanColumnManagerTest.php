<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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

use Tuleap\AgileDashboard\REST\v1\Kanban\KanbanColumnPATCHRepresentation;

require_once __DIR__ . '/../bootstrap.php';

class AgileDashboard_KanbanColumnManagerTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->project_id = 101;
        $this->kanban_id  = 2;
        $this->tracker_id = 4;
        $this->column_id  = 456;
        $this->wip_limit  = 12;

        $this->user       = aUser()->build();
        $this->tracker    = aTracker()->withProjectId($this->project_id)->withId($this->tracker_id)->build();
        $this->column     = new AgileDashboard_KanbanColumn($this->column_id, $this->kanban_id, "Todo", true, null, 2, true);

        $this->column_dao             = mock("AgileDashboard_KanbanColumnDao");
        $this->tracker_factory        = stub("TrackerFactory")->getTrackerById($this->tracker_id)->returns($this->tracker);
        $this->kanban_actions_checker = mock("AgileDashboard_KanbanActionsChecker");

        $this->kanban                = new AgileDashboard_Kanban($this->kanban_id, $this->tracker_id, "My Kanban");
        $this->kanban_column_manager = new AgileDashboard_KanbanColumnManager(
            $this->column_dao,
            mock('Tracker_FormElement_Field_List_Bind_Static_ValueDao'),
            $this->kanban_actions_checker
        );

        $this->kanban_patch_representation = new KanbanColumnPATCHRepresentation();
        $this->kanban_patch_representation->wip_limit = $this->wip_limit;
    }

    public function itUpdatesTheWIPLimit()
    {
        expect($this->column_dao)->setColumnWipLimit($this->kanban_id, $this->column_id, $this->wip_limit)->once();

        $this->kanban_column_manager->updateWipLimit($this->user, $this->kanban, $this->column, $this->wip_limit);
    }

    public function itThrowsAnExceptionIfUserNotAdmin()
    {
        stub($this->kanban_actions_checker)->checkUserCanAdministrate($this->user, $this->kanban)->throws(new AgileDashboard_UserNotAdminException($this->user));

        expect($this->column_dao)->setColumnWipLimit($this->kanban_id, $this->column_id, $this->wip_limit)->never();
        $this->expectException("AgileDashboard_UserNotAdminException");

        $this->kanban_column_manager->updateWipLimit($this->user, $this->kanban, $this->column, $this->wip_limit);
    }
}
