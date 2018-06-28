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
 * MERCHANTABILITY or FITNEsemantic_status FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'pre.php';

use Tuleap\CLI\Application;
use Tuleap\CLI\Command\ConfigGetCommand;
use Tuleap\CLI\Command\ConfigSetCommand;
use Tuleap\CLI\Command\UserPasswordCommand;
use Tuleap\Password\PasswordSanityChecker;

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
        new ConfigDao()
    )
);

$event_manager         = EventManager::instance();
$CLI_command_collector = new \Tuleap\CLI\CLICommandsCollector($application);
$event_manager->processEvent($CLI_command_collector);

$application->run();
