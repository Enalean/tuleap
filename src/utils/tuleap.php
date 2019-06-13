<?php
/**
 * Copyright (c) Enalean, 2015-present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNEsemantic_status FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once __DIR__.'/../www/include/pre.php';

use Tuleap\CLI\Application;
use Tuleap\CLI\CLICommandsCollector;
use Tuleap\CLI\Command\ConfigGetCommand;
use Tuleap\CLI\Command\ConfigSetCommand;
use Tuleap\CLI\Command\QueueSystemCheckCommand;
use Tuleap\CLI\Command\ImportProjectXMLCommand;
use Tuleap\CLI\Command\LaunchEveryMinuteJobCommand;
use Tuleap\CLI\Command\ProcessSystemEventsCommand;
use Tuleap\CLI\Command\UserPasswordCommand;
use Tuleap\DB\DBFactory;
use Tuleap\Password\PasswordSanityChecker;

$event_manager  = EventManager::instance();
$user_manager   = UserManager::instance();
$backend_logger = BackendLogger::getDefaultLogger();

$application = new Application();
$application->add(
    new UserPasswordCommand(
        UserManager::instance(),
        PasswordSanityChecker::build()
    )
);
$application->add(
    new ConfigGetCommand()
);
$application->add(
    new ConfigSetCommand(
        new ConfigDao(),
        $event_manager
    )
);
$application->add(
    new ImportProjectXMLCommand()
);
$main_db_connection = DBFactory::getMainTuleapDBConnection();
$application->add(
    new ProcessSystemEventsCommand(
        new SystemEventProcessor_Factory($backend_logger, SystemEventManager::instance(), $event_manager),
        new SystemEventProcessManager(),
        $main_db_connection
    )
);
$application->add(
    new QueueSystemCheckCommand($event_manager, $main_db_connection)
);
$application->add(
    new LaunchEveryMinuteJobCommand(
        $event_manager,
        $backend_logger,
        $main_db_connection
    )
);

$CLI_command_collector = new CLICommandsCollector($application);
$event_manager->processEvent($CLI_command_collector);

$application->run();
