<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

require_once __DIR__.'/../../src/common/autoload.php';
require_once __DIR__.'/../../src/common/autoload_libs.php';
require_once __DIR__.'/SimpleTestPHP7/TuleapTestCase.php';
require_once __DIR__.'/SimpleTestPHP7/FindCompatibleTests.php';
require_once __DIR__.'/SimpleTestPHP7/RunTestSuite.php';
require_once __DIR__.'/SimpleTestPHP7/CompareXMLResults.php';

// Tests are like gods, they can run an infinite time, eat all the memory and kill kittens
ini_set('max_execution_time', '0');
ini_set('memory_limit', '-1');
ini_set('display_errors', 'on');
date_default_timezone_set('Europe/Paris');

$basedir      = dirname(dirname(__DIR__));
$src_path     = $basedir.'/src';
$include_path = $basedir.'/src/www/include';
ini_set('include_path', ini_get('include_path').':'.$src_path.':'.$include_path);

require_once __DIR__.'/../../src/etc/local.inc.dist';

switch ($argv[1]) {
    case 'collect':
        $exec = new FindCompatibleTests();
        $exec->main();
        break;

    case 'blind-exec':
        array_shift($argv);
        $exec = new RunTestSuite();
        $exec->mainWithoutOutput($argv);
        break;

    case 'run':
        $exec = new RunTestSuite();
        $exec->mainWithCompatibilityList();
        break;

    case 'run-juint':
        array_shift($argv);
        $exec = new RunTestSuite();
        $exec->mainWithCompatibilityListJunit($argv);
        break;

    case 'run-file':
        array_shift($argv);
        $exec = new RunTestSuite();
        $exec->mainWithOneFile($argv);
        break;

    case 'compare-results':
        array_shift($argv);
        $exec = new CompareXMLResults();
        $exec->main($argv);
        break;
}
