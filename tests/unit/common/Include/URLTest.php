<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 * Copyright (c) The Codendi Team, Xerox, 2008. All Rights Reserved.
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

class URLTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        $GLOBALS['sys_news_group'] = 46;
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['sys_news_group']);
        unset($_REQUEST['forum_id']);
        unset($_REQUEST['artifact_id']);
    }

    public function testProjectsSvnExist()
    {
        $url = new URL();
        $this->assertEquals('group_name', $url->getGroupNameFromSVNUrl('/viewvc.php/?roottype=svn&root=group_name'));
        $this->assertEquals('group.name', $url->getGroupNameFromSVNUrl('/viewvc.php/?roottype=svn&root=group.name'));
        $this->assertEquals('group_name', $url->getGroupNameFromSVNUrl('/viewvc.php/?root=group_name&roottype=svn'));
        $this->assertEquals(
            'group_name',
            $url->getGroupNamefromSVNUrl('/viewvc.php/?root=group_name&action=co&roottype=svn'),
        );
        $this->assertFalse($url->getGroupNameFromSVNUrl('/viewvc.php/?roo=group_name&roottype=svn'));
    }

    public function testProjectsDontExist()
    {
        $url    = \Mockery::mock(\URL::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao    = \Mockery::spy(\ProjectDao::class);
        $exists = \Mockery::spy(\DataAccessResult::class);
        $exists->shouldReceive('rowCount')->andReturns(0);
        $exists->shouldReceive('getRow')->andReturns(false);
        $dao->shouldReceive('searchByUnixGroupName')->andReturns($exists);

        $rule = \Mockery::spy(\Rule_ProjectName::class);
        $url->shouldReceive('getProjectNameRule')->andReturns($rule);
        $url->shouldReceive('getProjectDao')->andReturns($dao);
        $this->assertFalse($url->getGroupIdFromURL('/projects/dontexist/'));
    }

    public function testProjectsExist()
    {
        $url    = \Mockery::mock(\URL::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $url->shouldReceive('getForumDao');
        $url->shouldReceive('getNewsBytesDao');
        $url->shouldReceive('getArtifactDao');

        $exists = \Mockery::spy(\DataAccessResult::class);
        $exists->shouldReceive('rowCount')->andReturns(1);
        $exists->shouldReceive('getRow')->andReturns(['group_id' => '1'])->ordered();
        $exists->shouldReceive('getRow')->andReturns(false)->ordered();

        $exists1 = \Mockery::spy(\DataAccessResult::class);
        $exists1->shouldReceive('rowCount')->andReturns(1);
        $exists1->shouldReceive('getRow')->andReturns(['group_id' => '1'])->ordered();
        $exists1->shouldReceive('getRow')->andReturns(false)->ordered();

        $rule = \Mockery::spy(\Rule_ProjectName::class);
        $rule->shouldReceive('containsIllegalChars')->andReturns(false);
        $url->shouldReceive('getProjectNameRule')->andReturns($rule);

        $dao    = \Mockery::spy(\ProjectDao::class);
        $dao->shouldReceive('searchByUnixGroupName')->andReturns($exists)->ordered();
        $dao->shouldReceive('searchByUnixGroupName')->andReturns($exists1)->ordered();

        $url->shouldReceive('getProjectDao')->andReturns($dao);
        $this->assertEquals(1, $url->getGroupIdFromURL('/projects/exist/'));
        $this->assertNotEquals(1, $url->getGroupIdFromURL('/toto/projects/exist/'));
    }

    public function testViewVcDontExist()
    {
        $url = \Mockery::mock(\URL::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao = \Mockery::spy(\ProjectDao::class);
        $exists = \Mockery::spy(\DataAccessResult::class);
        $exists->shouldReceive('rowCount')->andReturns(0);
        $exists->shouldReceive('getRow')->andReturns(false);
        $rule = \Mockery::spy(\Rule_ProjectName::class);
        $url->shouldReceive('getProjectNameRule')->andReturns($rule);
        $rule->shouldReceive('containsIllegalChars')->andReturns(false);

        $dao->shouldReceive('searchByUnixGroupName')->andReturns($exists);

        $url->shouldReceive('getProjectDao')->andReturns($dao);
        $this->assertFalse($url->getGroupIdFromURL('/viewvc.php/?roottype=svn&root=dontexist'));
    }

    public function testViewVcExist()
    {
        $url = \Mockery::mock(\URL::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao = \Mockery::spy(\ProjectDao::class);
        $exists = \Mockery::spy(\DataAccessResult::class);
        $exists->shouldReceive('rowCount')->andReturns(1);
        $exists->shouldReceive('getRow')->andReturns(array('group_id' => '1'))->ordered();
        $exists->shouldReceive('getRow')->andReturns(false)->ordered();
        $dao->shouldReceive('searchByUnixGroupName')->andReturns($exists);
        $rule = \Mockery::spy(\Rule_ProjectName::class);
        $url->shouldReceive('getProjectNameRule')->andReturns($rule);
        $rule->shouldReceive('containsIllegalChars')->andReturns(false);

        $url->shouldReceive('getProjectDao')->andReturns($dao);
        $this->assertEquals($url->getGroupIdFromURL('/viewvc.php/?roottype=svn&root=exist'), 1);
    }

    public function testViewVcNotValidProjectName()
    {
        $url = \Mockery::mock(\URL::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $rule = \Mockery::spy(\Rule_ProjectName::class);
        $url->shouldReceive('getProjectNameRule')->andReturns($rule);
        $rule->shouldReceive('containsIllegalChars')->andReturns(true);

        $this->assertEquals($url->getGroupIdFromURL('/viewvc.php/?roottype=svn&root=ex(ist'), false);
    }

    public function testViewVcExistForProjectWithPoint()
    {
        $url = \Mockery::mock(\URL::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao = \Mockery::spy(\ProjectDao::class);
        $exists = \Mockery::spy(\DataAccessResult::class);
        $exists->shouldReceive('rowCount')->andReturns(1);
        $exists->shouldReceive('getRow')->andReturns(array('group_id' => '1'))->ordered();
        $exists->shouldReceive('getRow')->andReturns(false)->ordered();
        $rule = \Mockery::spy(\Rule_ProjectName::class);
        $url->shouldReceive('getProjectNameRule')->andReturns($rule);
        $rule->shouldReceive('containsIllegalChars')->andReturns(false);
        $dao->shouldReceive('searchByUnixGroupName')->with('test.svn')->once()->andReturns($exists);

        $url->shouldReceive('getProjectDao')->andReturns($dao);
        $this->assertEquals($url->getGroupIdFromURL('/viewvc.php/?roottype=svn&root=test.svn'), 1);
    }

    public function testForumDontExist()
    {
        $url = \Mockery::mock(\URL::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao = \Mockery::spy(\ForumDao::class);
        $exists = \Mockery::spy(\DataAccessResult::class);
        $exists->shouldReceive('getRow')->andReturns(false);
        $dao->shouldReceive('searchByGroupForumId')->andReturns($exists);

        $url->shouldReceive('getForumDao')->andReturns($dao);
        $this->assertNull($url->getGroupIdFromURL('/forum/forum.php?forum_id=dontexist'));
    }

    public function testForumExist()
    {
        $url = \Mockery::mock(\URL::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao = \Mockery::spy(\ForumDao::class);
        $exists = \Mockery::spy(\DataAccessResult::class);
        $exists->shouldReceive('getRow')->andReturns(array('group_id' => '1'))->ordered();
        $exists->shouldReceive('getRow')->andReturns(false)->ordered();
        $exists1 = \Mockery::spy(\DataAccessResult::class);
        $exists1->shouldReceive('getRow')->andReturns(array('group_id' => '1'))->ordered();
        $exists1->shouldReceive('getRow')->andReturns(false)->ordered();
        $dao->shouldReceive('searchByGroupForumId')->andReturns($exists)->ordered();
        $dao->shouldReceive('searchByGroupForumId')->andReturns($exists1)->ordered();
        $_REQUEST['forum_id'] = 1;
        $url->shouldReceive('getForumDao')->andReturns($dao);
        $this->assertEquals(1, $url->getGroupIdFromURL('/forum/forum.php?forum_id=exist'));
        $this->assertNotEquals(1, $url->getGroupIdFromURL('/toto/forum/forum.php?forum_id=exist'));
    }

    public function testNewsBytesDontExist()
    {
        $url = \Mockery::mock(\URL::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao = \Mockery::spy(\ForumDao::class);
        $exists = \Mockery::spy(\DataAccessResult::class);

        $exists->shouldReceive('getRow')->andReturns(array('group_id' => '42'))->ordered();
        $exists->shouldReceive('getRow')->andReturns(false)->ordered();
        $dao->shouldReceive('searchByGroupForumId')->andReturns($exists);
        $_REQUEST['forum_id'] = 1;
        $url->shouldReceive('getForumDao')->andReturns($dao);
        $this->assertNotEquals($GLOBALS['sys_news_group'], $url->getGroupIdFromURL('/forum/forum.php?forum_id=exist'));
    }

    public function testNewsBytesExist()
    {
        $url = \Mockery::mock(\URL::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao = \Mockery::spy(\ForumDao::class);
        $exists = \Mockery::spy(\DataAccessResult::class);

        $exists->shouldReceive('getRow')->andReturns(array('group_id' => $GLOBALS['sys_news_group']))->ordered();
        $exists->shouldReceive('getRow')->andReturns(false)->ordered();
        $dao->shouldReceive('searchByGroupForumId')->andReturns($exists)->ordered();
        $_REQUEST['forum_id'] = 1;
        $group_id = $url->shouldReceive('getForumDao')->andReturns($dao);

        $dao2 = \Mockery::spy(\NewsBytesDao::class);
        $exists2 = \Mockery::spy(\DataAccessResult::class);
        $exists2->shouldReceive('getRow')->andReturns(array('group_id' => $GLOBALS['sys_news_group']))->ordered();
        $exists2->shouldReceive('getRow')->andReturns(false)->ordered();
        $dao2->shouldReceive('searchByForumId')->andReturns($exists2)->ordered();
        $url->shouldReceive('getNewsBytesDao')->andReturns($dao2);
        $this->assertEquals($url->getGroupIdFromURL('/forum/forum.php?forum_id=exist'), $GLOBALS['sys_news_group']);
    }

    public function testArtifactDontExist()
    {
        $url = \Mockery::mock(\URL::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao = \Mockery::spy(\ArtifactDao::class);
        $exists = \Mockery::spy(\DataAccessResult::class);
        $exists->shouldReceive('getRow')->andReturns(false);
        $dao->shouldReceive('searchArtifactId')->andReturns($exists);

        $url->shouldReceive('getArtifactDao')->andReturns($dao);
        $this->assertNull($url->getGroupIdFromURL('/tracker/download.php?artifact_id=dontexist'));
    }

    public function testArtifactExist()
    {
        $url = \Mockery::mock(\URL::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $dao = \Mockery::spy(\ArtifactDao::class);
        $exists = \Mockery::spy(\DataAccessResult::class);
        $exists->shouldReceive('getRow')->andReturns(array('group_id' => '1'))->ordered();
        $exists->shouldReceive('getRow')->andReturns(false)->ordered();

        $exists1 = \Mockery::spy(\DataAccessResult::class);
        $exists1->shouldReceive('getRow')->andReturns(array('group_id' => '1'))->ordered();
        $exists1->shouldReceive('getRow')->andReturns(false)->ordered();

        $dao->shouldReceive('searchArtifactId')->andReturns($exists)->ordered();
        $dao->shouldReceive('searchArtifactId')->andReturns($exists1)->ordered();
        $_REQUEST['artifact_id'] = 1;
        $url->shouldReceive('getArtifactDao')->andReturns($dao);
        $this->assertEquals(1, $url->getGroupIdFromURL('/tracker/download.php?artifact_id=exist'));
        $this->assertNotEquals(1, $url->getGroupIdFromURL('/toto/tracker/download.php?artifact_id=exist'));
    }
}
