<?php

require_once('common/dao/CodexDataAccess.class.php');
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
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

?>
