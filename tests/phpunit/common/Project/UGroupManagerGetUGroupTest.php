<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class UGroupManagerGetUGroupTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $this->non_existent_ugroup_id = 102;
        $this->integrators_ugroup_id  = 103;

        $this->project        = \Mockery::spy(\Project::class)->shouldReceive('getID')->andReturns(123)->getMock();
        $dao = \Mockery::spy(\UGroupDao::class);

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
            $dao->shouldReceive('searchByGroupIdAndUGroupId')->with((int) $def['group_id'], (int) $def['ugroup_id'])->andReturns(\TestHelper::arrayToDar($def));
            $dao->shouldReceive('searchByGroupIdAndName')->with((int) $def['group_id'], $def['name'])->andReturns(\TestHelper::arrayToDar($def));
        }
        $dao->shouldReceive('searchByGroupIdAndUGroupId')->andReturns(\TestHelper::emptyDar());
        $dao->shouldReceive('searchByGroupIdAndName')->andReturns(\TestHelper::emptyDar());
        $dao->shouldReceive('searchDynamicAndStaticByGroupId')->with(123)->andReturns(TestHelper::argListToDar($ugroup_definitions));

        $this->ugroup_manager = new UGroupManager($dao);
    }

    public function testItReturnsNullIfNoMatch(): void
    {
        $ugroup = $this->ugroup_manager->getUGroup($this->project, $this->non_existent_ugroup_id);
        $this->assertNull($ugroup);
    }

    public function testItReturnsStaticUgroupForAGivenProject(): void
    {
        $ugroup = $this->ugroup_manager->getUGroup($this->project, $this->integrators_ugroup_id);
        $this->assertEquals('Integrators', $ugroup->getName());
    }

    public function testItReturnsDynamicUgroupForAGivenProject(): void
    {
        $ugroup = $this->ugroup_manager->getUGroup($this->project, ProjectUGroup::PROJECT_MEMBERS);
        $this->assertEquals('ugroup_project_members_name_key', $ugroup->getName());
    }

    public function testItReturnsAllUgroupsOfAProject(): void
    {
        $ugroups = $this->ugroup_manager->getUGroups($this->project);
        $this->assertCount(12, $ugroups);
    }

    public function testItExcludesGivenUgroups(): void
    {
        $ugroups = $this->ugroup_manager->getUGroups($this->project, array(ProjectUGroup::NONE, ProjectUGroup::ANONYMOUS));
        $this->assertCount(10, $ugroups);
    }

    public function testItReturnsAStaticUGroupOfAProject(): void
    {
        $ugroup = $this->ugroup_manager->getUGroupByName($this->project, 'Integrators');
        $this->assertEquals('Integrators', $ugroup->getName());
    }

    public function testItReturnsASpecialNamedStaticUGroupOfAProject(): void
    {
        $ugroup = $this->ugroup_manager->getUGroupByName($this->project, 'ugroup_supra_name_key');
        $this->assertEquals('ugroup_supra_name_key', $ugroup->getName());
    }

    public function testItReturnsADynamicUGroupOfAProject(): void
    {
        $ugroup = $this->ugroup_manager->getUGroupByName($this->project, 'ugroup_project_members_name_key');
        $this->assertEquals('ugroup_project_members_name_key', $ugroup->getName());
    }

    public function testItReturnsNullIfNoDynamicMatch(): void
    {
        $this->assertNull($this->ugroup_manager->getUGroupByName($this->project, 'ugroup_BLA_name_key'));
    }

    public function testItReturnsNullIfNoStaticMatch(): void
    {
        $this->assertNull($this->ugroup_manager->getUGroupByName($this->project, 'BLA'));
    }
}
