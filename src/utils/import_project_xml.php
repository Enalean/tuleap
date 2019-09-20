#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

require_once __DIR__ . '/../www/include/pre.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Tuleap\CLI\Command\ImportProjectXMLCommand;

$application = new Application();
$import_project_xml_command = new ImportProjectXMLCommand();
$application->add($import_project_xml_command);
$application->setDefaultCommand($import_project_xml_command->getName(), $is_single_command = true);

$console_output = new ConsoleOutput();

$argv = $_SERVER['argv'];

foreach ($argv as $index => $value) {
    if ($value === '-n') {
        $console_output->writeln("<fg=yellow;options=bold>Warning : Option '-n' isn't longer supported. Replace by '-s' or '--name'</>");
        unset($argv[$index]);
        $argv[] = '-s';
    }
}
$console_output->writeln('<fg=yellow;options=bold>Please use tuleap import-project-xml -u < username > -m < mapping > -i < archive > -p < project > ... </>');

$application->run(new ArgvInput($argv), $console_output);
