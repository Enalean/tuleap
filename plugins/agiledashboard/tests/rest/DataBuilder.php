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

namespace Tuleap\AgileDashboard\REST;

use BackendLogger;
use BrokerLogger;
use EventManager;
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
    public const RELEASE_TRACKER_SHORTNAME          = 'rel';
    public const PROJECT_BURNUP_SHORTNAME           = 'burnup';
    public const EXPLICIT_BACKLOG_PROJECT_SHORTNAME = 'explicitadbacklog';

    private SystemEventManager $system_event_manager;
    private SystemEventRunner $system_event_runner;
    private \Tracker_ArtifactFactory $tracker_artifact_factory;

    public function __construct()
    {
        parent::__construct();
        $this->instanciateFactories();

        $this->tracker_artifact_factory = \Tracker_ArtifactFactory::instance();
        $this->system_event_manager     = SystemEventManager::instance();

        $console    = new TruncateLevelLogger(new Log_ConsoleLogger(), ForgeConfig::get('sys_logger_level'));
        $logger     = BackendLogger::getDefaultLogger();
        $broker_log = new BrokerLogger([$logger, $console]);

        $factory                   = new SystemEventProcessor_Factory(
            $broker_log,
            SystemEventManager::instance(),
            EventManager::instance()
        );
        $this->system_event_runner = new SystemEventRunner($factory);
    }

    public function setUp()
    {
        $this->generateBurnupCache();
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
