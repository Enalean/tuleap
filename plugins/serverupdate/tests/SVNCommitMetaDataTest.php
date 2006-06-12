<?php
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('../../codex_tools/tests/CodexReporter.class');
}

require_once('../include/SVNCommitMetaData.class');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: SVNCommitMetaDataTest.php,v 1.1 2005/05/10 09:48:10 nterray Exp $
 *
 * Test the class SVNCommitMetaData
 */
class SVNCommitMetaDataTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function SVNCommitMetaDataTest($name = 'SVNCommitMetaData test') {
        $this->UnitTestCase($name);
    }
    
    
    
    function testSetMetaData() {
        $msg_1 = "-- update info --\nlevel=0\nneed manual update=no\n--\n\nThis is a commit message test\n";
        $msg_2 = "level=9\nneed manual update=yes";
        $msg_3 = "this is a commit message without metadata infos";
        $msg_4 = "level=a\nneed manual update=no";
        $msg_5 = "leve=2\nneed manual update=3";
        
        $m1 =& new SVNCommitMetaData();
        $this->assertNull($m1->getLevel());
        $this->assertNull($m1->getNeedManualUpdate());
        
        $m1->setMetaData($msg_1);
        $this->assertEqual($m1->getLevel(), 0);
        $this->assertFalse($m1->getNeedManualUpdate());
        
        $m2 =& new SVNCommitMetaData();
        $m2->setMetaData($msg_2);
        $this->assertEqual($m2->getLevel(), 9);
        $this->assertTrue($m2->getNeedManualUpdate());
        
        $m3 =& new SVNCommitMetaData();
        $m3->setMetaData($msg_3);
        $this->assertNull($m3->getLevel());
        $this->assertNull($m3->getNeedManualUpdate());
        
        $m4 =& new SVNCommitMetaData();
        $m4->setMetaData($msg_4);
        $this->assertNull($m4->getLevel());
        $this->assertFalse($m4->getNeedManualUpdate());
        
        $m5 =& new SVNCommitMetaData();
        $m5->setMetaData($msg_5);
        $this->assertNull($m5->getLevel());
        $this->assertNull($m5->getNeedManualUpdate());
        
    }
    
}

//We want to be able to run one test AND many tests
if (CODEX_RUNNER === __FILE__) {
    $test = &new SVNCommitMetaDataTest();
    $test->run(new CodexReporter());
 }
?>
