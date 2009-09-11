<?php
require_once(dirname(__FILE__).'/../include/SVNUpdate.class.php');
require_once(dirname(__FILE__).'/../include/SVNCommit.class.php');
Mock::generate('SVNCommit');

Mock::generatePartial(
    'SVNUpdate',
    'SVNUpdateTestVersion',
    array('getCommits')
);



/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Test the class SVNUpdate
 * 
 * Tests based upon SVN 1.6
 *  
 */
class SVNUpdateTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function SVNUpdateTest($name = 'SVNUpdate test') {
        $this->UnitTestCase($name);
    }
    
    
    /**
	 * Result of query "svn info" 
	 */
    function testGetSVNInfo() {
        $svn_infos = "Path: .\n";
        $svn_infos .= "URL: https://partners.xrce.xerox.com/svnroot/codendi/support/CX_3_6_SUP\n";
        $svn_infos .= "Repository Root: https://partners.xrce.xerox.com/svnroot/codendi\n";
        $svn_infos .= "Repository UUID: df09dd2a-99fe-0310-ba0d-faeadf64de00\n";
        $svn_infos .= "Revision: 2599\n";
        $svn_infos .= "Node Kind: directory\n";
        $svn_infos .= "Schedule: normal\n";
        $svn_infos .= "Last Changed Author: guerin\n";
        $svn_infos .= "Last Changed Rev: 2553\n";
        $svn_infos .= "Last Changed Date: 2006-02-14 16:20:23 +0100 (Tue, 14 Feb 2006)";
        
        $su =& new SVNUpdateTestVersion($this);
        
        $this->assertEqual($su->_getSVNInfoRevision($svn_infos), 2599);
        $repository = "https://partners.xrce.xerox.com/svnroot/codendi/support/CX_3_6_SUP";
        $this->assertEqual($su->_getSVNInfoRepository($svn_infos), $repository, "Repository should be : ".$repository." ; found: ".$su->_getSVNInfoRepository($svn_infos));
        
    }
    
	
    /**
	 * Result of query "svn log --xml -v -r {rev_X}:HEAD" 
	 */
    function testSetCommitsfromXML() {
    	$svn_log_xml = file_get_contents( dirname(__FILE__) . '/_fixtures/svn_1.6_log.xml' );
    	
    	$su =& new SVNUpdateTestVersion($this);
        $commits = array();
        $commits = $su->_setCommitsFromXML($svn_log_xml);
		
        $this->assertEqual(count($commits), 4);
        $commit_1 = $commits[0];
        $commit_2 = $commits[1];
        $commit_3 = $commits[2];
        $commit_4 = $commits[3];
        $this->assertEqual($commit_1->getRevision(), 2);
        $this->assertEqual($commit_2->getRevision(), 3);
        $this->assertEqual($commit_3->getRevision(), 4);
        $this->assertEqual($commit_4->getRevision(), 5);
        $this->assertEqual($commit_1->getAuthor(), "guerin");
        $this->assertEqual($commit_2->getAuthor(), "nazarian");
        $this->assertEqual($commit_3->getAuthor(), "terray");
        $this->assertEqual($commit_4->getAuthor(), "admin");
        $this->assertEqual($commit_1->getDate(), "2009-07-01T19:22:56.206193Z");
        $this->assertEqual($commit_2->getDate(), "2009-07-01T19:23:25.843363Z");
        $this->assertEqual($commit_3->getDate(), "2009-07-01T19:24:14.922066Z");
        $this->assertEqual($commit_4->getDate(), "2009-07-01T19:24:36.284289Z");
        $this->assertEqual(strlen($commit_1->getMessage()), 53);
        $this->assertEqual(strlen($commit_2->getMessage()), 23);
        $this->assertEqual(strlen($commit_3->getMessage()), 60);
        $this->assertEqual(strlen($commit_4->getMessage()), 31);
        $files_1 = $commit_1->getFiles();
        $files_2 = $commit_2->getFiles();
        $files_3 = $commit_3->getFiles();
        $files_4 = $commit_4->getFiles();
        $this->assertEqual(count($files_1), 1);
        $this->assertEqual(count($files_2), 1);
        $this->assertEqual(count($files_3), 3);
        $this->assertEqual(count($files_4), 1);
        $file_1_1 = $files_1[0];
        $file_2_1 = $files_2[0];
        $file_3_1 = $files_3[0];
        $file_3_2 = $files_3[1];
        $file_3_3 = $files_3[2];
        $file_4_1 = $files_4[0];
        $this->assertEqual($file_1_1->getAction(), "A");
        $this->assertEqual($file_1_1->getPath(), "/support/CX_4_0_TEST/README.fake.txt");
        $this->assertEqual($file_2_1->getAction(), "M");
        $this->assertEqual($file_2_1->getPath(), "/support/CX_4_0_TEST/README.fake.txt");
        $this->assertEqual($file_3_1->getAction(), "D");
        $this->assertEqual($file_3_1->getPath(), "/support/CX_4_0_TEST/README.fake.txt");
        $this->assertEqual($file_3_2->getAction(), "M");
        $this->assertEqual($file_3_2->getPath(), "/support/CX_4_0_TEST/fake1.txt");
        $this->assertEqual($file_3_3->getAction(), "A");
        $this->assertEqual($file_3_3->getPath(), "/support/CX_4_0_TEST/fake2.txt");
        $this->assertEqual($file_4_1->getAction(), "D");
        $this->assertEqual($file_4_1->getPath(), "/support/CX_4_0_TEST/txt.fake");
        
    }
    
    
    function testTestUpdate() {
    	$svn_merge_txt = file_get_contents( dirname(__FILE__) . '/_fixtures/svn_1.6_merge_dry-run_1.txt' );
    	
    	$su =& new SVNUpdateTestVersion($this);
        $conflictedLines = $su->getConflictedLines($svn_merge_txt);
        
        $this->assertEqual(count($conflictedLines), 1);
        $this->assertEqual($conflictedLines[0], "   C README.fake.txt");
    }
    
	function testTestUpdate2() {
    	$svn_merge_txt = file_get_contents( dirname(__FILE__) . '/_fixtures/svn_1.6_merge_dry-run_2.txt' );
    	
    	$su =& new SVNUpdateTestVersion($this);
        $conflictedLines = $su->getConflictedLines($svn_merge_txt);
        
        $this->assertEqual(count($conflictedLines), 3);
        $this->assertEqual($conflictedLines[0], "   C alpha");
        $this->assertEqual($conflictedLines[1], " C   beta");
        $this->assertEqual($conflictedLines[2], "C    gamma");
    }
    
	function testTestUpdate3() {
    	$svn_merge_txt = file_get_contents( dirname(__FILE__) . '/_fixtures/svn_1.6_merge_dry-run_3.txt' );
    	
    	$su =& new SVNUpdateTestVersion($this);
        $conflictedLines = $su->getConflictedLines($svn_merge_txt);
        
        $this->assertEqual(count($conflictedLines), 1);
        $this->assertEqual($conflictedLines[0], "Skipped missing target: 'baz.c'");
    }
    
	function testTestUpdate4() {
    	$svn_merge_txt = file_get_contents( dirname(__FILE__) . '/_fixtures/svn_1.6_merge_dry-run_4.txt' );
    	
    	$su =& new SVNUpdateTestVersion($this);
        $conflictedLines = $su->getConflictedLines($svn_merge_txt);
        
        $this->assertEqual(count($conflictedLines), 0);
    }
    
	function testTestUpdate5() {
    	$svn_merge_txt = file_get_contents( dirname(__FILE__) . '/_fixtures/svn_1.6_merge_dry-run_5.txt' );
    	
    	$su =& new SVNUpdateTestVersion($this);
        $conflictedLines = $su->getConflictedLines($svn_merge_txt);
        
        $this->assertEqual(count($conflictedLines), 0);
    }
    
    function testUpdate1() {
    	$svn_merge_txt = file_get_contents( dirname(__FILE__) . '/_fixtures/svn_1.6_update_1.txt' );
    	
    	$su =& new SVNUpdateTestVersion($this);
        $conflictedLines = $su->getConflictedLines($svn_merge_txt);
        
        $this->assertEqual(count($conflictedLines), 0);
    }
    
	function testUpdate2() {
    	$svn_merge_txt = file_get_contents( dirname(__FILE__) . '/_fixtures/svn_1.6_update_2.txt' );
    	
    	$su =& new SVNUpdateTestVersion($this);
        $conflictedLines = $su->getConflictedLines($svn_merge_txt);
        
        $this->assertEqual(count($conflictedLines), 0);
    }
    
	function testUpdate3() {
    	$svn_merge_txt = file_get_contents( dirname(__FILE__) . '/_fixtures/svn_1.6_update_3.txt' );
    	
    	$su =& new SVNUpdateTestVersion($this);
        $conflictedLines = $su->getConflictedLines($svn_merge_txt);
        
        $this->assertEqual(count($conflictedLines), 1);
        $this->assertEqual($conflictedLines[0], "C    fake1.txt");
    }
    
    /**
	 * Test that reproduce cx3 #1968 : ServerUpdate reports an error when a file is restored 
	 */
	function testUpdate4() {
    	$svn_merge_txt = file_get_contents( dirname(__FILE__) . '/_fixtures/svn_1.6_update_4.txt' );
    	
    	$su =& new SVNUpdateTestVersion($this);
        $conflictedLines = $su->getConflictedLines($svn_merge_txt);
        
        $this->assertEqual(count($conflictedLines), 0);
    }
    
    
    function testGetConflictedLines() {
        $merge_output = "U      /server/directory/path/file.txt\n";
        $merge_output .= "A      /server/directory/path/path2\n";
        $merge_output .= "C      /server/directory/path/file2.class.php\n";
        $merge_output .= "G      /server/directory/path/path2/file2.class.php\n";
        $merge_output .= "Skipped missing target: 'foo.php'";
        
        $su =& new SVNUpdateTestVersion($this);
        $conflictedLines = $su->getConflictedLines($merge_output);
        
        $this->assertEqual(count($conflictedLines), 2);
        $this->assertEqual($conflictedLines[0], "C      /server/directory/path/file2.class.php");
        $this->assertEqual($conflictedLines[1], "Skipped missing target: 'foo.php'");   
    }
    
	function testGetPropertiesUpdatedLines() {
        $merge_output = "U      /server/directory/path/file.txt\n";
        $merge_output .= "A      /server/directory/path/path2\n";
        $merge_output .= "G      /server/directory/path/path2/file2.class.php\n";
        $merge_output .= " C     /ignore_directory\n";
        $merge_output .= " U     .\n";
        
        $su =& new SVNUpdateTestVersion($this);
        $conflictedLines = $su->getConflictedLines($merge_output);
        
        $this->assertEqual(count($conflictedLines), 1);   
        $this->assertEqual($conflictedLines[0], " C     /ignore_directory");
    }
    
    function testGetPropertiesUpdates() {
    	$merge_output = "";
    	$merge_output .= " U   plugins/salome/www/c.php\n";
    	$merge_output .= " U   documentation/user_guide/xml/en_US/User_Guide.xml\n";
    	$merge_output .= " U   documentation/user_guide/xml/en_US/SiteOverview.xml\n";
    	$merge_output .= " U   documentation/user_guide/xml/en_US/BecomingACitizen.xml\n";
    	$merge_output .= " U   documentation/user_guide/xml/fr_FR/User_Guide.xml\n";
    	$merge_output .= " U   documentation/user_guide/xml/fr_FR/SiteOverview.xml\n";
    	$merge_output .= " U   documentation/user_guide/xml/fr_FR/BecomingACitizen.xml\n";
    	$merge_output .= "Updated to revision 9994.\n";
    	
    	$su =& new SVNUpdateTestVersion($this);
        $conflictedLines = $su->getConflictedLines($merge_output);
        
        $this->assertEqual(count($conflictedLines), 0);
    }
    
    function testGetSVNCommit() {
        $c1 =& new MockSVNCommit($this);
        $c1->setReturnValue('getRevision', 1);
        $c2 =& new MockSVNCommit($this);
        $c2->setReturnValue('getRevision', 2);
        $c3 =& new MockSVNCommit($this);
        $c3->setReturnValue('getRevision', 3);
        $c5 =& new MockSVNCommit($this);
        $c5->setReturnValue('getRevision', 5);
        $c22 =& new MockSVNCommit($this);
        $c22->setReturnValue('getRevision', 22);
        
        $commits = array($c1, $c2, $c3, $c5, $c22);
        
        $su =& new SVNUpdateTestVersion($this);
        $su->setReturnValue('getCommits', $commits);
        
        $cc1 = $su->getSVNCommit(1);
        $this->assertEqual($cc1->getRevision(), $c1->getRevision());
        $this->assertEqual($su->getSVNCommit(1), $c1);
        
        $cc2 = $su->getSVNCommit(2);
        $this->assertEqual($cc2->getRevision(), $c2->getRevision());
        $this->assertEqual($su->getSVNCommit(2), $c2);
        
        $this->assertNotEqual($cc2->getRevision(), $c1->getRevision());
        $this->assertNotEqual($su->getSVNCommit(2), $c1);
        
        $this->assertNull($su->getSVNCommit(4));
        $this->assertNotNull($su->getSVNCommit(5));
        $this->assertNull($su->getSVNCommit(6));
        
        $this->assertNull($su->getSVNCommit(21));
        $this->assertNotNull($su->getSVNCommit(22));
        $cc22 = $su->getSVNCommit(22);
        $this->assertEqual($cc22->getRevision(), $c22->getRevision());
        $this->assertEqual($su->getSVNCommit(22), $c22);
        $this->assertNull($su->getSVNCommit(23));
        
        $this->assertNull($su->getSVNCommit(0));
    }
    
    
    function testGetSVNCommitsBetween() {
        $c1 =& new MockSVNCommit($this);
        $c1->setReturnValue('getRevision', 1);
        $c2 =& new MockSVNCommit($this);
        $c2->setReturnValue('getRevision', 2);
        $c3 =& new MockSVNCommit($this);
        $c3->setReturnValue('getRevision', 3);
        $c5 =& new MockSVNCommit($this);
        $c5->setReturnValue('getRevision', 5);
        $c22 =& new MockSVNCommit($this);
        $c22->setReturnValue('getRevision', 22);
        
        $commits = array($c1, $c2, $c3, $c5, $c22);
        
        $su =& new SVNUpdateTestVersion($this);
        $su->setReturnValue('getCommits', $commits);
        
        $cc = $su->getSVNCommitsBetween(0, 25);
        $this->assertEqual(count($cc), count($commits));
        $cc0 = $cc[0];
        $this->assertEqual($cc0->getRevision(), $c1->getRevision());
        $cc1 = $cc[1];
        $this->assertEqual($cc1->getRevision(), $c2->getRevision());
        $cc2 = $cc[2];
        $this->assertEqual($cc2->getRevision(), $c3->getRevision());
        $cc3 = $cc[3];
        $this->assertEqual($cc3->getRevision(), $c5->getRevision());
        $cc4 = $cc[4];
        $this->assertEqual($cc4->getRevision(), $c22->getRevision());
        
        $cc = $su->getSVNCommitsBetween(1, 22);
        $this->assertEqual(count($cc), count($commits));
        
        $cc = $su->getSVNCommitsBetween(0, 22);
        $this->assertEqual(count($cc), count($commits));
        
        $cc = $su->getSVNCommitsBetween(2, 3);
        $this->assertEqual(count($cc), 2);
        
        $cc = $su->getSVNCommitsBetween(4, 21);
        $this->assertEqual(count($cc), 1);
        
        $cc = $su->getSVNCommitsBetween(5, 22);
        $this->assertEqual(count($cc), 2);
        
    }
    
    
    function testIsPresentInFurtherRevision() {
        $c1 =& new MockSVNCommit($this);
        $c1->setReturnValue('getRevision', 1);
        $c1->setReturnValue('isFilePartOfCommit', false);
        $c2 =& new MockSVNCommit($this);
        $c2->setReturnValue('getRevision', 2);
        $c2->setReturnValue('isFilePartOfCommit', false);
        $c3 =& new MockSVNCommit($this);
        $c3->setReturnValue('getRevision', 3);
        $c3->setReturnValue('isFilePartOfCommit', false);
        $c5 =& new MockSVNCommit($this);
        $c5->setReturnValue('getRevision', 5);
        $c5->setReturnValue('isFilePartOfCommit', true);
        $c22 =& new MockSVNCommit($this);
        $c22->setReturnValue('getRevision', 22);
        $c22->setReturnValue('isFilePartOfCommit', false);
        
        $commits = array($c1, $c2, $c3, $c5, $c22);
        
        $su =& new SVNUpdateTestVersion($this);
        $su->setReturnValue('getCommits', $commits);
        
        $this->assertTrue($su->isPresentInFurtherRevision("toto", 1));
        $this->assertTrue($su->isPresentInFurtherRevision("toto", 2));
        $this->assertTrue($su->isPresentInFurtherRevision("toto", 3));
        $this->assertTrue($su->isPresentInFurtherRevision("toto", 4));
        $this->assertFalse($su->isPresentInFurtherRevision("toto", 5));
        $this->assertFalse($su->isPresentInFurtherRevision("toto", 6));
        $this->assertFalse($su->isPresentInFurtherRevision("toto", 21));
        $this->assertFalse($su->isPresentInFurtherRevision("toto", 22));
        
    }
    
    
}
?>