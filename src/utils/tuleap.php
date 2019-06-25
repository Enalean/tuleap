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

$event_manager         = EventManager::instance();
$backend_logger        = BackendLogger::getDefaultLogger();
$CLI_command_collector = new CLICommandsCollector();

$CLI_command_collector->addCommand(
    ConfigGetCommand::NAME,
    static function(): ConfigGetCommand {
        return new ConfigGetCommand();
    }
);
$CLI_command_collector->addCommand(
    ConfigSetCommand::NAME,
    static function() use ($event_manager) : ConfigSetCommand  {
        return new ConfigSetCommand(
            new ConfigDao(),
            $event_manager
        );
    }
);
$CLI_command_collector->addCommand(
    UserPasswordCommand::NAME,
    static function() : UserPasswordCommand  {
        return new UserPasswordCommand(
            UserManager::instance(),
            PasswordSanityChecker::build()
        );
    }
);
$CLI_command_collector->addCommand(
    ImportProjectXMLCommand::NAME,
    static function() : ImportProjectXMLCommand  {
        return new ImportProjectXMLCommand();
    }
);
$CLI_command_collector->addCommand(
    ProcessSystemEventsCommand::NAME,
    static function() use ($backend_logger, $event_manager) : ProcessSystemEventsCommand  {
        return new ProcessSystemEventsCommand(
            new SystemEventProcessor_Factory($backend_logger, SystemEventManager::instance(), $event_manager),
            new SystemEventProcessManager(),
            DBFactory::getMainTuleapDBConnection()
        );
    }
);
$CLI_command_collector->addCommand(
    QueueSystemCheckCommand::NAME,
    static function() use ($event_manager) : QueueSystemCheckCommand  {
        return new QueueSystemCheckCommand($event_manager, DBFactory::getMainTuleapDBConnection());
    }
);
$CLI_command_collector->addCommand(
    LaunchEveryMinuteJobCommand::NAME,
    static function() use ($event_manager, $backend_logger) : LaunchEveryMinuteJobCommand  {
        return new LaunchEveryMinuteJobCommand(
            $event_manager,
            $backend_logger,
            DBFactory::getMainTuleapDBConnection()
        );
    }
);

$event_manager->dispatch($CLI_command_collector);

$application = new Application();
$CLI_command_collector->loadCommands($application);
$application->run();
