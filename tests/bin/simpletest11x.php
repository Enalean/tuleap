<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 *
 */

require_once __DIR__ . '/../../src/vendor/autoload.php';
require_once __DIR__ . '/../../src/common/constants.php';
require_once __DIR__ . '/SimpleTest11x/TuleapTestCase.php';
require_once __DIR__ . '/SimpleTest11x/TuleapColorTextReporter.php';
require_once __DIR__ . '/SimpleTest11x/TuleapJunitXMLReporter.php';
require_once __DIR__ . '/SimpleTest11x/RunTestSuite.php';

// Tests are like gods, they can run an infinite time, eat all the memory and kill kittens
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');
ini_set('display_errors', 'on');
date_default_timezone_set('Europe/Paris');

require_once __DIR__ . '/../../src/etc/local.inc.dist';

$cli_args = $argv;
array_shift($cli_args);

$options = getopt('', ['log-junit:', 'quiet']);
foreach ($options as $opt) {
    array_shift($cli_args);
}

$command = $cli_args[0];
array_shift($cli_args);

switch ($command) {
    case 'run':
        $exec = new RunTestSuite($cli_args, $options);
        $exec->main();
        break;

    default:
        fwrite(STDERR, "Unknown option " . $command . "\n");
        exit(255);
}
