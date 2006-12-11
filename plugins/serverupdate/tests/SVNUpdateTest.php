<?php
require_once(dirname(__FILE__).'/../include/SVNUpdate.class');
require_once(dirname(__FILE__).'/../include/SVNCommit.class');
Mock::generate('SVNCommit');

Mock::generatePartial(
    'SVNUpdate',
    'SVNUpdateTestVersion',
    array('getCommits')
);



/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: SVNUpdateTest.php,v 1.1 2005/05/10 09:48:10 nterray Exp $
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
        $svn_infos .= "URL: https://partners.xrce.xerox.com/svnroot/codex/support/CX_2_6_SUP\n";
        $svn_infos .= "Repository UUID: df09dd2a-99fe-0310-ba0d-faeadf64de00\n";
        $svn_infos .= "Revision: 2599\n";
        $svn_infos .= "Node Kind: directory\n";
        $svn_infos .= "Schedule: normal\n";
        $svn_infos .= "Last Changed Author: guerin\n";
        $svn_infos .= "Last Changed Rev: 2553\n";
        $svn_infos .= "Last Changed Date: 2006-02-14 16:20:23 +0100 (Tue, 14 Feb 2006)";
        
        $this->assertEqual(SVNUpdate::_getSVNInfoRevision($svn_infos), 2599);
        $repository = "https://partners.xrce.xerox.com/svnroot/codex/support/CX_2_6_SUP";
        $this->assertEqual(SVNUpdate::_getSVNInfoRepository($svn_infos), $repository);
        
    }
    

    /*function testSetCommitsfromXML() {
        $svn_log_xml = '<?xml version="1.0" encoding="utf-8"?>';
        $svn_log_xml .= '<log>';
        $svn_log_xml .= ' <logentry revision="2926">';
        $svn_log_xml .= '  <author>guerin</author>';
        $svn_log_xml .= '  <date>2006-04-11T16:14:52.159548Z</date>';
        $svn_log_xml .= '  <paths>';
        $svn_log_xml .= '   <path action="M">/dev/trunk/src/www/include/trove.php</path>';
        $svn_log_xml .= '   <path action="M">/dev/trunk/src/www/softwaremap/trove_list.php</path>';
        $svn_log_xml .= '   <path action="M">/dev/trunk/src/etc/local.inc.dist</path>';
        $svn_log_xml .= '  </paths>';
        $svn_log_xml .= '  <msg>Add parameter in local.inc to specify the default trove category displayed\n';
        $svn_log_xml .= '       in the Software Map welcome page.\n';
        $svn_log_xml .= '  </msg>';
        $svn_log_xml .= ' </logentry>';
        $svn_log_xml .= ' <logentry revision="2925">';
        $svn_log_xml .= '  <author>guerin</author>';
        $svn_log_xml .= '  <date>2006-04-11T16:03:41.273381Z</date>';
        $svn_log_xml .= '  <paths>';
        $svn_log_xml .= '   <path action="M">/dev/trunk/src/db/mysql/database_initvalues.sql</path>';
        $svn_log_xml .= '  </paths>';
        $svn_log_xml .= '  <msg>Fixed typo</msg>';
        $svn_log_xml .= ' </logentry>';
        $svn_log_xml .= '</log>';
        
        $svnupdate =& new MockSVNUpdate($this);
        
        $commits = array();
        $commits = $svnupdate->_setCommitsFromXML($svn_log_xml);
        
        $this->assertEqual(count($commits), 2);
        
    }*/
    
    
    function testGetConflictedLines() {
        $merge_output = "U      /server/directory/path/file.txt\n";
        $merge_output .= "A      /server/directory/path/path2\n";
        $merge_output .= "C      /server/directory/path/file2.class\n";
        $merge_output .= "G      /server/directory/path/path2/file2.class\n";
        $merge_output .= "Skipped missing target: 'foo.php'";
        
        $conflictedLines = SVNUpdate::getConflictedLines($merge_output);
        
        $this->assertEqual(count($conflictedLines), 2);
        $this->assertEqual($conflictedLines[0], "C      /server/directory/path/file2.class");
        $this->assertEqual($conflictedLines[1], "Skipped missing target: 'foo.php'");   
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
