<?php

require_once(dirname(__FILE__).'/../include/SVNCommit.class.php');
require_once(dirname(__FILE__).'/../include/SVNCommitedFile.class.php');
Mock::generate('SVNCommitedFile');



/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Test the class SVNCommit
 */
class SVNCommitTest extends UnitTestCase {

    function testIsFilePartOfCommit() {
        $cf1 =& new MockSVNCommitedFile($this);
        $cf1->setReturnValue('getPath', "/upgrades/scripts/CodendiUpgrade_001.class.php");
        $cf1->setReturnValue('getAction', "A");
        $cf2 =& new MockSVNCommitedFile($this);
        $cf2->setReturnValue('getPath', "/upgrades/scripts/CodendiUpgrade_002.class.php");
        $cf3 =& new MockSVNCommitedFile($this);
        $cf3->setReturnValue('getPath', "/src/www/include/mail/mail.php");
        
        $c =& new SVNCommit();
        $c->setFiles(array($cf1, $cf2, $cf3));
        
        $cf4 =& new MockSVNCommitedFile($this);
        $cf4->setReturnValue('getPath', "/upgrades/scripts/CodendiUpgrade_002.class.php");
        $cf4->setReturnValue('getAction', "D");
        
        $cf5 =& new MockSVNCommitedFile($this);
        $cf5->setReturnValue('getPath', "/upgrades/scripts/CodendiUpgrade_003.class.php");
        $cf5->setReturnValue('getAction', "A");
        
        $cf6 =& new MockSVNCommitedFile($this);
        $cf6->setReturnValue('getPath', "/upgrades/scripts/CodendiUpgrade_001");
        $cf6->setReturnValue('getAction', "A");
        
        $this->assertTrue($c->isFilePartOfCommit($cf1));
        $this->assertTrue($c->isFilePartOfCommit($cf2));
        $this->assertTrue($c->isFilePartOfCommit($cf3));
        
        $this->assertTrue($c->isFilePartOfCommit($cf4));
        $this->assertFalse($c->isFilePartOfCommit($cf5));
        $this->assertFalse($c->isFilePartOfCommit($cf6));
        
    }
    
}

?>
