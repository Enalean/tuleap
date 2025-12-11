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

    #[\Override]
    protected function tearDown(): void
    {
        unset($_REQUEST['artifact_id']);
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
            'getProjectNameRule',
            'getProjectDao',
        ]);

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
        $dao->expects($this->once())->method('searchByUnixGroupName')->with('test.svn')->willReturn($exists);

        $url->method('getProjectDao')->willReturn($dao);
        self::assertEquals(1, $url->getGroupIdFromURL('/viewvc.php/?roottype=svn&root=test.svn'));
    }
}
