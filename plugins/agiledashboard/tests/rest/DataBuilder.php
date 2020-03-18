<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

use AgileDashboard_KanbanDao;
use AgileDashboard_KanbanManager;
use BackendLogger;
use BrokerLogger;
use EventManager;
use Exception;
use ForgeConfig;
use Log_ConsoleLogger;
use REST_TestDataBuilder;
use SystemEvent;
use SystemEventManager;
use SystemEventProcessor_Factory;
use TruncateLevelLogger;
use Tuleap\AgileDashboard\FormElement\SystemEvent\SystemEvent_BURNUP_GENERATE;
use Tuleap\Project\SystemEventRunner;

class DataBuilder extends REST_TestDataBuilder
{
    public const PROJECT_KANBAN_CUMULATIVE_FLOW_SHORTNAME = 'kanban-cumulative-flow';
    public const KANBAN_CUMULATIVE_FLOW_NAME              = 'kanban_cumulative_flow_test';
    public const RELEASE_TRACKER_SHORTNAME                = 'rel';
    public const PROJECT_BURNUP_SHORTNAME                 = 'burnup';
    public const KANBAN_CUMULATIVE_FLOW_ID                = 2;

    public const EXPLICIT_BACKLOG_PROJECT_SHORTNAME = 'explicitadbacklog';

    /**
     * @var SystemEventManager
     */
    private $system_event_manager;
    /**
     * @var SystemEventRunner
     */
    private $system_event_runner;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;

    /**
     * @var AgileDashboard_KanbanManager
     */
    private $kanban_manager;

    public function __construct()
    {
        parent::__construct();
        $this->instanciateFactories();

        $kanban_dao            = new AgileDashboard_KanbanDao();
        $this->kanban_manager  = new AgileDashboard_KanbanManager(
            $kanban_dao,
            $this->tracker_factory
        );

        $this->tracker_artifact_factory = \Tracker_ArtifactFactory::instance();
        $this->system_event_manager     = SystemEventManager::instance();

        $console    = new TruncateLevelLogger(new Log_ConsoleLogger(), ForgeConfig::get('sys_logger_level'));
        $logger     = BackendLogger::getDefaultLogger();
        $broker_log = new BrokerLogger(array($logger, $console));

        $factory                   = new SystemEventProcessor_Factory(
            $broker_log,
            SystemEventManager::instance(),
            EventManager::instance()
        );
        $this->system_event_runner = new SystemEventRunner($factory);
    }

    public function setUp()
    {
        $this->createKanbanCumulativeFlow();
        $this->generateBurnupCache();
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

    private function generateBurnupCache()
    {
        $tracker = $this->getTrackerInProject(self::RELEASE_TRACKER_SHORTNAME, self::PROJECT_BURNUP_SHORTNAME);

        $artifacts = $this->tracker_artifact_factory->getArtifactsByTrackerId($tracker->getId());
        $this->system_event_manager->createEvent(
            SystemEvent_BURNUP_GENERATE::class,
            reset($artifacts)->getId(),
            SystemEvent::PRIORITY_MEDIUM,
            SystemEvent::OWNER_APP
        );

        $this->system_event_runner->runSystemEvents();
    }
}
