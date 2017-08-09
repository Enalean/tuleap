<?php
/**
 * Copyright (c) STMicroelectronics 2011. All rights reserved
 * Copyright (c) Enalean 2017. All rights reserved
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

class SVNAccessFileTest extends TuleapTestCase
{

    public function testisGroupDefinedInvalidSyntax()
    {
        $saf    = new SVNAccessFile();
        $groups = array();
        $this->assertFalse($saf->isGroupDefined($groups, 'uGroup1 = rw'));
        $this->assertFalse($saf->isGroupDefined($groups, '@uGroup1  rw'));
        $this->assertFalse($saf->isGroupDefined($groups, '@uGroup1'));
        $this->assertFalse($saf->isGroupDefined($groups, '@ uGroup1 = rw'));
        $this->assertFalse($saf->isGroupDefined($groups, '@@uGroup1 = rw'));
    }

    public function testisGroupDefinedNoUGroup()
    {
        $groups = array();
        $saf    = new SVNAccessFile();
        $this->assertFalse($saf->isGroupDefined($groups, '@uGroup3 = rw'));
    }

    public function testisGroupDefined()
    {
        $groups = array('ugroup2' => true, 'a' => true);
        $saf    = new SVNAccessFile();
        $this->assertTrue($saf->isGroupDefined($groups, '@ugroup2=rw'));
        $this->assertFalse($saf->isGroupDefined($groups, '@uGroup2=rw'));
        $this->assertFalse($saf->isGroupDefined($groups, '@uGroup3 = rw'));
        $this->assertTrue($saf->isGroupDefined($groups, '@a=rw'));
    }

    public function testValidateUGroupLine()
    {
        $saf = partial_mock('SVNAccessFile', array('isGroupDefined'));
        $saf->setReturnValue('isGroupDefined', true);
        $groups = array('uGroup1' => false, 'uGroup2' => false, 'uGroup3' => true, 'uGroup33' => true);
        $this->assertEqual(' uGroup1 = rw', $saf->validateUGroupLine($groups, ' uGroup1 = rw', null));
        $this->assertEqual(' @uGroup11 = rw', $saf->validateUGroupLine($groups, ' @uGroup11 = rw', null));
        $this->assertEqual(' @@uGroup1 = rw', $saf->validateUGroupLine($groups, ' @@uGroup1 = rw', null));
        $this->assertEqual('# @uGroup1 = rw', $saf->validateUGroupLine($groups, '# @uGroup1 = rw', null));

        $this->assertEqual('@uGroup3 = rw', $saf->validateUGroupLine($groups, '@uGroup3 = rw', null));
        $this->assertEqual('@uGroup33 = rw', $saf->validateUGroupLine($groups, '@uGroup33 = rw', null));
        $this->assertEqual('@uGroup33	= rw', $saf->validateUGroupLine($groups, '@uGroup33	= rw', null));
    }

    public function testRenameGroup()
    {
        $groups = array('ugroup1' => SVNAccessFile::UGROUP_DEFAULT, 'ugroup2' => SVNAccessFile::UGROUP_DEFAULT, 'ugroup3' => SVNAccessFile::UGROUP_REDEFINED);
        $saf    = new SVNAccessFile();
        $saf->setRenamedGroup('ugroup11', 'ugroup1');
        $this->assertEqual('@ugroup11 = rw', $saf->renameGroup($groups, '@ugroup1 = rw'));
        $this->assertEqual('@ugroup2 = rw', $saf->renameGroup($groups, '@ugroup2 = rw'));

        $saf->setRenamedGroup('ugroup33', 'ugroup3');
        $this->assertEqual('@ugroup3 = rw', $saf->renameGroup($groups, '@ugroup3 = rw'));
        $this->assertEqual('@ugroup2 = rw', $saf->renameGroup($groups, '@ugroup2 = rw'));
    }

    public function testCommentInvalidLine()
    {
        $groups = array('ugroup1' => SVNAccessFile::UGROUP_DEFAULT, 'ugroup2' => SVNAccessFile::UGROUP_DEFAULT, 'ugroup3' => SVNAccessFile::UGROUP_REDEFINED);
        $saf    = new SVNAccessFile();
        $this->assertEqual('@ugroup1 = rw', $saf->commentInvalidLine($groups, '@ugroup1 = rw'));
        $this->assertEqual('# @ugroup2', $saf->commentInvalidLine($groups, '@ugroup2'));
    }

    public function testParseGroupLines()
    {
        $project = aMockProject()->build();

        $saf = partial_mock('SVNAccessFile', array('getPlatformBlock'));
        $saf->setReturnValue('getPlatformBlock', "[groups]\nmembers = user1, user2\nuGroup1 = user3\n\n[/]\n*=\n@members=rw\n");

        $this->assertEqual("[/]\n@members=rw\n# @group1 = r", $saf->parseGroupLines($project, "[/]\n@members=rw\n@group1 = r"));
        $this->assertEqual("[/]\n@members=rw\n# @group1 = r\n[Groups]\ngroup1=user1, user2\n[/trunk]\n@group1=r\nuser1=rw", $saf->parseGroupLines($project, "[/]\n@members=rw\n@group1 = r\n[Groups]\ngroup1=user1, user2\n[/trunk]\n@group1=r\nuser1=rw"));
        $this->assertEqual("[/]\n@members=rw\n# @group1 = r\n[Groups]\ngroup1=user1, user2\n[groups]\ngroup2=user3\n[/trunk]\n@group1=r\nuser1=rw\n@group2=rw", $saf->parseGroupLines($project, "[/]\n@members=rw\n@group1 = r\n[Groups]\ngroup1=user1, user2\n[groups]\ngroup2=user3\n[/trunk]\n@group1=r\nuser1=rw\n@group2=rw"));
    }

    public function testAccumulateDefinedGroupsFromDeFaultGroupsSection()
    {
        $saf = new SVNAccessFile();
        $this->assertEqual(array(), $saf->accumulateDefinedGroups(array(), '', true));

        $this->assertEqual(array('group1' => SVNAccessFile::UGROUP_DEFAULT), $saf->accumulateDefinedGroups(array(), 'group1 = user1, user2', true));

        $this->assertEqual(array('group1' => SVNAccessFile::UGROUP_DEFAULT), $saf->accumulateDefinedGroups(array('group1' => SVNAccessFile::UGROUP_DEFAULT), 'group1 = user11, user22', true));

        $this->assertEqual(array('group1' => SVNAccessFile::UGROUP_REDEFINED, 'group2' => SVNAccessFile::UGROUP_DEFAULT), $saf->accumulateDefinedGroups(array('group1' => SVNAccessFile::UGROUP_REDEFINED), 'group2 = user11, user22', true));
    }

    public function testAccumulateDefinedGroups()
    {
        $saf = new SVNAccessFile();
        $this->assertEqual(array(), $saf->accumulateDefinedGroups(array(), ''));

        $this->assertEqual(array(), $saf->accumulateDefinedGroups(array(), 'blah'));

        $this->assertEqual(array(), $saf->accumulateDefinedGroups(array(), '[Groups]'));

        $this->assertEqual(array(), $saf->accumulateDefinedGroups(array(), '[/]'));

        $this->assertEqual(array('group1' => SVNAccessFile::UGROUP_REDEFINED), $saf->accumulateDefinedGroups(array(), 'group1 = user1, user2', false));
        $this->assertNotEqual(array('group1' => SVNAccessFile::UGROUP_REDEFINED), $saf->accumulateDefinedGroups(array(), 'Group1 = user1, user2', false));

        $this->assertEqual(array('group1' => SVNAccessFile::UGROUP_REDEFINED), $saf->accumulateDefinedGroups(array('group1' => SVNAccessFile::UGROUP_DEFAULT), 'group1 = user1, user2', false));
        $this->assertNotEqual(array('group1' => SVNAccessFile::UGROUP_REDEFINED), $saf->accumulateDefinedGroups(array('group1' => SVNAccessFile::UGROUP_DEFAULT), 'Group1 = user1, user2', false));

        $this->assertEqual(array('group1' => SVNAccessFile::UGROUP_DEFAULT, 'group2' => SVNAccessFile::UGROUP_REDEFINED), $saf->accumulateDefinedGroups(array('group1' => SVNAccessFile::UGROUP_DEFAULT), 'group2 = user1, user2', false));
        $this->assertNotEqual(array('group1' => SVNAccessFile::UGROUP_DEFAULT, 'group2' => SVNAccessFile::UGROUP_REDEFINED), $saf->accumulateDefinedGroups(array('group1' => SVNAccessFile::UGROUP_DEFAULT), 'Group2 = user1, user2', false));
    }

    public function testGetCurrentSection()
    {
        $saf = new SVNAccessFile();
        $this->assertEqual(-1, $saf->getCurrentSection('', -1));
        $this->assertEqual(-1, $saf->getCurrentSection('blah', -1));
        $this->assertEqual('groups', $saf->getCurrentSection('[Groups]', -1));
        $this->assertEqual('groups', $saf->getCurrentSection('[Groups]', 'groups'));
        $this->assertEqual(-1, $saf->getCurrentSection('[/]', -1));
        $this->assertEqual(-1, $saf->getCurrentSection('[/]', 'groups'));
        $this->assertEqual('groups', $saf->getCurrentSection('Group1 = user1, user2', 'groups'));
        $this->assertEqual(-1, $saf->getCurrentSection('Group1 = user1, user2', -1));
    }

    public function testSvnAccessFileShouldCallsvn_utils_read_svn_access_file_defaultsWithCaseSensitiveRepositoryName()
    {
        $project = aMockProject()->build();
        stub($project)->getSVNRootPath()->returns('/svnroot/mytestproject');

        $saf = partial_mock('SVNAccessFile', array('getPlatformBlock'));
        expect($saf)->getPlatformBlock('/svnroot/mytestproject')->once();

        $saf->parseGroupLines($project, '');
    }
}
