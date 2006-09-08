<?php
//We want to be able to run one test AND many tests
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('tests/CodexReporter.class');
}

require_once('tests/simpletest/unit_tester.php');
//require_once('tests/simpletest/mock_objects.php'); //uncomment to use Mocks
require_once('common/dao/CodexDataAccess.class');
require(getenv('CODEX_LOCAL_INC'));
        
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: CodexDataAccessTest.php,v 1.2 2005/08/01 14:29:51 nterray Exp $
 *
 * Tests the class CodexDataAccess
 */
class CodexDataAccessTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function CodexDataAccessTest($name = 'CodexDataAccess test') {
        $this->UnitTestCase($name);
    }

    function testConnection() {
        $da =& new CodexDataAccess();
        $this->assertFalse($da->isError());
        $this->assertIsA($da->fetch("select *"),'DataAccessResult');
    }
    
    function testSingleton() {
        $this->assertReference(
                CodexDataAccess::instance(),
                CodexDataAccess::instance());
        $this->assertIsA(CodexDataAccess::instance(), 'CodexDataAccess');
    }

}

if (CODEX_RUNNER === __FILE__) {
    $test = &new CodexDataAccessTest();
    $test->run(new CodexReporter());
 }
?>
