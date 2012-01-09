<?php
/**
 * Copyright (c) STMicroelectronics 2011. All rights reserved
 *
 * This code is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this code. If not, see <http://www.gnu.org/licenses/>.
 */

// Hostname/ip address of the server to be tested
$GLOBALS['host'] = 'http://192.168.1.141';

// Hostname/ip address of the client holding Selenium RC & the controlled browser
$GLOBALS['client'] = 'lxc-selenium-server';

// Browser to be used for tests *firefox, *iexplore, etc.
$GLOBALS['browser'] = '*firefox';

// Primary user that will be used to run tests
$GLOBALS['user'] = 'asma';

// Password of the primary user
$GLOBALS['password'] = 'asmaasma';

// Primary project that will be used for tests
$GLOBALS['project'] = 'selenium';

// ID of the primary project
$GLOBALS['project_id'] = '115';

// Docman root id of the primary project
$GLOBALS['docman_root_id'] = '43';

// Primary tracker that will be used for the tests
$GLOBALS['tracker'] = 'Bugs';

// Name of the primary tracker
$GLOBALS['trackerName'] = 'Bug';

// Shortname of the primary tracker
$GLOBALS['trackerShortName'] = 'bug';

?>
