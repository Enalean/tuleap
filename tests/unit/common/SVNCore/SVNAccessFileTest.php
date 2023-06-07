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
use Tuleap\NeverThrow\Fault;
use Tuleap\SVNCore\CollectionOfSVNAccessFileFaults;
use Tuleap\SVNCore\SVNAccessFile;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class SVNAccessFileTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testisGroupDefinedInvalidSyntax(): void
    {
        $saf    = new SVNAccessFile();
        $groups = [];
        self::assertInstanceOf(Fault::class, $saf->isGroupDefined($groups, 'uGroup1 = rw')->unwrapOr(null));
        self::assertInstanceOf(Fault::class, $saf->isGroupDefined($groups, '@uGroup1  rw')->unwrapOr(null));
        self::assertInstanceOf(Fault::class, $saf->isGroupDefined($groups, '@uGroup1')->unwrapOr(null));
        self::assertInstanceOf(Fault::class, $saf->isGroupDefined($groups, '@ uGroup1 = rw')->unwrapOr(null));
        self::assertInstanceOf(Fault::class, $saf->isGroupDefined($groups, '@@uGroup1 = rw')->unwrapOr(null));
    }

    public function testisGroupDefinedNoUGroup(): void
    {
        $groups = [];
        $saf    = new SVNAccessFile();
        self::assertInstanceOf(Fault::class, $saf->isGroupDefined($groups, '@uGroup3 = rw')->unwrapOr(null));
    }

    public function testisGroupDefined(): void
    {
        $groups = ['ugroup2' => true, 'a' => true];
        $saf    = new SVNAccessFile();
        self::assertNull($saf->isGroupDefined($groups, '@ugroup2=rw')->unwrapOr(null));
        self::assertInstanceOf(Fault::class, $saf->isGroupDefined($groups, '@uGroup2=rw')->unwrapOr(null));
        self::assertInstanceOf(Fault::class, $saf->isGroupDefined($groups, '@uGroup3 = rw')->unwrapOr(null));
        self::assertNull($saf->isGroupDefined($groups, '@a=rw')->unwrapOr(null));
    }

    public function testValidateUGroupLine(): void
    {
        $saf = \Mockery::mock(\Tuleap\SVNCore\SVNAccessFile::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $saf->shouldReceive('isGroupDefined')->andReturns(\Tuleap\Option\Option::nothing(Fault::class));
        $groups = ['uGroup1' => false, 'uGroup2' => false, 'uGroup3' => true, 'uGroup33' => true];
        $this->assertEquals(' uGroup1 = rw', $saf->validateUGroupLine($groups, ' uGroup1 = rw', new CollectionOfSVNAccessFileFaults()));
        $this->assertEquals(' @uGroup11 = rw', $saf->validateUGroupLine($groups, ' @uGroup11 = rw', new CollectionOfSVNAccessFileFaults()));
        $this->assertEquals(' @@uGroup1 = rw', $saf->validateUGroupLine($groups, ' @@uGroup1 = rw', new CollectionOfSVNAccessFileFaults()));
        $this->assertEquals('# @uGroup1 = rw', $saf->validateUGroupLine($groups, '# @uGroup1 = rw', new CollectionOfSVNAccessFileFaults()));

        $this->assertEquals('@uGroup3 = rw', $saf->validateUGroupLine($groups, '@uGroup3 = rw', new CollectionOfSVNAccessFileFaults()));
        $this->assertEquals('@uGroup33 = rw', $saf->validateUGroupLine($groups, '@uGroup33 = rw', new CollectionOfSVNAccessFileFaults()));
        $this->assertEquals('@uGroup33	= rw', $saf->validateUGroupLine($groups, '@uGroup33	= rw', new CollectionOfSVNAccessFileFaults()));
    }

    public function testRenameGroup(): void
    {
        $groups = ['ugroup1' => SVNAccessFile::UGROUP_DEFAULT, 'ugroup2' => SVNAccessFile::UGROUP_DEFAULT, 'ugroup3' => SVNAccessFile::UGROUP_REDEFINED];
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
        $groups = ['ugroup1' => SVNAccessFile::UGROUP_DEFAULT, 'ugroup2' => SVNAccessFile::UGROUP_DEFAULT, 'ugroup3' => SVNAccessFile::UGROUP_REDEFINED];
        $saf    = new SVNAccessFile();
        $this->assertEquals('@ugroup1 = rw', $saf->commentInvalidLine($groups, '@ugroup1 = rw', new CollectionOfSVNAccessFileFaults()));
        $this->assertEquals('# @ugroup2', $saf->commentInvalidLine($groups, '@ugroup2', new CollectionOfSVNAccessFileFaults()));
    }

    public function testParseGroupLines(): void
    {
        $project = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->build();

        $saf = new SVNAccessFile();
        $saf->setPlatformBlock("[groups]\nmembers = user1, user2\nuGroup1 = user3\n\n[/]\n*=\n@members=rw\n");

        $this->assertEquals("[/]\n@members=rw\n# @group1 = r", $saf->parseGroupLines($project, "[/]\n@members=rw\n@group1 = r")->contents);
        $this->assertEquals("[/]\n@members=rw\n# @group1 = r\n[Groups]\ngroup1=user1, user2\n[/trunk]\n@group1=r\nuser1=rw", $saf->parseGroupLines($project, "[/]\n@members=rw\n@group1 = r\n[Groups]\ngroup1=user1, user2\n[/trunk]\n@group1=r\nuser1=rw")->contents);
        $this->assertEquals("[/]\n@members=rw\n# @group1 = r\n[Groups]\ngroup1=user1, user2\n[groups]\ngroup2=user3\n[/trunk]\n@group1=r\nuser1=rw\n@group2=rw", $saf->parseGroupLines($project, "[/]\n@members=rw\n@group1 = r\n[Groups]\ngroup1=user1, user2\n[groups]\ngroup2=user3\n[/trunk]\n@group1=r\nuser1=rw\n@group2=rw")->contents);
    }

    public function testAccumulateDefinedGroupsFromDeFaultGroupsSection(): void
    {
        $saf = new SVNAccessFile();
        $this->assertEquals([], $saf->accumulateDefinedGroups([], '', true));

        $this->assertEquals(['group1' => SVNAccessFile::UGROUP_DEFAULT], $saf->accumulateDefinedGroups([], 'group1 = user1, user2', true));

        $this->assertEquals(['group1' => SVNAccessFile::UGROUP_DEFAULT], $saf->accumulateDefinedGroups(['group1' => SVNAccessFile::UGROUP_DEFAULT], 'group1 = user11, user22', true));

        $this->assertEquals(['group1' => SVNAccessFile::UGROUP_REDEFINED, 'group2' => SVNAccessFile::UGROUP_DEFAULT], $saf->accumulateDefinedGroups(['group1' => SVNAccessFile::UGROUP_REDEFINED], 'group2 = user11, user22', true));
    }

    public function testAccumulateDefinedGroups(): void
    {
        $saf = new SVNAccessFile();
        $this->assertEquals([], $saf->accumulateDefinedGroups([], ''));

        $this->assertEquals([], $saf->accumulateDefinedGroups([], 'blah'));

        $this->assertEquals([], $saf->accumulateDefinedGroups([], '[Groups]'));

        $this->assertEquals([], $saf->accumulateDefinedGroups([], '[/]'));

        $this->assertEquals(['group1' => SVNAccessFile::UGROUP_REDEFINED], $saf->accumulateDefinedGroups([], 'group1 = user1, user2', false));
        $this->assertNotEquals(['group1' => SVNAccessFile::UGROUP_REDEFINED], $saf->accumulateDefinedGroups([], 'Group1 = user1, user2', false));

        $this->assertEquals(['group1' => SVNAccessFile::UGROUP_REDEFINED], $saf->accumulateDefinedGroups(['group1' => SVNAccessFile::UGROUP_DEFAULT], 'group1 = user1, user2', false));
        $this->assertNotEquals(['group1' => SVNAccessFile::UGROUP_REDEFINED], $saf->accumulateDefinedGroups(['group1' => SVNAccessFile::UGROUP_DEFAULT], 'Group1 = user1, user2', false));

        $this->assertEquals(['group1' => SVNAccessFile::UGROUP_DEFAULT, 'group2' => SVNAccessFile::UGROUP_REDEFINED], $saf->accumulateDefinedGroups(['group1' => SVNAccessFile::UGROUP_DEFAULT], 'group2 = user1, user2', false));
        $this->assertNotEquals(['group1' => SVNAccessFile::UGROUP_DEFAULT, 'group2' => SVNAccessFile::UGROUP_REDEFINED], $saf->accumulateDefinedGroups(['group1' => SVNAccessFile::UGROUP_DEFAULT], 'Group2 = user1, user2', false));
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

        $saf = \Mockery::mock(\Tuleap\SVNCore\SVNAccessFile::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $saf->shouldReceive('getPlatformBlock')->with('/svnroot/mytestproject')->once();

        $saf->parseGroupLines($project, '');
    }
}
