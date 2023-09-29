<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\REST;

use Tuleap\Kanban\KanbanDao;
use Tuleap\Kanban\KanbanManager;
use Exception;
use REST_TestDataBuilder;

class DataBuilder extends REST_TestDataBuilder
{
    public const PROJECT_KANBAN_CUMULATIVE_FLOW_SHORTNAME = 'kanban-cumulative-flow';
    public const KANBAN_CUMULATIVE_FLOW_NAME              = 'kanban_cumulative_flow_test';
    public const KANBAN_CUMULATIVE_FLOW_ID                = 2;

    private KanbanManager $kanban_manager;

    public function __construct()
    {
        parent::__construct();
        $this->instanciateFactories();

        $kanban_dao           = new KanbanDao();
        $this->kanban_manager = new KanbanManager(
            $kanban_dao,
            $this->tracker_factory
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
            false,
            $tracker_kanban_cumulative_flow->getId()
        );

        if ($kanban_id !== self::KANBAN_CUMULATIVE_FLOW_ID) {
            throw new Exception(
                'The kanban used for the test of the cumulative flow is not the one expected. Please update the builder accordingly.'
            );
        }
    }
}
