<?php
/**
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

require_once('common/project/Project.class.php');
Mock::generate('Project');
require_once('common/dao/UGroupDao.class.php');
Mock::generate('UGroupDao');
require_once('common/project/UGroup.class.php');
Mock::generate('UGroup');
require_once('common/svn/SVNAccessFile.class.php');
Mock::generatePartial('SVNAccessFile', 'SVNAccessFileTestVersion', array('_getUGroupDao', '_getUGroupFromRow'));
Mock::generatePartial('SVNAccessFile', 'SVNAccessFileTestVersion2', array('isValidUGroupLine'));

class SVNAccessFileTest extends UnitTestCase {

    function testIsValidUGroupLineInvalidSyntax() {
        $saf = new SVNAccessFile();
        $project = new MockProject();
        $this->assertFalse($saf->isValidUGroupLine($project, 'uGroup1 = rw'));
        $this->assertFalse($saf->isValidUGroupLine($project, '@uGroup1  rw'));
        $this->assertFalse($saf->isValidUGroupLine($project, '@uGroup1'));
        $this->assertFalse($saf->isValidUGroupLine($project, '@ uGroup1 = rw'));
        $this->assertFalse($saf->isValidUGroupLine($project, '@@uGroup1 = rw'));
    }

    function testIsValidUGroupLineNoUGroup() {
        $ugroups = array(1, 2);
        $ugdao = new MockUGroupDao();
        $ugdao->setReturnValue('searchByGroupId',$ugroups);

        $ugroup1 = new MockUGroup();
        $ugroup1->setReturnValue('getMembers',array(1));
        $ugroup1->setReturnValue('getName',"uGroup1");
        $ugroup2 = new MockUGroup();
        $ugroup2->setReturnValue('getMembers',array(2));
        $ugroup2->setReturnValue('getName',"uGroup2");

        $project = new MockProject();

        $saf = new SVNAccessFileTestVersion();
        $saf->setReturnValueAt(0, '_getUGroupFromRow', $ugroup1);
        $saf->setReturnValueAt(1, '_getUGroupFromRow', $ugroup2);
        $saf->setReturnValue('_getUGroupDao', $ugdao);
        $this->assertFalse($saf->isValidUGroupLine($project, '@uGroup3 = rw'));
    }

    function testIsValidUGroupLineEmptyUGroup() {
        $ugroups = array(1, 2);
        $ugdao = new MockUGroupDao();
        $ugdao->setReturnValue('searchByGroupId',$ugroups);

        $ugroup1 = new MockUGroup();
        $ugroup1->setReturnValue('getMembers',array(1));
        $ugroup1->setReturnValue('getName',"uGroup1");
        $ugroup2 = new MockUGroup();
        $ugroup2->setReturnValue('getName',"uGroup2");
        $ugroup2->setReturnValue('getMembers',array());

        $project = new MockProject();

        $saf = new SVNAccessFileTestVersion();
        $saf->setReturnValueAt(0, '_getUGroupFromRow', $ugroup1);
        $saf->setReturnValueAt(1, '_getUGroupFromRow', $ugroup2);
        $saf->setReturnValue('_getUGroupDao', $ugdao);
        $this->assertFalse($saf->isValidUGroupLine($project, '@uGroup2 = rw'));
    }

    function testIsValidUGroupLineMembers() {
        $ugroups = array(1, 2);
        $ugdao = new MockUGroupDao();
        $ugdao->setReturnValue('searchByGroupId',$ugroups);

        $ugroup1 = new MockUGroup();
        $ugroup1->setReturnValue('getMembers',array(1));
        $ugroup1->setReturnValue('getName',"uGroup1");
        $ugroup2 = new MockUGroup();
        $ugroup2->setReturnValue('getMembers',array(2));
        $ugroup2->setReturnValue('getName',"uGroup2");

        $project = new MockProject();

        $saf = new SVNAccessFileTestVersion();
        $saf->setReturnValueAt(0, '_getUGroupFromRow', $ugroup1);
        $saf->setReturnValueAt(1, '_getUGroupFromRow', $ugroup2);
        $saf->setReturnValue('_getUGroupDao', $ugdao);
        $this->assertTrue($saf->isValidUGroupLine($project, '@members = rw'));
    }

    function testIsValidUGroupLine() {
        $ugroups = array(1, 2);
        $ugdao = new MockUGroupDao();
        $ugdao->setReturnValue('searchByGroupId',$ugroups);

        $ugroup1 = new MockUGroup();
        $ugroup1->setReturnValue('getMembers',array());
        $ugroup1->setReturnValue('getName',"uGroup1");
        $ugroup2 = new MockUGroup();
        $ugroup2->setReturnValue('getName',"uGroup2");
        $ugroup2->setReturnValue('getMembers',array(2));

        $project = new MockProject();

        $saf = new SVNAccessFileTestVersion();
        $saf->setReturnValueAt(0, '_getUGroupFromRow', $ugroup1);
        $saf->setReturnValueAt(1, '_getUGroupFromRow', $ugroup2);
        $saf->setReturnValue('_getUGroupDao', $ugdao);
        $this->assertTrue($saf->isValidUGroupLine($project, '@uGroup2=rw'));
    }

    function testIsValidUGroupLineOneCharacter() {
        $ugroups = array(1, 2);
        $ugdao = new MockUGroupDao();
        $ugdao->setReturnValue('searchByGroupId',$ugroups);

        $ugroup1 = new MockUGroup();
        $ugroup1->setReturnValue('getMembers',array());
        $ugroup1->setReturnValue('getName',"uGroup1");
        $ugroup2 = new MockUGroup();
        $ugroup2->setReturnValue('getName',"a");
        $ugroup2->setReturnValue('getMembers',array(2));

        $project = new MockProject();

        $saf = new SVNAccessFileTestVersion();
        $saf->setReturnValueAt(0, '_getUGroupFromRow', $ugroup1);
        $saf->setReturnValueAt(1, '_getUGroupFromRow', $ugroup2);
        $saf->setReturnValue('_getUGroupDao', $ugdao);
        $this->assertTrue($saf->isValidUGroupLine($project, '@a=rw'));
    }

    function testValidateUGroupLine() {
        $saf = new SVNAccessFileTestVersion2();
        $saf->setReturnValue('isValidUGroupLine', true);
        $project = new MockProject();
        $this->assertEqual(' uGroup1 = rw', $saf->validateUGroupLine($project, ' uGroup1 = rw', null, 'uGroup2', 'uGroup1'));
        $this->assertEqual(' @uGroup11 = rw', $saf->validateUGroupLine($project, ' @uGroup11 = rw', null, 'uGroup2', 'uGroup1'));
        $this->assertEqual(' @@uGroup1 = rw', $saf->validateUGroupLine($project, ' @@uGroup1 = rw', null, 'uGroup2', 'uGroup1'));
        $this->assertEqual('# @uGroup1 = rw', $saf->validateUGroupLine($project, '# @uGroup1 = rw', null, 'uGroup2', 'uGroup1'));

        $this->assertEqual('@uGroup2 = rw', $saf->validateUGroupLine($project, '@uGroup1 = rw', null, 'uGroup2', 'uGroup1'));
        $this->assertEqual(' @uGroup2 = rw', $saf->validateUGroupLine($project, ' @uGroup1 = rw', null, 'uGroup2', 'uGroup1'));
    }

}

?>