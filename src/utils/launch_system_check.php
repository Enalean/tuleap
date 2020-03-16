<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 *
 */

require_once __DIR__ . '/../www/include/pre.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Tuleap\CLI\Command\QueueSystemCheckCommand;
use Tuleap\CLI\DelayExecution\ConditionalTuleapCronEnvExecutionDelayer;
use Tuleap\CLI\DelayExecution\ExecutionDelayedLauncher;
use Tuleap\CLI\DelayExecution\ExecutionDelayerRandomizedSleep;
use Tuleap\DB\DBFactory;

$application                 = new Application();
$launch_system_check_command = new QueueSystemCheckCommand(
    EventManager::instance(),
    DBFactory::getMainTuleapDBConnection(),
    new ExecutionDelayedLauncher(
        new ConditionalTuleapCronEnvExecutionDelayer(
            new ExecutionDelayerRandomizedSleep(1799)
        )
    )
);
$application->add($launch_system_check_command);
$application->setDefaultCommand($launch_system_check_command->getName(), $is_single_command = true);

$console_output = new ConsoleOutput();
$console_output->writeln('<fg=yellow;options=bold>This command is deprecated. Please use `/usr/bin/tuleap ' .
    OutputFormatter::escape($launch_system_check_command->getName()) . '` instead.</>');

$application->run(new ArgvInput($_SERVER['argv']), $console_output);
