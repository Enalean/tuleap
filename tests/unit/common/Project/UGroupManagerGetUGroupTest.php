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

declare(strict_types=1);

namespace Tuleap\Project;

use ProjectUGroup;
use Tuleap\Test\Builders\ProjectTestBuilder;
use UGroupManager;

final class UGroupManagerGetUGroupTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private int $non_existent_ugroup_id;
    private int $integrators_ugroup_id;
    private \Project $project;
    private UGroupManager $ugroup_manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->non_existent_ugroup_id = 102;
        $this->integrators_ugroup_id  = 103;

        $this->project = ProjectTestBuilder::aProject()->withId(123)->build();
        $dao           = $this->createMock(\UGroupDao::class);

        $ugroup_definitions = [
            ['ugroup_id' => "1", 'name' => "ugroup_anonymous_users_name_key", 'description' => "ugroup_anonymous_users_desc_key", 'group_id' => "100"],
            ['ugroup_id' => "2", 'name' => "ugroup_registered_users_name_key", 'description' => "ugroup_registered_users_desc_key", 'group_id' => "100"],
            ['ugroup_id' => "3", 'name' => "ugroup_project_members_name_key", 'description' => "ugroup_project_members_desc_key", 'group_id' => "100"],
            ['ugroup_id' => "4", 'name' => "ugroup_project_admins_name_key", 'description' => "ugroup_project_admins_desc_key", 'group_id' => "100"],
            ['ugroup_id' => "11", 'name' => "ugroup_file_manager_admin_name_key", 'description' => "ugroup_file_manager_admin_desc_key", 'group_id' => "100"],
            ['ugroup_id' => "12", 'name' => "ugroup_document_tech_name_key", 'description' => "ugroup_document_tech_desc_key", 'group_id' => "100"],
            ['ugroup_id' => "13", 'name' => "ugroup_document_admin_name_key", 'description' => "ugroup_document_admin_desc_key", 'group_id' => "100"],
            ['ugroup_id' => "14", 'name' => "ugroup_wiki_admin_name_key", 'description' => "ugroup_wiki_admin_desc_key", 'group_id' => "100"],
            ['ugroup_id' => "15", 'name' => "ugroup_tracker_admins_name_key", 'description' => "ugroup_tracker_admins_desc_key", 'group_id' => "100"],
            ['ugroup_id' => "100", 'name' => "ugroup_nobody_name_key", 'description' => "ugroup_nobody_desc_key", 'group_id' => "100"],
            ['ugroup_id' => "103", 'name' => "Integrators", 'description' => "", 'group_id' => "123"],
            ['ugroup_id' => "103", 'name' => "ugroup_supra_name_key", 'description' => "", 'group_id' => "123"],
        ];
        $dao->method('searchByGroupIdAndUGroupId')->willReturnCallback(function (string|int $group_id, int $ugroup_id) use ($ugroup_definitions) {
            foreach ($ugroup_definitions as $def) {
                if (
                    (int) $def['group_id'] === (int) $group_id &&
                    (int) $def['ugroup_id'] === $ugroup_id
                ) {
                    return \TestHelper::arrayToDar($def);
                }
            }

            return \TestHelper::emptyDar();
        });
        $dao->method('searchByGroupIdAndName')->willReturnCallback(function (string|int $group_id, string $name) use ($ugroup_definitions) {
            foreach ($ugroup_definitions as $def) {
                if (
                    (int) $def['group_id'] === (int) $group_id &&
                    $def['name'] === $name
                ) {
                    return \TestHelper::arrayToDar($def);
                }
            }

            return \TestHelper::emptyDar();
        });
        $dao->method('searchDynamicAndStaticByGroupId')->with(123)->willReturn(\TestHelper::argListToDar($ugroup_definitions));

        $this->ugroup_manager = new UGroupManager($dao);
    }

    public function testItReturnsNullIfNoMatch(): void
    {
        $ugroup = $this->ugroup_manager->getUGroup($this->project, $this->non_existent_ugroup_id);
        self::assertNull($ugroup);
    }

    public function testItReturnsStaticUgroupForAGivenProject(): void
    {
        $ugroup = $this->ugroup_manager->getUGroup($this->project, $this->integrators_ugroup_id);
        self::assertEquals('Integrators', $ugroup->getName());
    }

    public function testItReturnsDynamicUgroupForAGivenProject(): void
    {
        $ugroup = $this->ugroup_manager->getUGroup($this->project, ProjectUGroup::PROJECT_MEMBERS);
        self::assertEquals('ugroup_project_members_name_key', $ugroup->getName());
    }

    public function testItReturnsAllUgroupsOfAProject(): void
    {
        $ugroups = $this->ugroup_manager->getUGroups($this->project);
        self::assertCount(12, $ugroups);
    }

    public function testItExcludesGivenUgroups(): void
    {
        $ugroups = $this->ugroup_manager->getUGroups($this->project, [ProjectUGroup::NONE, ProjectUGroup::ANONYMOUS]);
        self::assertCount(10, $ugroups);
    }

    public function testItReturnsAStaticUGroupOfAProject(): void
    {
        $ugroup = $this->ugroup_manager->getUGroupByName($this->project, 'Integrators');
        self::assertEquals('Integrators', $ugroup->getName());
    }

    public function testItReturnsASpecialNamedStaticUGroupOfAProject(): void
    {
        $ugroup = $this->ugroup_manager->getUGroupByName($this->project, 'ugroup_supra_name_key');
        self::assertEquals('ugroup_supra_name_key', $ugroup->getName());
    }

    public function testItReturnsADynamicUGroupOfAProject(): void
    {
        $ugroup = $this->ugroup_manager->getUGroupByName($this->project, 'ugroup_project_members_name_key');
        self::assertEquals('ugroup_project_members_name_key', $ugroup->getName());
    }

    public function testItReturnsNullIfNoDynamicMatch(): void
    {
        self::assertNull($this->ugroup_manager->getUGroupByName($this->project, 'ugroup_BLA_name_key'));
    }

    public function testItReturnsNullIfNoStaticMatch(): void
    {
        self::assertNull($this->ugroup_manager->getUGroupByName($this->project, 'BLA'));
    }
}
