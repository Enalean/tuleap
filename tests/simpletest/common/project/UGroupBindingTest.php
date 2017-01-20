<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

require_once 'common/project/UGroupBinding.class.php';

Mock::generate('UGroupBinding');
Mock::generatePartial('UGroupBinding', 'UGroupBindingTestVersion', array('getUGroupsByBindingSource', 'getUGroupManager', 'getUGroupUserDao'));
Mock::generate('ProjectUGroup');
Mock::generate('UGroupUserDao');
Mock::generate('UGroupManager');
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once('common/include/Response.class.php');
Mock::generate('Response');

class UGroupBindingTest extends TuleapTestCase {
    private $ugroup_id = 200;
    private $source_id = 300;
    private $ugroupManager;
    private $ugroupUserDao;
    private $ugroupBinding;

    public function setUp() {
        parent::setUp();
        $GLOBALS['Response'] = new MockResponse();
        $this->ugroupManager = new MockUGroupManager();
        $this->ugroupUserDao = new MockUGroupUserDao();
        $this->ugroupBinding = new UGroupBinding($this->ugroupUserDao, $this->ugroupManager);
    }

    function testRemoveUgroupBinding() {
        $this->ugroupManager->setReturnValue('updateUgroupBinding', true);
        $GLOBALS['Language']->expectOnce('getText', array('project_ugroup_binding','binding_removed'));
        $GLOBALS['Response']->expectOnce('addFeedback');
        $this->assertTrue($this->ugroupBinding->removeBinding($this->ugroup_id));
    }

    function testResetUgroupFailureUpdateUGroupNotAllowed() {
        $this->ugroupManager->setReturnValue('isUpdateUsersAllowed', false);
        $this->expectException(new RuntimeException());
        $this->ugroupBinding->resetUgroup($this->ugroup_id);
    }

    function testResetUgroupDaoFailure() {
        $this->ugroupManager->setReturnValue('isUpdateUsersAllowed', true);
        $this->ugroupUserDao->setReturnValue('resetUgroupUserList', false);
        $this->expectException(new LogicException());
        $this->ugroupBinding->resetUgroup($this->ugroup_id);
    }

    function testCloneUgroupFailureUpdateUGroupNotAllowed() {
        $this->ugroupManager->setReturnValue('isUpdateUsersAllowed', false);
        $this->expectException(new RuntimeException());
        $this->ugroupBinding->cloneUgroup($this->source_id, $this->ugroup_id);
    }

    function testCloneUgroupDaoFailure() {
        $this->ugroupManager->setReturnValue('isUpdateUsersAllowed', true);
        $this->ugroupUserDao->setReturnValue('cloneUgroup', false);
        $this->expectException(new LogicException());
        $this->ugroupBinding->cloneUgroup($this->source_id, $this->ugroup_id);
    }

    function testUpdateUgroupBindingFailure() {
        $this->ugroupManager->setReturnValue('updateUgroupBinding', false);
        $this->expectException(new Exception('Unable to store ugroup binding'));
        $this->ugroupBinding->updateUgroupBinding($this->ugroup_id, $this->source_id);
    }

    function testRemoveAllUGroupsBinding() {
        $bindedUgroups = array(300, 400, 500, 600);
        $ugroupBinding = new UGroupBindingTestVersion();
        $ugroupBinding->setReturnValue('getUGroupsByBindingSource', $bindedUgroups);
        $ugroupBinding->setReturnValue('getUGroupManager', $this->ugroupManager);

        $this->ugroupManager->expectCallCount('updateUgroupBinding', 4);
        $this->ugroupManager->setReturnValueAt(0, 'updateUgroupBinding', true);
        $this->ugroupManager->setReturnValueAt(2, 'updateUgroupBinding', false);
        $this->assertFalse($ugroupBinding->removeAllUGroupsBinding($this->ugroup_id));
    }
}
