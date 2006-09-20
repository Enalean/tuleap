<?php
if (! defined('CODEX_RUNNER')) {
    define('CODEX_RUNNER', __FILE__);
    require_once('../../codex_tools/tests/CodexReporter.class');
}

require_once('../include/SVNUpdateFilter.class');

require_once('tests/simpletest/unit_tester.php');
require_once('tests/simpletest/mock_objects.php'); //uncomment to use Mocks

require_once('../include/SVNCommit.class');
Mock::generate('SVNCommit');


/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: SVNCommitMetaDataTest.php,v 1.1 2005/05/10 09:48:10 nterray Exp $
 *
 * Test the class SVNCommitMetaData
 */
class SVNUpdateFilterTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function SVNUpdateFilter($name = 'SVNUpdateFilter test') {
        $this->UnitTestCase($name);
    }
    
    function testAccept() {
        // 
        // Commit #1 : level minor
        //
        $c1 =& new MockSVNCommit($this);
        $c1->setReturnValue('getLevel', LEVEL_VALUE_MINOR);
        $uf1 =& new SVNUpdateFilter();
        
        // default filter should accept every commit
        $this->assertTrue($uf1->accept($c1));
        
        $uf1->addCriteria(LEVEL_CRITERIA, LEVEL_VALUE_MINOR);
        $this->assertTrue($uf1->accept($c1));
        
        $uf1->addCriteria(LEVEL_CRITERIA, LEVEL_VALUE_NORMAL);
        $this->assertFalse($uf1->accept($c1));
        
        $uf1->addCriteria(LEVEL_CRITERIA, LEVEL_VALUE_CRITICAL);
        $this->assertFalse($uf1->accept($c1));
        
        //
        // Commit #2 : level normal
        //
        $c2 =& new MockSVNCommit($this);
        $c2->setReturnValue('getLevel', LEVEL_VALUE_NORMAL);
        $uf2 =& new SVNUpdateFilter();
        // default filter should accept every commit
        $this->assertTrue($uf2->accept($c2));
        
        $uf2->addCriteria(LEVEL_CRITERIA, LEVEL_VALUE_MINOR);
        $this->assertFalse($uf2->accept($c2));
        
        $uf2->addCriteria(LEVEL_CRITERIA, LEVEL_VALUE_NORMAL);
        $this->assertTrue($uf2->accept($c2));
        
        $uf2->addCriteria(LEVEL_CRITERIA, LEVEL_VALUE_CRITICAL);
        $this->assertFalse($uf2->accept($c2));
        
        //
        // Commit #3 : level critical
        //
        $c3 =& new MockSVNCommit($this);
        $c3->setReturnValue('getLevel', LEVEL_VALUE_CRITICAL);
        $uf3 =& new SVNUpdateFilter();
        // default filter should accept every commit
        $this->assertTrue($uf3->accept($c3));
        
        $uf3->addCriteria(LEVEL_CRITERIA, LEVEL_VALUE_MINOR);
        $this->assertFalse($uf3->accept($c3));
        
        $uf3->addCriteria(LEVEL_CRITERIA, LEVEL_VALUE_NORMAL);
        $this->assertFalse($uf3->accept($c3));
        
        $uf3->addCriteria(LEVEL_CRITERIA, LEVEL_VALUE_CRITICAL);
        $this->assertTrue($uf3->accept($c3));
        
    }
    
    function testApply() {
        // Commit #1 : level minor
        $c1 =& new MockSVNCommit($this);
        $c1->setReturnValue('getLevel', LEVEL_VALUE_MINOR);
        // Commit #2 : level minor
        $c2 =& new MockSVNCommit($this);
        $c2->setReturnValue('getLevel', LEVEL_VALUE_MINOR);
        // Commit #3 : level normal
        $c3 =& new MockSVNCommit($this);
        $c3->setReturnValue('getLevel', LEVEL_VALUE_NORMAL);
        // Commit #4 : level critical
        $c4 =& new MockSVNCommit($this);
        $c4->setReturnValue('getLevel', LEVEL_VALUE_CRITICAL);
        // Commit #5 : level critical
        $c5 =& new MockSVNCommit($this);
        $c5->setReturnValue('getLevel', LEVEL_VALUE_CRITICAL);
        // Commit #6 : level normal
        $c6 =& new MockSVNCommit($this);
        $c6->setReturnValue('getLevel', LEVEL_VALUE_NORMAL);
        
        $commits = array($c1, $c2, $c3, $c4, $c5, $c6);
        
        $uf1 =& new SVNUpdateFilter();
        $this->assertEqual($uf1->apply($commits), $commits);
        
        $uf1->addCriteria(LEVEL_CRITERIA, LEVEL_VALUE_MINOR);
        $this->assertEqual($uf1->apply($commits), array($c1, $c2));
        
        $uf1->addCriteria(LEVEL_CRITERIA, LEVEL_VALUE_NORMAL);
        $this->assertEqual($uf1->apply($commits), array($c3, $c6));
        
        $uf1->addCriteria(LEVEL_CRITERIA, LEVEL_VALUE_CRITICAL);
        $this->assertEqual($uf1->apply($commits), array($c4, $c5));
        
    }
    
    
    
    
}

//We want to be able to run one test AND many tests
if (CODEX_RUNNER === __FILE__) {
    $test = &new SVNCommitMetaDataTest();
    $test->run(new CodexReporter());
 }
?>
