<?php
/* 
 * Copyright (c) The Codendi Team, Xerox, 2008. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */

require_once('common/include/URL.class.php');
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');
Mock::generatePartial('URL', 'URLTestVersion', array('getProjectDao','getForumDao','getNewsBytesDao','getArtifactDao'));

require_once('common/dao/ProjectDao.class.php');
Mock::generate('ProjectDao');
require_once('common/dao/ForumDao.class.php');
Mock::generate('ForumDao');
require_once('common/dao/NewsBytesDao.class.php');
Mock::generate('NewsBytesDao');
require_once('common/dao/ArtifactDao.class.php');
Mock::generate('ArtifactDao');

class URLTest extends UnitTestCase {
    function setUp() {
        $GLOBALS['sys_news_group'] = 46;
    }
    
    function tearDown() {
        unset($GLOBALS['group_id']);
        unset($GLOBALS['sys_news_group']);
        unset($_REQUEST['forum_id']);
        unset($GLOBALS['PATH_INFO']);
    }
    
    function testProjectsDontExist() {
        $url = new URLTestVersion($this);
        $dao = new MockProjectDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('rowCount', 0);
        $exists->setReturnValue('getRow', false);
        $dao->setReturnReference('searchByUnixGroupName', $exists);
        
        $url->setReturnReference('getProjectDao', $dao);
        $this->assertFalse($url->getGroupIdFromURL('/projects/dontexist/'));
    }
    
    function testProjectsExist() {
        $url = new URLTestVersion($this);
        $dao = new MockProjectDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('rowCount', 1);
        $exists->setReturnValue('getRow', false);
        $exists->setReturnValueAt(0, 'getRow', array('group_id' => '1'));
        $dao->setReturnReference('searchByUnixGroupName', $exists);
        
        $url->setReturnReference('getProjectDao', $dao);
        $this->assertEqual($url->getGroupIdFromURL('/projects/exist/'), 1);
    }
    
    function testViewVcDontExist() {
        $url = new URLTestVersion($this);
        $dao = new MockProjectDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('rowCount', 0);
        $exists->setReturnValue('getRow', false);
        $dao->setReturnReference('searchByUnixGroupName', $exists);
        
        $url->setReturnReference('getProjectDao', $dao);
        $this->assertFalse($url->getGroupIdFromURL('/viewvc.php/?roottype=svn&root=dontexist'));
    }
    
    function testViewVcExist() {
        $url = new URLTestVersion($this);
        $dao = new MockProjectDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('rowCount', 1);
        $exists->setReturnValue('getRow', false);
        $exists->setReturnValueAt(0, 'getRow', array('group_id' => '1'));
        $dao->setReturnReference('searchByUnixGroupName', $exists);
        
        $url->setReturnReference('getProjectDao', $dao);
        $this->assertEqual($url->getGroupIdFromURL('/viewvc.php/?roottype=svn&root=exist'), 1);
    }
    
    function testForumDontExist() {
        $url = new URLTestVersion($this);
        $dao = new MockForumDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('getRow', false);
        $dao->setReturnReference('searchByGroupForumId', $exists);
        
        $url->setReturnReference('getForumDao', $dao);
        $this->assertFalse($url->getGroupIdFromURL('/forum/forum.php?forum_id=dontexist'));
    }
    
    function testForumExist() {
        $url = new URLTestVersion($this);
        $dao = new MockForumDao($this);
        $exists = new MockDataAccessResult($this);
        
        $exists->setReturnValue('getRow', false);
        $exists->setReturnValueAt(0, 'getRow', array('group_id' => '1'));
        $dao->setReturnReference('searchByGroupForumId', $exists);
        $_REQUEST['forum_id']=1;
        $url->setReturnReference('getForumDao', $dao);
        $this->assertEqual($url->getGroupIdFromURL('/forum/forum.php?forum_id=exist'), 1);
    }
    
   function testNewsBytesDontExist() {
        $url = new URLTestVersion($this);
        $dao = new MockForumDao($this);
        $exists = new MockDataAccessResult($this);
        
        $exists->setReturnValue('getRow', false);
        $exists->setReturnValueAt(0, 'getRow', array('group_id' => '42'));
        $dao->setReturnReference('searchByGroupForumId', $exists);
        $_REQUEST['forum_id']=1;
        $group_id = $url->setReturnReference('getForumDao', $dao);
        $this->assertNotEqual($url->getGroupIdFromURL('/forum/forum.php?forum_id=exist'), $GLOBALS['sys_news_group']);
               
    }
    
   function testNewsBytesExist() {
        $url = new URLTestVersion($this);
        $dao = new MockForumDao($this);
        $exists = new MockDataAccessResult($this);
        
        $exists->setReturnValue('getRow', false);
        $exists->setReturnValueAt(0, 'getRow', array('group_id' => $GLOBALS['sys_news_group']));
        $dao->setReturnReference('searchByGroupForumId', $exists);
        $_REQUEST['forum_id']=1;
        $group_id = $url->setReturnReference('getForumDao', $dao);
        
        $dao2 = new MockNewsBytesDao($this);
        $exists2 = new MockDataAccessResult($this);
        $exists2->setReturnValue('getRow', false);
        $exists2->setReturnValueAt(0, 'getRow', array('group_id' =>$GLOBALS['sys_news_group']));
        $dao2->setReturnReference('searchByForumId', $exists2);
        $url->setReturnReference('getNewsBytesDao', $dao2);
        $this->assertEqual($url->getGroupIdFromURL('/forum/forum.php?forum_id=exist'), $GLOBALS['sys_news_group']);
               
    }

    
    function testArtifactDontExist(){
        $url = new URLTestVersion($this);
        $dao = new MockArtifactDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('getRow', false);
        $dao->setReturnReference('searchArtifactId', $exists);
        
        $url->setReturnReference('getArtifactDao', $dao);
        $this->assertFalse($url->getGroupIdFromURL('/tracker/download.php?artifact_id=dontexist'));
    }
    
    function testArtifactExist() {
        $url = new URLTestVersion($this);
        $dao = new MockArtifactDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('getRow', false);
        $exists->setReturnValueAt(0, 'getRow', array('group_id' => '1'));
        $dao->setReturnReference('searchArtifactId', $exists);
        $_REQUEST['artifact_id']=1;
        $url->setReturnReference('getArtifactDao', $dao);
        $this->assertEqual($url->getGroupIdFromURL('/tracker/download.php?artifact_id=exist'), 1);
    }
    
    function testFileDownload() {
        $url = new URL();
        $GLOBALS['PATH_INFO'] = '/101/1/p9_r4/toto.csv';
        $this->assertEqual($url->getGroupIdFromURL('/file/download.php/101/1/p9_r4/toto.csv'), 101);
    }

}
?>
