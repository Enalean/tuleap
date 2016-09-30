<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST;

require_once 'common/autoload.php';

use AgileDashboard_HierarchyChecker;
use AgileDashboard_KanbanDao;
use AgileDashboard_KanbanFactory;
use AgileDashboard_KanbanManager;
use Exception;
use PlanningFactory;
use REST_TestDataBuilder;
use TrackerFactory;

class DataBuilder extends REST_TestDataBuilder
{
    const PROJECT_KANBAN_CUMULATIVE_FLOW_SHORTNAME = 'kanban-cumulative-flow';
    const KANBAN_CUMULATIVE_FLOW_NAME              = 'kanban_cumulative_flow_test';
    const KANBAN_CUMULATIVE_FLOW_ID                = 2;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var AgileDashboard_KanbanManager
     */
    private $kanban_manager;

    public function __construct()
    {
        parent::__construct();

        $this->tracker_factory = TrackerFactory::instance();
        $kanban_dao            = new AgileDashboard_KanbanDao();
        $kanban_factory        = new AgileDashboard_KanbanFactory($this->tracker_factory, $kanban_dao);
        $planning_factory      = PlanningFactory::build();
        $hierarchy_checker     = new AgileDashboard_HierarchyChecker(
            $planning_factory,
            $kanban_factory,
            $this->tracker_factory
        );
        $this->kanban_manager  = new AgileDashboard_KanbanManager(
            $kanban_dao,
            $this->tracker_factory,
            $hierarchy_checker
        );
    }

    public function setUp()
    {
        $this->createKanbanCumulativeFlow();
    }

    private function createKanbanCumulativeFlow()
    {
        $project_kanban_cumulative_flow = $this->project_manager->getProjectByUnixName(
            self::PROJECT_KANBAN_CUMULATIVE_FLOW_SHORTNAME
        );
        $trackers                       = $this->tracker_factory->getTrackersByGroupId(
            $project_kanban_cumulative_flow->getID()
        );
        $tracker_kanban_cumulative_flow = array_shift($trackers);

        $kanban_id = $this->kanban_manager->createKanban(
            self::KANBAN_CUMULATIVE_FLOW_NAME,
            $tracker_kanban_cumulative_flow->getId()
        );

        if ($kanban_id !== self::KANBAN_CUMULATIVE_FLOW_ID) {
            throw new Exception(
                'The kanban used for the test of the cumulative flow is not the one expected. Please update the builder accordingly.'
            );
        }
    }
}
