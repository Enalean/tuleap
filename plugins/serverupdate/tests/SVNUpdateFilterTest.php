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
    
    function testCompare() {
        $this->assertTrue(SVNUpdateFilter::_compare(0, 0, "eq"));
        $this->assertTrue(SVNUpdateFilter::_compare(0, 0, "ge"));
        $this->assertTrue(SVNUpdateFilter::_compare(0, 0, "le"));
        $this->assertFalse(SVNUpdateFilter::_compare(0, 0.0001, "eq"));
        $this->assertTrue(SVNUpdateFilter::_compare(1, 0, "ge"));
        $this->assertTrue(SVNUpdateFilter::_compare(1, 0, "gt"));
        $this->assertTrue(SVNUpdateFilter::_compare(-1.9, 1.2, "le"));
        $this->assertTrue(SVNUpdateFilter::_compare(-10, 9.9, "lt"));
    }
    
    
    
    function testAccept() {
        // 
        // Commit #1 : level 1, don't need a manual update
        //
        $c1 =& new MockSVNCommit($this);
        $c1->setReturnValue('getLevel', 1);
        $c1->setReturnValue('needManualUpdate', false);
        $uf1 =& new SVNUpdateFilter();
        
        // default filter should accept every commit
        $this->assertTrue($uf1->accept($c1));
        
        $uf1->addCriteria(LEVEL_CRITERIA, 4);
        $uf1->addCriteria(LEVEL_OPERATOR_CRITERIA, GREATER_THAN);
        $uf1->addCriteria(NEED_MANUAL_UPDATE_CRITERIA, ANY_MANUAL_UPDATE);
        $this->assertFalse($uf1->accept($c1));
        
        $uf1->addCriteria(LEVEL_OPERATOR_CRITERIA, LESS_THAN);
        $this->assertTrue($uf1->accept($c1));
        
        $uf1->addCriteria(LEVEL_CRITERIA, 1);
        $uf1->addCriteria(LEVEL_OPERATOR_CRITERIA, EQUAL);
        $this->assertTrue($uf1->accept($c1));
        
        $uf1->addCriteria(LEVEL_OPERATOR_CRITERIA, GREATER_OR_EQUAL);
        $this->assertTrue($uf1->accept($c1));
        
        //
        // Commit #2 : level 4, need a manual update
        //
        $c2 =& new MockSVNCommit($this);
        $c2->setReturnValue('getLevel', 4);
        $c2->setReturnValue('needManualUpdate', true);
        $uf2 =& new SVNUpdateFilter();
        // default filter should accept every commit
        $this->assertTrue($uf2->accept($c2));
        
        $uf2->addCriteria(LEVEL_CRITERIA, 4);
        $uf2->addCriteria(LEVEL_OPERATOR_CRITERIA, EQUAL);
        $uf2->addCriteria(NEED_MANUAL_UPDATE_CRITERIA, DONT_NEED_MANUAL_UPDATE);
        $this->assertFalse($uf2->accept($c2));
        
        $uf2->addCriteria(NEED_MANUAL_UPDATE_CRITERIA, ANY_MANUAL_UPDATE);
        $this->assertTrue($uf2->accept($c2));
        
        $uf2->addCriteria(NEED_MANUAL_UPDATE_CRITERIA, NEED_MANUAL_UPDATE);
        $this->assertTrue($uf2->accept($c2));
        
        $uf2->addCriteria(LEVEL_OPERATOR_CRITERIA, GREATER_OR_EQUAL);
        $this->assertTrue($uf2->accept($c2));
        
        $uf2->addCriteria(LEVEL_OPERATOR_CRITERIA, LESS_OR_EQUAL);
        $this->assertTrue($uf2->accept($c2));
        
    }
    
    function testApply() {
        // Commit #1 : level 1, don't need a manual update
        $c1 =& new MockSVNCommit($this);
        $c1->setReturnValue('getLevel', 1);
        $c1->setReturnValue('needManualUpdate', false);
        // Commit #2 : level 1, need a manual update
        $c2 =& new MockSVNCommit($this);
        $c2->setReturnValue('getLevel', 1);
        $c2->setReturnValue('needManualUpdate', true);
        // Commit #3 : level 3, don't need a manual update
        $c3 =& new MockSVNCommit($this);
        $c3->setReturnValue('getLevel', 3);
        $c3->setReturnValue('needManualUpdate', false);
        // Commit #4 : level 9, need a manual update
        $c4 =& new MockSVNCommit($this);
        $c4->setReturnValue('getLevel', 9);
        $c4->setReturnValue('needManualUpdate', true);
        // Commit #5 : level 9, don't need a manual update
        $c5 =& new MockSVNCommit($this);
        $c5->setReturnValue('getLevel', 9);
        $c5->setReturnValue('needManualUpdate', false);
        // Commit #6 : level 4, need a manual update
        $c6 =& new MockSVNCommit($this);
        $c6->setReturnValue('getLevel', 4);
        $c6->setReturnValue('needManualUpdate', true);
        
        $commits = array($c1, $c2, $c3, $c4, $c5, $c6);
        
        $uf1 =& new SVNUpdateFilter();
        $this->assertEqual($uf1->apply($commits), $commits);
        
        $uf1->addCriteria(LEVEL_CRITERIA, 1);
        $uf1->addCriteria(LEVEL_OPERATOR_CRITERIA, GREATER_THAN);
        $uf1->addCriteria(NEED_MANUAL_UPDATE_CRITERIA, ANY_MANUAL_UPDATE);
        $this->assertEqual($uf1->apply($commits), array($c3, $c4, $c5, $c6));
        
        $uf1->addCriteria(LEVEL_CRITERIA, 1);
        $uf1->addCriteria(LEVEL_OPERATOR_CRITERIA, GREATER_THAN);
        $uf1->addCriteria(NEED_MANUAL_UPDATE_CRITERIA, NEED_MANUAL_UPDATE);
        $this->assertEqual($uf1->apply($commits), array($c4, $c6));
        
        $uf1->addCriteria(LEVEL_CRITERIA, 1);
        $uf1->addCriteria(LEVEL_OPERATOR_CRITERIA, GREATER_THAN);
        $uf1->addCriteria(NEED_MANUAL_UPDATE_CRITERIA, NEED_MANUAL_UPDATE);
        $this->assertEqual($uf1->apply($commits), array($c4, $c6));
        
        $uf1->addCriteria(LEVEL_CRITERIA, 4);
        $uf1->addCriteria(LEVEL_OPERATOR_CRITERIA, GREATER_OR_EQUAL);
        $uf1->addCriteria(NEED_MANUAL_UPDATE_CRITERIA, NEED_MANUAL_UPDATE);
        $this->assertEqual($uf1->apply($commits), array($c4, $c6));
        
        $uf1->addCriteria(LEVEL_CRITERIA, 9);
        $uf1->addCriteria(LEVEL_OPERATOR_CRITERIA, LESS_THAN);
        $uf1->addCriteria(NEED_MANUAL_UPDATE_CRITERIA, NEED_MANUAL_UPDATE);
        $this->assertEqual($uf1->apply($commits), array($c2, $c6));
        
        $uf1->addCriteria(LEVEL_CRITERIA, 9);
        $uf1->addCriteria(LEVEL_OPERATOR_CRITERIA, LESS_THAN);
        $uf1->addCriteria(NEED_MANUAL_UPDATE_CRITERIA, ANY_MANUAL_UPDATE);
        $this->assertEqual($uf1->apply($commits), array($c1, $c2, $c3, $c6));
        
        $uf1->addCriteria(LEVEL_CRITERIA, 9);
        $uf1->addCriteria(LEVEL_OPERATOR_CRITERIA, LESS_OR_EQUAL);
        $uf1->addCriteria(NEED_MANUAL_UPDATE_CRITERIA, ANY_MANUAL_UPDATE);
        $this->assertEqual($uf1->apply($commits), array($c1, $c2, $c3, $c4, $c5, $c6));
        
    }
    
    
    
    
}

//We want to be able to run one test AND many tests
if (CODEX_RUNNER === __FILE__) {
    $test = &new SVNCommitMetaDataTest();
    $test->run(new CodexReporter());
 }
?>
