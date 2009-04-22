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
 */
class SVNUpdateTest extends UnitTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function SVNUpdateTest($name = 'SVNUpdate test') {
        $this->UnitTestCase($name);
    }
    
    
    function testGetSVNInfo() {
        $svn_infos = "Path: .\n";
        $svn_infos .= "URL: https://partners.xrce.xerox.com/svnroot/codex/support/CX_3_6_SUP\n";
        $svn_infos .= "Repository Root: https://partners.xrce.xerox.com/svnroot/codex\n";
        $svn_infos .= "Repository UUID: df09dd2a-99fe-0310-ba0d-faeadf64de00\n";
        $svn_infos .= "Revision: 2599\n";
        $svn_infos .= "Node Kind: directory\n";
        $svn_infos .= "Schedule: normal\n";
        $svn_infos .= "Last Changed Author: guerin\n";
        $svn_infos .= "Last Changed Rev: 2553\n";
        $svn_infos .= "Last Changed Date: 2006-02-14 16:20:23 +0100 (Tue, 14 Feb 2006)";
        
        $su =& new SVNUpdateTestVersion($this);
        
        $this->assertEqual($su->_getSVNInfoRevision($svn_infos), 2599);
        $repository = "https://partners.xrce.xerox.com/svnroot/codex/support/CX_3_6_SUP";
        $this->assertEqual($su->_getSVNInfoRepository($svn_infos), $repository, "Repository should be : ".$repository." ; found: ".$su->_getSVNInfoRepository($svn_infos));
        
    }
    

    function testSetCommitsfromXML() {
        $svn_log_xml = '<?xml version="1.0" encoding="utf-8"?>';
        $svn_log_xml .= '<log>';
        $svn_log_xml .= ' <logentry revision="2926">';
        $svn_log_xml .= '  <author>guerin</author>';
        $svn_log_xml .= '  <date>2006-04-11T16:14:52.159548Z</date>';
        $svn_log_xml .= '  <paths>';
        $svn_log_xml .= '   <path action="M">/dev/trunk/src/www/include/trove.php</path>';
        $svn_log_xml .= '   <path action="A">/dev/trunk/src/www/softwaremap/trove_list.php</path>';
        $svn_log_xml .= '   <path action="D">/dev/trunk/src/etc/local.inc.dist</path>';
        $svn_log_xml .= '  </paths>';
        $svn_log_xml .= '  <msg>Add parameter in local.inc to specify the default trove category displayed\n';
        $svn_log_xml .= '       in the Software Map welcome page.\n';
        $svn_log_xml .= '  </msg>';
        $svn_log_xml .= ' </logentry>';
        $svn_log_xml .= ' <logentry revision="2925">';
        $svn_log_xml .= '  <author>nterray</author>';
        $svn_log_xml .= '  <date>2006-04-11T16:03:41.273381Z</date>';
        $svn_log_xml .= '  <paths>';
        $svn_log_xml .= '   <path action="M">/dev/trunk/src/db/mysql/database_initvalues.sql</path>';
        $svn_log_xml .= '  </paths>';
        $svn_log_xml .= '  <msg>Fixed typo</msg>';
        $svn_log_xml .= ' </logentry>';
        $svn_log_xml .= ' <logentry revision="9546">';
        $svn_log_xml .= '  <author>mnazaria</author>';
        $svn_log_xml .= '  <date>2008-08-22T16:40:18.197564Z</date>';
        $svn_log_xml .= '  <paths>';
        $svn_log_xml .= '   <path copyfrom-path="/dev/trunk" copyfrom-rev="9545" action="A">/support/CX_3_6_SUP</path>';
  		$svn_log_xml .= '  </paths>';
  		$svn_log_xml .= '  <msg>Codendi 3.6 support branch created.
</msg>';
		$svn_log_xml .= '  </logentry>';
        $svn_log_xml .= '</log>';
        
        $su =& new SVNUpdateTestVersion($this);
        
        $commits = array();
        $commits = $su->_setCommitsFromXML($svn_log_xml);
        
        $this->assertEqual(count($commits), 3, "Number of commits should be 3.");
        $commit_1 = $commits[0];
        $commit_2 = $commits[1];
        $commit_3 = $commits[2];
        $this->assertEqual($commit_1->getRevision(), 2926, "Revision of first commit should be 2926");
        $this->assertEqual($commit_2->getRevision(), 2925, "Revision of second commit should be 2925");
        $this->assertEqual($commit_3->getRevision(), 9546, "Revision of third commit should be 9546");
        $this->assertEqual($commit_1->getAuthor(), "guerin", "Revision of first commit should be guerin");
        $this->assertEqual($commit_2->getAuthor(), "nterray", "Revision of second commit should be nterray");
        $this->assertEqual($commit_3->getAuthor(), "mnazaria", "Revision of third commit should be mnazaria");
        $this->assertEqual($commit_1->getDate(), "2006-04-11T16:14:52.159548Z", "Date of first commit should be 2006-04-11T16:14:52.159548Z");
        $this->assertEqual($commit_2->getDate(), "2006-04-11T16:03:41.273381Z", "Date of second commit should be 2006-04-11T16:03:41.273381Z");
        $this->assertEqual($commit_3->getDate(), "2008-08-22T16:40:18.197564Z", "Date of third commit should be 2008-08-22T16:40:18.197564ZZ");
        $this->assertEqual(strlen($commit_1->getMessage()), 120, "Lenght of first commit message should be 120");
        $this->assertEqual(strlen($commit_2->getMessage()), 10, "Lenght of second commit message should be 10");
        $this->assertEqual(strlen($commit_3->getMessage()), 36, "Lenght of third commit message should be 36");
        $files_1 = $commit_1->getFiles();
        $files_2 = $commit_2->getFiles();
        $files_3 = $commit_3->getFiles();
        $this->assertEqual(count($files_1), 3, "Number of files of first commit message should be 3");
        $this->assertEqual(count($files_2), 1, "Number of files of first commit message should be 1");
        $this->assertEqual(count($files_3), 1, "Number of files of third commit message should be 1");
        $file_1_1 = $files_1[0];
        $file_1_2 = $files_1[1];
        $file_1_3 = $files_1[2];
        $file_2_1 = $files_2[0];
        $file_3_1 = $files_3[0];
        $this->assertEqual($file_1_1->getAction(), "M", "Action of file should be M");
        $this->assertEqual($file_1_1->getPath(), "/dev/trunk/src/www/include/trove.php", "Path of file should be /dev/trunk/src/www/include/trove.php");
        $this->assertEqual($file_1_2->getAction(), "A", "Action of file should be A");
        $this->assertEqual($file_1_2->getPath(), "/dev/trunk/src/www/softwaremap/trove_list.php", "Path of file should be /dev/trunk/src/www/softwaremap/trove_list.php");
        $this->assertEqual($file_1_3->getAction(), "D", "Action of file should be D");
        $this->assertEqual($file_1_3->getPath(), "/dev/trunk/src/etc/local.inc.dist", "Path of file should be /dev/trunk/src/etc/local.inc.dist");
        $this->assertEqual($file_2_1->getAction(), "M", "Action of file should be M");
        $this->assertEqual($file_2_1->getPath(), "/dev/trunk/src/db/mysql/database_initvalues.sql", "Path of file should be /dev/trunk/src/db/mysql/database_initvalues.sql");
        $this->assertEqual($file_3_1->getAction(), "A", "Action of file should be A");
        $this->assertEqual($file_3_1->getPath(), "/support/CX_3_6_SUP", "Path of file should be /support/CX_3_6_SUP");
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
