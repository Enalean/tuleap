<?php
require_once(dirname(__FILE__).'/../include/SVNCommitMetaData.class.php');

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
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
        $msg_1 = "-- update info --\nlevel=minor\nupdate=auto\n--\n\nThis is a commit message test\n";
        $msg_2 = "level=critical\nupdate=db,manual";
        $msg_3 = "this is a commit message without metadata infos";
        $msg_4 = "level=a\n";
        $msg_5 = "leve=normal\nupdate=toto";
        
        $m1 =& new SVNCommitMetaData();
        $this->assertNull($m1->getLevel());
        $this->assertNull($m1->getNeedManualUpdate());
        
        $m1->setMetaData($msg_1);
        $this->assertEqual($m1->getLevel(), LEVEL_VALUE_MINOR);
        $this->assertFalse($m1->getNeedManualUpdate());
        
        $m2 =& new SVNCommitMetaData();
        $m2->setMetaData($msg_2);
        $this->assertEqual($m2->getLevel(), LEVEL_VALUE_CRITICAL);
        $this->assertTrue($m2->getNeedManualUpdate());
        
        $m3 =& new SVNCommitMetaData();
        $m3->setMetaData($msg_3);
        $this->assertNull($m3->getLevel());
        $this->assertFalse($m3->getNeedManualUpdate()); // By default, don't need manual update
        
        $m4 =& new SVNCommitMetaData();
        $m4->setMetaData($msg_4);
        $this->assertNull($m4->getLevel());
        $this->assertFalse($m4->getNeedManualUpdate());
        
        $m5 =& new SVNCommitMetaData();
        $m5->setMetaData($msg_5);
        $this->assertNull($m5->getLevel());
        $this->assertFalse($m5->getNeedManualUpdate()); // By default, don't need manual update
        
    }
    
}
?>
