<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/project/UGroupManager.class.php';

class UGroupManager_BaseTest extends TuleapTestCase {

    public function setUp() {
        $this->non_existent_ugroup_id = 102;
        $this->integrators_ugroup_id  = 103;

        $this->project        = stub('Project')->getID()->returns(123);
        $dao = mock('UGroupDao');

        $ugroup_definitions = array(
            array('ugroup_id' => "1",   'name' => "ugroup_anonymous_users_name_key",    'description' => "ugroup_anonymous_users_desc_key",     'group_id' => "100"),
            array('ugroup_id' => "2",   'name' => "ugroup_registered_users_name_key",   'description' => "ugroup_registered_users_desc_key",    'group_id' => "100"),
            array('ugroup_id' => "3",   'name' => "ugroup_project_members_name_key",    'description' => "ugroup_project_members_desc_key",     'group_id' => "100"),
            array('ugroup_id' => "4",   'name' => "ugroup_project_admins_name_key",     'description' => "ugroup_project_admins_desc_key",      'group_id' => "100"),
            array('ugroup_id' => "11",  'name' => "ugroup_file_manager_admin_name_key", 'description' => "ugroup_file_manager_admin_desc_key",  'group_id' => "100"),
            array('ugroup_id' => "12",  'name' => "ugroup_document_tech_name_key",      'description' => "ugroup_document_tech_desc_key",       'group_id' => "100"),
            array('ugroup_id' => "13",  'name' => "ugroup_document_admin_name_key",     'description' => "ugroup_document_admin_desc_key",      'group_id' => "100"),
            array('ugroup_id' => "14",  'name' => "ugroup_wiki_admin_name_key",         'description' => "ugroup_wiki_admin_desc_key",          'group_id' => "100"),
            array('ugroup_id' => "15",  'name' => "ugroup_tracker_admins_name_key",     'description' => "ugroup_tracker_admins_desc_key",      'group_id' => "100"),
            array('ugroup_id' => "100", 'name' => "ugroup_nobody_name_key",             'description' => "ugroup_nobody_desc_key",              'group_id' => "100"),
            array('ugroup_id' => "103", 'name' => "Integrators",                        'description' => "",                                    'group_id' => "123"),
            array('ugroup_id' => "103", 'name' => "ugroup_supra_name_key",              'description' => "",                                    'group_id' => "123"),
        );
        foreach ($ugroup_definitions as $def) {
            stub($dao)->searchByGroupIdAndUGroupId((int)$def['group_id'], (int)$def['ugroup_id'])->returnsDar($def);
            stub($dao)->searchByGroupIdAndName((int)$def['group_id'], $def['name'])->returnsDar($def);
        }
        stub($dao)->searchByGroupIdAndUGroupId()->returnsEmptyDar();
        stub($dao)->searchByGroupIdAndName()->returnsEmptyDar();
        stub($dao)->searchDynamicAndStaticByGroupId(123)->returns(TestHelper::argListToDar($ugroup_definitions));

        $this->ugroup_manager = new UGroupManager($dao);
    }
}

class UGroupManager_getUGroup_Test extends UGroupManager_BaseTest {
    public function itReturnsNullIfNoMatch() {
        $ugroup = $this->ugroup_manager->getUGroup($this->project, $this->non_existent_ugroup_id);
        $this->assertNull($ugroup);
    }

    public function itReturnsStaticUgroupForAGivenProject() {
        $ugroup = $this->ugroup_manager->getUGroup($this->project, $this->integrators_ugroup_id);
        $this->assertEqual('Integrators', $ugroup->getName());
    }

    public function itReturnsDynamicUgroupForAGivenProject() {
        $ugroup = $this->ugroup_manager->getUGroup($this->project, UGroup::PROJECT_MEMBERS);
        $this->assertEqual('ugroup_project_members_name_key', $ugroup->getName());
    }
}

class UGroupManager_getUGroups_Test extends UGroupManager_BaseTest {

    public function itReturnsAllUgroupsOfAProject() {
        $ugroups = $this->ugroup_manager->getUGroups($this->project);
        $this->assertCount($ugroups, 12);
    }

    public function itExcludesGivenUgroups() {
        $ugroups = $this->ugroup_manager->getUGroups($this->project, array(UGROUP::NONE, UGROUP::ANONYMOUS));
        $this->assertCount($ugroups, 10);
    }
}

class UGroupManager_getUGroupByName_Test extends UGroupManager_BaseTest {

    public function itReturnsAStaticUGroupOfAProject() {
        $ugroup = $this->ugroup_manager->getUGroupByName($this->project, 'Integrators');
        $this->assertEqual($ugroup->getName(), 'Integrators');
    }

    public function itReturnsASpecialNamedStaticUGroupOfAProject() {
        $ugroup = $this->ugroup_manager->getUGroupByName($this->project, 'ugroup_supra_name_key');
        $this->assertEqual($ugroup->getName(), 'ugroup_supra_name_key');
    }

    public function itReturnsADynamicUGroupOfAProject() {
        $ugroup = $this->ugroup_manager->getUGroupByName($this->project, 'ugroup_project_members_name_key');
        $this->assertEqual($ugroup->getName(), 'ugroup_project_members_name_key');
    }

    public function itReturnsNullIfNoDynamicMatch() {
        $this->assertNull($this->ugroup_manager->getUGroupByName($this->project, 'ugroup_BLA_name_key'));
    }

    public function itReturnsNullIfNoStaticMatch() {
        $this->assertNull($this->ugroup_manager->getUGroupByName($this->project, 'BLA'));
    }
}
?>
