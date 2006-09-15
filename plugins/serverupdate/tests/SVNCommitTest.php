<?php
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('../../codex_tools/tests/CodexReporter.class');
}

require_once('../include/SVNComit.class');


require_once('tests/simpletest/unit_tester.php');
require_once('tests/simpletest/mock_objects.php'); //uncomment to use Mocks

require_once('../include/SVNCommitedFile.class');
Mock::generate('SVNCommitedFile');



/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: SVNCommitTest.php,v 1.1 2005/05/10 09:48:10 nterray Exp $
 *
 * Test the class SVNCommit
 */
class SVNCommitTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function SVNCommitTest($name = 'SVNCommit test') {
        $this->UnitTestCase($name);
    }
    
    function testIsFilePartOfCommit() {
        $cf1 =& new MockSVNCommitedFile($this);
        $cf1->setReturnValue('getPath', "/upgrades/scripts/CodeXUpgrade_001.class");
        $cf1->setReturnValue('getAction', "A");
        $cf2 =& new MockSVNCommitedFile($this);
        $cf2->setReturnValue('getPath', "/upgrades/scripts/CodeXUpgrade_002.class");
        $cf3 =& new MockSVNCommitedFile($this);
        $cf3->setReturnValue('getPath', "/src/www/include/mail/mail.php");
        
        $c =& new SVNCommit();
        $c->setFiles(array($cf1, $cf2, $cf3));
        
        $cf4 =& new MockSVNCommitedFile($this);
        $cf4->setReturnValue('getPath', "/upgrades/scripts/CodeXUpgrade_002.class");
        $cf4->setReturnValue('getAction', "D");
        
        $cf5 =& new MockSVNCommitedFile($this);
        $cf5->setReturnValue('getPath', "/upgrades/scripts/CodeXUpgrade_003.class");
        $cf5->setReturnValue('getAction', "A");
        
        $cf6 =& new MockSVNCommitedFile($this);
        $cf6->setReturnValue('getPath', "/upgrades/scripts/CodeXUpgrade_001");
        $cf6->setReturnValue('getAction', "A");
        
        $this->assertTrue($c->isFilePartOfCommit($cf1));
        $this->assertTrue($c->isFilePartOfCommit($cf2));
        $this->assertTrue($c->isFilePartOfCommit($cf3));
        
        $this->assertTrue($c->isFilePartOfCommit($cf4));
        $this->assertFalse($c->isFilePartOfCommit($cf5));
        $this->assertFalse($c->isFilePartOfCommit($cf6));
        
    }
    
}

//We want to be able to run one test AND many tests
if (CODEX_RUNNER === __FILE__) {
    $test = &new SVNCommitTest();
    $test->run(new CodexReporter());
 }
?>
