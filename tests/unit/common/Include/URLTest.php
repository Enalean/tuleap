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

class URLTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore
{
    use \Tuleap\ForgeConfigSandbox;

    protected function setUp(): void
    {
        ForgeConfig::set('sys_news_group', 46);
    }

    protected function tearDown(): void
    {
        unset($_REQUEST['forum_id'], $_REQUEST['artifact_id']);
    }

    public function testProjectsSvnExist(): void
    {
        $url = new URL();
        self::assertEquals('group_name', $url->getGroupNameFromSVNUrl('/viewvc.php/?roottype=svn&root=group_name'));
        self::assertEquals('group.name', $url->getGroupNameFromSVNUrl('/viewvc.php/?roottype=svn&root=group.name'));
        self::assertEquals('group_name', $url->getGroupNameFromSVNUrl('/viewvc.php/?root=group_name&roottype=svn'));
        self::assertEquals(
            'group_name',
            $url->getGroupNamefromSVNUrl('/viewvc.php/?root=group_name&action=co&roottype=svn'),
        );
        self::assertFalse($url->getGroupNameFromSVNUrl('/viewvc.php/?roo=group_name&roottype=svn'));
    }

    public function testProjectsDontExist(): void
    {
        $url    = $this->createPartialMock(\URL::class, [
            'getProjectNameRule',
            'getProjectDao',
        ]);
        $dao    = $this->createMock(\ProjectDao::class);
        $exists = $this->createMock(\DataAccessResult::class);
        $exists->method('rowCount')->willReturn(0);
        $exists->method('getRow')->willReturn(false);
        $dao->method('searchByUnixGroupName')->willReturn($exists);

        $rule = $this->createMock(\Rule_ProjectName::class);
        $rule->method('containsIllegalChars');
        $url->method('getProjectNameRule')->willReturn($rule);
        $url->method('getProjectDao')->willReturn($dao);
        self::assertFalse($url->getGroupIdFromURL('/projects/dontexist/'));
    }

    public function testProjectsExist(): void
    {
        $url = $this->createPartialMock(\URL::class, [
            'getForumDao',
            'getNewsBytesDao',
            'getArtifactDao',
            'getProjectNameRule',
            'getProjectDao',
        ]);
        $url->method('getForumDao');
        $url->method('getNewsBytesDao');
        $url->method('getArtifactDao');

        $exists = $this->createMock(\DataAccessResult::class);
        $exists->method('rowCount')->willReturn(1);
        $exists->method('getRow')->willReturnOnConsecutiveCalls(['group_id' => '1'], false);

        $exists1 = $this->createMock(\DataAccessResult::class);
        $exists1->method('rowCount')->willReturn(1);
        $exists1->method('getRow')->willReturnOnConsecutiveCalls(['group_id' => '1'], false);

        $rule = $this->createMock(\Rule_ProjectName::class);
        $rule->method('containsIllegalChars')->willReturn(false);
        $url->method('getProjectNameRule')->willReturn($rule);

        $dao = $this->createMock(\ProjectDao::class);
        $dao->method('searchByUnixGroupName')->willReturnOnConsecutiveCalls($exists, $exists1);

        $url->method('getProjectDao')->willReturn($dao);
        self::assertEquals(1, $url->getGroupIdFromURL('/projects/exist/'));
        self::assertNotEquals(1, $url->getGroupIdFromURL('/toto/projects/exist/'));
    }

    public function testViewVcDontExist(): void
    {
        $url    = $this->createPartialMock(\URL::class, [
            'getProjectNameRule',
            'getProjectDao',
        ]);
        $dao    = $this->createMock(\ProjectDao::class);
        $exists = $this->createMock(\DataAccessResult::class);
        $exists->method('rowCount')->willReturn(0);
        $exists->method('getRow')->willReturn(false);
        $rule = $this->createMock(\Rule_ProjectName::class);
        $url->method('getProjectNameRule')->willReturn($rule);
        $rule->method('containsIllegalChars')->willReturn(false);

        $dao->method('searchByUnixGroupName')->willReturn($exists);

        $url->method('getProjectDao')->willReturn($dao);
        self::assertFalse($url->getGroupIdFromURL('/viewvc.php/?roottype=svn&root=dontexist'));
    }

    public function testViewVcExist(): void
    {
        $url    = $this->createPartialMock(\URL::class, [
            'getProjectNameRule',
            'getProjectDao',
        ]);
        $dao    = $this->createMock(\ProjectDao::class);
        $exists = $this->createMock(\DataAccessResult::class);
        $exists->method('rowCount')->willReturn(1);
        $exists->method('getRow')->willReturnOnConsecutiveCalls(['group_id' => '1'], false);
        $dao->method('searchByUnixGroupName')->willReturn($exists);
        $rule = $this->createMock(\Rule_ProjectName::class);
        $url->method('getProjectNameRule')->willReturn($rule);
        $rule->method('containsIllegalChars')->willReturn(false);

        $url->method('getProjectDao')->willReturn($dao);
        self::assertEquals(1, $url->getGroupIdFromURL('/viewvc.php/?roottype=svn&root=exist'));
    }

    public function testViewVcNotValidProjectName(): void
    {
        $url  = $this->createPartialMock(\URL::class, [
            'getProjectNameRule',
        ]);
        $rule = $this->createMock(\Rule_ProjectName::class);
        $url->method('getProjectNameRule')->willReturn($rule);
        $rule->method('containsIllegalChars')->willReturn(true);

        self::assertFalse($url->getGroupIdFromURL('/viewvc.php/?roottype=svn&root=ex(ist'));
    }

