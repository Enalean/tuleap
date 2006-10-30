<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 * 
 * Originally written by Manuel VACELET, 2006.
 * 
 * This file is a part of CodeX.
 * 
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * 
 * $Id$
 */

//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('../../../codex_tools/tests/CodexReporter.class');
}

require_once('../../../codex_tools/tests/simpletest/unit_tester.php');
require_once('../../../codex_tools/tests/simpletest/mock_objects.php');

require_once('pre.php');

//We define a group of test
class DocmanGroupTest extends GroupTest {
    function DocmanGroupTest($name = 'All Docman Plugin tests') {
        $this->GroupTest($name);
        
        $this->addTestFile(dirname(__FILE__).'/MetadataTest.php');
        $this->addTestFile(dirname(__FILE__).'/MetadataListOfValuesElementDaoTest.php');
        $this->addTestFile(dirname(__FILE__).'/PermissionsManagerTest.php');
        $this->addTestFile(dirname(__FILE__).'/PermissionsManagerPerfTest.php');
    }
}
if (CODEX_RUNNER === __FILE__) {
    $test =& new DocmanGroupTest();
    $test->run(new CodexReporter());
 }
?>
