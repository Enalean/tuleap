<?php
/**
 * Copyright (c) The Codendi Team, Xerox, 2008. All Rights Reserved.
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

Mock::generate('DataAccessResult');
Mock::generatePartial('URL', 'URLTestVersion', array('getProjectDao','getForumDao','getNewsBytesDao','getArtifactDao', 'getProjectNameRule'));

Mock::generate('ProjectDao');
Mock::generate('ForumDao');
Mock::generate('NewsBytesDao');
Mock::generate('ArtifactDao');
Mock::generate('Rule_ProjectName');

class URLTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $GLOBALS['sys_news_group'] = 46;
    }

    public function tearDown()
    {
        unset($GLOBALS['sys_news_group']);
        unset($_REQUEST['forum_id']);
        unset($_REQUEST['artifact_id']);
        parent::tearDown();
    }

    function testProjectsSvnExist()
    {
        $url = new URL();
        $this->assertEqual($url->getGroupNameFromSVNUrl('/viewvc.php/?roottype=svn&root=group_name'), 'group_name');
        $this->assertEqual($url->getGroupNameFromSVNUrl('/viewvc.php/?roottype=svn&root=group.name'), 'group.name');
        $this->assertEqual($url->getGroupNameFromSVNUrl('/viewvc.php/?root=group_name&roottype=svn'), 'group_name');
        $this->assertEqual($url->getGroupNamefromSVNUrl('/viewvc.php/?root=group_name&action=co&roottype=svn'), 'group_name');
        $this->assertEqual($url->getGroupNameFromSVNUrl('/viewvc.php/?roo=group_name&roottype=svn'), false);
    }

    function testProjectsDontExist()
    {
        $url = new URLTestVersion($this);
        $dao = new MockProjectDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('rowCount', 0);
        $exists->setReturnValue('getRow', false);
        $dao->setReturnReference('searchByUnixGroupName', $exists);

        $rule = new MockRule_ProjectName();
        $url->setReturnValue('getProjectNameRule', $rule);
        $url->setReturnReference('getProjectDao', $dao);
        $this->assertFalse($url->getGroupIdFromURL('/projects/dontexist/'));
    }

    function testProjectsExist()
    {
        $url = new URLTestVersion($this);
        $dao = new MockProjectDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('rowCount', 1);
        $exists->setReturnValue('getRow', false);
        $exists->setReturnValueAt(0, 'getRow', array('group_id' => '1'));

        $exists1 = new MockDataAccessResult($this);
        $exists1->setReturnValue('rowCount', 1);
        $exists1->setReturnValue('getRow', false);
        $exists1->setReturnValueAt(0, 'getRow', array('group_id' => '1'));
        $rule = new MockRule_ProjectName();
        $url->setReturnValue('getProjectNameRule', $rule);
        $rule->setReturnValue('containsIllegalChars', false);

        $dao->setReturnReferenceAt(0, 'searchByUnixGroupName', $exists);
        $dao->setReturnReferenceAt(1, 'searchByUnixGroupName', $exists1);

        $url->setReturnReference('getProjectDao', $dao);
        $this->assertEqual($url->getGroupIdFromURL('/projects/exist/'), 1);
        $this->assertNotEqual($url->getGroupIdFromURL('/toto/projects/exist/'), 1);
    }

    function testViewVcDontExist()
    {
        $url = new URLTestVersion($this);
        $dao = new MockProjectDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('rowCount', 0);
        $exists->setReturnValue('getRow', false);
        $rule = new MockRule_ProjectName();
        $url->setReturnValue('getProjectNameRule', $rule);
        $rule->setReturnValue('containsIllegalChars', false);

        $dao->setReturnReference('searchByUnixGroupName', $exists);

        $url->setReturnReference('getProjectDao', $dao);
        $this->assertFalse($url->getGroupIdFromURL('/viewvc.php/?roottype=svn&root=dontexist'));
    }

    function testViewVcExist()
    {
        $url = new URLTestVersion($this);
        $dao = new MockProjectDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('rowCount', 1);
        $exists->setReturnValue('getRow', false);
        $exists->setReturnValueAt(0, 'getRow', array('group_id' => '1'));
        $dao->setReturnReference('searchByUnixGroupName', $exists);
        $rule = new MockRule_ProjectName();
        $url->setReturnValue('getProjectNameRule', $rule);
        $rule->setReturnValue('containsIllegalChars', false);

        $url->setReturnReference('getProjectDao', $dao);
        $this->assertEqual($url->getGroupIdFromURL('/viewvc.php/?roottype=svn&root=exist'), 1);
    }

    function testViewVcNotValidProjectName()
    {
        $url = new URLTestVersion($this);
        $rule = new MockRule_ProjectName();
        $url->setReturnValue('getProjectNameRule', $rule);
        $rule->setReturnValue('containsIllegalChars', true);

        $this->assertEqual($url->getGroupIdFromURL('/viewvc.php/?roottype=svn&root=ex(ist'), false);
    }

    function testViewVcExistForProjectWithPoint()
    {
        $url = new URLTestVersion($this);
        $dao = new MockProjectDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('rowCount', 1);
        $exists->setReturnValue('getRow', false);
        $exists->setReturnValueAt(0, 'getRow', array('group_id' => '1'));
        $rule = new MockRule_ProjectName();
        $url->setReturnValue('getProjectNameRule', $rule);
        $rule->setReturnValue('containsIllegalChars', false);

        $dao->expectOnce('searchByUnixGroupName', array('test.svn'));
        $dao->setReturnReference('searchByUnixGroupName', $exists);

        $url->setReturnReference('getProjectDao', $dao);
        $this->assertEqual($url->getGroupIdFromURL('/viewvc.php/?roottype=svn&root=test.svn'), 1);
    }

    function testForumDontExist()
    {
        $url = new URLTestVersion($this);
        $dao = new MockForumDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('getRow', false);
        $dao->setReturnReference('searchByGroupForumId', $exists);

        $url->setReturnReference('getForumDao', $dao);
        $this->assertFalse($url->getGroupIdFromURL('/forum/forum.php?forum_id=dontexist'));
    }

    function testForumExist()
    {
        $url = new URLTestVersion($this);
        $dao = new MockForumDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('getRow', false);
        $exists->setReturnValueAt(0, 'getRow', array('group_id' => '1'));
        $exists1 = new MockDataAccessResult($this);
        $exists1->setReturnValue('getRow', false);
        $exists1->setReturnValueAt(0, 'getRow', array('group_id' => '1'));
        $dao->setReturnReferenceAt(0, 'searchByGroupForumId', $exists);
        $dao->setReturnReferenceAt(1, 'searchByGroupForumId', $exists1);
        $_REQUEST['forum_id']=1;
        $url->setReturnReference('getForumDao', $dao);
        $this->assertEqual($url->getGroupIdFromURL('/forum/forum.php?forum_id=exist'), 1);
        $this->assertNotEqual($url->getGroupIdFromURL('/toto/forum/forum.php?forum_id=exist'), 1);
    }

    function testNewsBytesDontExist()
    {
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

    function testNewsBytesExist()
    {
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


    function testArtifactDontExist()
    {
        $url = new URLTestVersion($this);
        $dao = new MockArtifactDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('getRow', false);
        $dao->setReturnReference('searchArtifactId', $exists);

        $url->setReturnReference('getArtifactDao', $dao);
        $this->assertFalse($url->getGroupIdFromURL('/tracker/download.php?artifact_id=dontexist'));
    }

    function testArtifactExist()
    {
        $url = new URLTestVersion($this);
        $dao = new MockArtifactDao($this);
        $exists = new MockDataAccessResult($this);
        $exists->setReturnValue('getRow', false);
        $exists->setReturnValueAt(0, 'getRow', array('group_id' => '1'));

        $exists1 = new MockDataAccessResult($this);
        $exists1->setReturnValue('getRow', false);
        $exists1->setReturnValueAt(0, 'getRow', array('group_id' => '1'));

        $dao->setReturnReferenceAt(0, 'searchArtifactId', $exists);
        $dao->setReturnReferenceAt(1, 'searchArtifactId', $exists1);
        $_REQUEST['artifact_id']=1;
        $url->setReturnReference('getArtifactDao', $dao);
        $this->assertEqual($url->getGroupIdFromURL('/tracker/download.php?artifact_id=exist'), 1);
        $this->assertNotEqual($url->getGroupIdFromURL('/toto/tracker/download.php?artifact_id=exist'), 1);
    }
}
