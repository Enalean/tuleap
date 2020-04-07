<?php
/**
 * Copyright (c) Enalean 2017 - Present. All rights reserved
 * Copyright (c) STMicroelectronics 2011. All rights reserved
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\GlobalSVNPollution;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class SVNAccessFileTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalSVNPollution;
    use GlobalResponseMock;
    use GlobalLanguageMock;

    public function testisGroupDefinedInvalidSyntax(): void
    {
        $saf    = new SVNAccessFile();
        $groups = array();
        $this->assertFalse($saf->isGroupDefined($groups, 'uGroup1 = rw'));
        $this->assertFalse($saf->isGroupDefined($groups, '@uGroup1  rw'));
        $this->assertFalse($saf->isGroupDefined($groups, '@uGroup1'));
        $this->assertFalse($saf->isGroupDefined($groups, '@ uGroup1 = rw'));
        $this->assertFalse($saf->isGroupDefined($groups, '@@uGroup1 = rw'));
    }

    public function testisGroupDefinedNoUGroup(): void
    {
        $groups = array();
        $saf    = new SVNAccessFile();
        $this->assertFalse($saf->isGroupDefined($groups, '@uGroup3 = rw'));
    }

    public function testisGroupDefined(): void
    {
        $groups = array('ugroup2' => true, 'a' => true);
        $saf    = new SVNAccessFile();
        $this->assertTrue($saf->isGroupDefined($groups, '@ugroup2=rw'));
        $this->assertFalse($saf->isGroupDefined($groups, '@uGroup2=rw'));
        $this->assertFalse($saf->isGroupDefined($groups, '@uGroup3 = rw'));
        $this->assertTrue($saf->isGroupDefined($groups, '@a=rw'));
    }

    public function testValidateUGroupLine(): void
    {
        $saf = \Mockery::mock(\SVNAccessFile::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $saf->shouldReceive('isGroupDefined')->andReturns(true);
        $groups = array('uGroup1' => false, 'uGroup2' => false, 'uGroup3' => true, 'uGroup33' => true);
        $this->assertEquals(' uGroup1 = rw', $saf->validateUGroupLine($groups, ' uGroup1 = rw', null));
        $this->assertEquals(' @uGroup11 = rw', $saf->validateUGroupLine($groups, ' @uGroup11 = rw', null));
        $this->assertEquals(' @@uGroup1 = rw', $saf->validateUGroupLine($groups, ' @@uGroup1 = rw', null));
        $this->assertEquals('# @uGroup1 = rw', $saf->validateUGroupLine($groups, '# @uGroup1 = rw', null));

        $this->assertEquals('@uGroup3 = rw', $saf->validateUGroupLine($groups, '@uGroup3 = rw', null));
        $this->assertEquals('@uGroup33 = rw', $saf->validateUGroupLine($groups, '@uGroup33 = rw', null));
        $this->assertEquals('@uGroup33	= rw', $saf->validateUGroupLine($groups, '@uGroup33	= rw', null));
    }

    public function testRenameGroup(): void
    {
        $groups = array('ugroup1' => SVNAccessFile::UGROUP_DEFAULT, 'ugroup2' => SVNAccessFile::UGROUP_DEFAULT, 'ugroup3' => SVNAccessFile::UGROUP_REDEFINED);
        $saf    = new SVNAccessFile();
        $saf->setRenamedGroup('ugroup11', 'ugroup1');
        $this->assertEquals('@ugroup11 = rw', $saf->renameGroup($groups, '@ugroup1 = rw'));
        $this->assertEquals('@ugroup2 = rw', $saf->renameGroup($groups, '@ugroup2 = rw'));

        $saf->setRenamedGroup('ugroup33', 'ugroup3');
        $this->assertEquals('@ugroup3 = rw', $saf->renameGroup($groups, '@ugroup3 = rw'));
        $this->assertEquals('@ugroup2 = rw', $saf->renameGroup($groups, '@ugroup2 = rw'));
    }

    public function testCommentInvalidLine(): void
    {
        $groups = array('ugroup1' => SVNAccessFile::UGROUP_DEFAULT, 'ugroup2' => SVNAccessFile::UGROUP_DEFAULT, 'ugroup3' => SVNAccessFile::UGROUP_REDEFINED);
        $saf    = new SVNAccessFile();
        $this->assertEquals('@ugroup1 = rw', $saf->commentInvalidLine($groups, '@ugroup1 = rw'));
        $this->assertEquals('# @ugroup2', $saf->commentInvalidLine($groups, '@ugroup2'));
    }

    public function testParseGroupLines(): void
    {
        $project = \Mockery::spy(\Project::class, ['getID' => false, 'getUnixName' => false, 'isPublic' => false]);

        $saf = \Mockery::mock(\SVNAccessFile::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $saf->shouldReceive('getPlatformBlock')->andReturns("[groups]\nmembers = user1, user2\nuGroup1 = user3\n\n[/]\n*=\n@members=rw\n");

        $this->assertEquals("[/]\n@members=rw\n# @group1 = r", $saf->parseGroupLines($project, "[/]\n@members=rw\n@group1 = r"));
        $this->assertEquals("[/]\n@members=rw\n# @group1 = r\n[Groups]\ngroup1=user1, user2\n[/trunk]\n@group1=r\nuser1=rw", $saf->parseGroupLines($project, "[/]\n@members=rw\n@group1 = r\n[Groups]\ngroup1=user1, user2\n[/trunk]\n@group1=r\nuser1=rw"));
        $this->assertEquals("[/]\n@members=rw\n# @group1 = r\n[Groups]\ngroup1=user1, user2\n[groups]\ngroup2=user3\n[/trunk]\n@group1=r\nuser1=rw\n@group2=rw", $saf->parseGroupLines($project, "[/]\n@members=rw\n@group1 = r\n[Groups]\ngroup1=user1, user2\n[groups]\ngroup2=user3\n[/trunk]\n@group1=r\nuser1=rw\n@group2=rw"));
    }

    public function testAccumulateDefinedGroupsFromDeFaultGroupsSection(): void
    {
        $saf = new SVNAccessFile();
        $this->assertEquals(array(), $saf->accumulateDefinedGroups(array(), '', true));

        $this->assertEquals(array('group1' => SVNAccessFile::UGROUP_DEFAULT), $saf->accumulateDefinedGroups(array(), 'group1 = user1, user2', true));

        $this->assertEquals(array('group1' => SVNAccessFile::UGROUP_DEFAULT), $saf->accumulateDefinedGroups(array('group1' => SVNAccessFile::UGROUP_DEFAULT), 'group1 = user11, user22', true));

        $this->assertEquals(array('group1' => SVNAccessFile::UGROUP_REDEFINED, 'group2' => SVNAccessFile::UGROUP_DEFAULT), $saf->accumulateDefinedGroups(array('group1' => SVNAccessFile::UGROUP_REDEFINED), 'group2 = user11, user22', true));
    }

    public function testAccumulateDefinedGroups(): void
    {
        $saf = new SVNAccessFile();
        $this->assertEquals(array(), $saf->accumulateDefinedGroups(array(), ''));

        $this->assertEquals(array(), $saf->accumulateDefinedGroups(array(), 'blah'));

        $this->assertEquals(array(), $saf->accumulateDefinedGroups(array(), '[Groups]'));

        $this->assertEquals(array(), $saf->accumulateDefinedGroups(array(), '[/]'));

        $this->assertEquals(array('group1' => SVNAccessFile::UGROUP_REDEFINED), $saf->accumulateDefinedGroups(array(), 'group1 = user1, user2', false));
        $this->assertNotEquals(array('group1' => SVNAccessFile::UGROUP_REDEFINED), $saf->accumulateDefinedGroups(array(), 'Group1 = user1, user2', false));

        $this->assertEquals(array('group1' => SVNAccessFile::UGROUP_REDEFINED), $saf->accumulateDefinedGroups(array('group1' => SVNAccessFile::UGROUP_DEFAULT), 'group1 = user1, user2', false));
        $this->assertNotEquals(array('group1' => SVNAccessFile::UGROUP_REDEFINED), $saf->accumulateDefinedGroups(array('group1' => SVNAccessFile::UGROUP_DEFAULT), 'Group1 = user1, user2', false));

        $this->assertEquals(array('group1' => SVNAccessFile::UGROUP_DEFAULT, 'group2' => SVNAccessFile::UGROUP_REDEFINED), $saf->accumulateDefinedGroups(array('group1' => SVNAccessFile::UGROUP_DEFAULT), 'group2 = user1, user2', false));
        $this->assertNotEquals(array('group1' => SVNAccessFile::UGROUP_DEFAULT, 'group2' => SVNAccessFile::UGROUP_REDEFINED), $saf->accumulateDefinedGroups(array('group1' => SVNAccessFile::UGROUP_DEFAULT), 'Group2 = user1, user2', false));
    }

    public function testGetCurrentSection(): void
    {
        $saf = new SVNAccessFile();
        $this->assertEquals(-1, $saf->getCurrentSection('', -1));
        $this->assertEquals(-1, $saf->getCurrentSection('blah', -1));
        $this->assertEquals('groups', $saf->getCurrentSection('[Groups]', -1));
        $this->assertEquals('groups', $saf->getCurrentSection('[Groups]', 'groups'));
        $this->assertEquals(-1, $saf->getCurrentSection('[/]', -1));
        $this->assertEquals(-1, $saf->getCurrentSection('[/]', 'groups'));
        $this->assertEquals('groups', $saf->getCurrentSection('Group1 = user1, user2', 'groups'));
        $this->assertEquals(-1, $saf->getCurrentSection('Group1 = user1, user2', -1));
    }

    public function testSvnAccessFileShouldCallSVNUtilsWithCaseSensitiveRepositoryName(): void
    {
        $project = \Mockery::spy(\Project::class, ['getID' => false, 'getUnixName' => false, 'isPublic' => false]);
        $project->shouldReceive('getSVNRootPath')->andReturns('/svnroot/mytestproject');

        $saf = \Mockery::mock(\SVNAccessFile::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $saf->shouldReceive('getPlatformBlock')->with('/svnroot/mytestproject')->once();

        $saf->parseGroupLines($project, '');
    }
}