    public function testViewVcExistForProjectWithPoint(): void
    {
        $url    = $this->createPartialMock(\URL::class, [
            'getProjectNameRule',
            'getProjectDao',
        ]);
        $dao    = $this->createMock(\ProjectDao::class);
        $exists = $this->createMock(\DataAccessResult::class);
        $exists->method('rowCount')->willReturn(1);
        $exists->method('getRow')->willReturnOnConsecutiveCalls(['group_id' => '1'], false);
        $rule = $this->createMock(\Rule_ProjectName::class);
        $url->method('getProjectNameRule')->willReturn($rule);
        $rule->method('containsIllegalChars')->willReturn(false);
        $dao->expects(self::once())->method('searchByUnixGroupName')->with('test.svn')->willReturn($exists);

        $url->method('getProjectDao')->willReturn($dao);
        self::assertEquals(1, $url->getGroupIdFromURL('/viewvc.php/?roottype=svn&root=test.svn'));
    }

    public function testForumDontExist(): void
    {
        $url    = $this->createPartialMock(\URL::class, [
            'getForumDao',
        ]);
        $dao    = $this->createMock(\ForumDao::class);
        $exists = $this->createMock(\DataAccessResult::class);
        $exists->method('getRow')->willReturn(false);
        $dao->method('searchByGroupForumId')->willReturn($exists);

        $url->method('getForumDao')->willReturn($dao);
        self::assertNull($url->getGroupIdFromURL('/forum/forum.php?forum_id=dontexist'));
    }

    public function testForumExist(): void
    {
        $url    = $this->createPartialMock(\URL::class, [
            'getForumDao',
        ]);
        $dao    = $this->createMock(\ForumDao::class);
        $exists = $this->createMock(\DataAccessResult::class);
        $exists->method('getRow')->willReturnOnConsecutiveCalls(['group_id' => '1'], false);
        $exists1 = $this->createMock(\DataAccessResult::class);
        $exists1->method('getRow')->willReturnOnConsecutiveCalls(['group_id' => '1'], false);
        $dao->method('searchByGroupForumId')->willReturnOnConsecutiveCalls($exists, $exists1);
        $_REQUEST['forum_id'] = 1;
        $url->method('getForumDao')->willReturn($dao);
        self::assertEquals(1, $url->getGroupIdFromURL('/forum/forum.php?forum_id=exist'));
        self::assertNotEquals(1, $url->getGroupIdFromURL('/toto/forum/forum.php?forum_id=exist'));
    }

    public function testNewsBytesDontExist(): void
    {
        $url    = $this->createPartialMock(\URL::class, [
            'getForumDao',
        ]);
        $dao    = $this->createMock(\ForumDao::class);
        $exists = $this->createMock(\DataAccessResult::class);

        $exists->method('getRow')->willReturn(['group_id' => '42'], false);
        $dao->method('searchByGroupForumId')->willReturn($exists);
        $_REQUEST['forum_id'] = 1;
        $url->method('getForumDao')->willReturn($dao);
        self::assertNotEquals(ForgeConfig::get('sys_news_group'), $url->getGroupIdFromURL('/forum/forum.php?forum_id=exist'));
    }

    public function testNewsBytesExist(): void
    {
        $url    = $this->createPartialMock(\URL::class, [
            'getForumDao',
            'getNewsBytesDao',
        ]);
        $dao    = $this->createMock(\ForumDao::class);
        $exists = $this->createMock(\DataAccessResult::class);

        $exists->method('getRow')->willReturnOnConsecutiveCalls(['group_id' => ForgeConfig::get('sys_news_group')], false);
        $dao->method('searchByGroupForumId')->willReturn($exists);
        $_REQUEST['forum_id'] = 1;
        $url->method('getForumDao')->willReturn($dao);

        $dao2    = $this->createMock(\NewsBytesDao::class);
        $exists2 = $this->createMock(\DataAccessResult::class);
        $exists2->method('getRow')->willReturnOnConsecutiveCalls(['group_id' => ForgeConfig::get('sys_news_group')], false);
        $dao2->method('searchByForumId')->willReturn($exists2);
        $url->method('getNewsBytesDao')->willReturn($dao2);
        self::assertEquals($url->getGroupIdFromURL('/forum/forum.php?forum_id=exist'), ForgeConfig::get('sys_news_group'));
    }

    public function testArtifactDontExist(): void
    {
        $url    = $this->createPartialMock(\URL::class, [
            'getArtifactDao',
        ]);
        $dao    = $this->createMock(\ArtifactDao::class);
        $exists = $this->createMock(\DataAccessResult::class);
        $exists->method('getRow')->willReturn(false);
        $dao->method('searchArtifactId')->willReturn($exists);

        $url->method('getArtifactDao')->willReturn($dao);
        self::assertNull($url->getGroupIdFromURL('/tracker/download.php?artifact_id=dontexist'));
    }

    public function testArtifactExist(): void
    {
        $url    = $this->createPartialMock(\URL::class, [
            'getArtifactDao',
        ]);
        $dao    = $this->createMock(\ArtifactDao::class);
        $exists = $this->createMock(\DataAccessResult::class);
        $exists->method('getRow')->willReturnOnConsecutiveCalls(['group_id' => '1'], false);

        $exists1 = $this->createMock(\DataAccessResult::class);
        $exists1->method('getRow')->willReturnOnConsecutiveCalls(['group_id' => '1'], false);

        $dao->method('searchArtifactId')->willReturnOnConsecutiveCalls($exists, $exists1);
        $_REQUEST['artifact_id'] = 1;
        $url->method('getArtifactDao')->willReturn($dao);
        self::assertEquals(1, $url->getGroupIdFromURL('/tracker/download.php?artifact_id=exist'));
        self::assertNotEquals(1, $url->getGroupIdFromURL('/toto/tracker/download.php?artifact_id=exist'));
    }
}
