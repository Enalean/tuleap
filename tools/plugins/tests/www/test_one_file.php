<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 *
 * Allow to execute only one test, in CLI
 *
 * Example:
 * $> php-launcher.sh test_one_file.php ../tests/common/frs/FRSFileTest.php
 */

error_reporting(E_ALL);
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

require(getenv('CODENDI_LOCAL_INC')?getenv('CODENDI_LOCAL_INC'):'/etc/codendi/conf/local.inc');
require($GLOBALS['db_config_file']);
require_once('../include/simpletest/unit_tester.php');
require_once('../include/simpletest/mock_objects.php');
require_once('../include/simpletest/web_tester.php');
require_once('../include/simpletest/expectation.php');
require_once('./tests_utils.php');


// Start
$unitTestFile = $_SERVER['argv'][1];

// Load & Go
require_once($unitTestFile);
$className = basename($unitTestFile, '.php');
$test      = new $className();
$test->run(new TextReporter());

?>