<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectUGroup;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use UGroupManager;

final class UGroupRetrieverWithLegacyTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private UGroupManager&MockObject $ugroup_manager;
    private UGroupRetrieverWithLegacy $ugroup_retriever;
    private Project $project;
    private ProjectUGroup&MockObject $ugroup;

    protected function setUp(): void
    {
        $this->project          = ProjectTestBuilder::aProject()->build();
        $this->ugroup           = $this->createMock(ProjectUGroup::class);
        $this->ugroup_manager   = $this->createMock(UGroupManager::class);
        $this->ugroup_retriever = new UGroupRetrieverWithLegacy($this->ugroup_manager);
    }

    public function testGetUGroupIdReturnLegacyUgroupId(): void
    {
        self::assertEquals(100, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_NONE'));
        self::assertEquals(1, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_ANONYMOUS'));
        self::assertEquals(2, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_REGISTERED'));
        self::assertEquals(5, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_AUTHENTICATED'));
        self::assertEquals(3, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_PROJECT_MEMBERS'));
        self::assertEquals(4, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_PROJECT_ADMIN'));
        self::assertEquals(11, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_FILE_MANAGER_ADMIN'));
        self::assertEquals(14, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_WIKI_ADMIN'));
        self::assertEquals(15, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_TRACKER_ADMIN'));
    }

    public function testGetUgroupReturnProjectUgroup(): void
    {
        $this->ugroup->method('getId')->willReturn(102);
        $this->ugroup_manager->method('getUGroupByName')->willReturn($this->ugroup);

        self::assertEquals(102, $this->ugroup_retriever->getUGroupId($this->project, 'ugroup_project'));
    }

    public function testGetUgroupReturnNullIfGroupDoesntExist(): void
    {
        $this->ugroup->expects(self::never())->method('getId');
        $this->ugroup_manager->method('getUGroupByName')->willReturn(null);

        self::assertNull($this->ugroup_retriever->getUGroupId($this->project, 'ugroup_project'));
    }

    public function testGetProjectUGroups(): void
    {
        $project_group = ProjectUGroupTestBuilder::aCustomUserGroup(42)
            ->withName('legroup')
            ->build();

        $this->ugroup_manager->method('getStaticUGroups')->willReturn([$project_group]);

        $ugroups = [
            'UGROUP_NONE'               => ProjectUGroup::NONE,
            'UGROUP_ANONYMOUS'          => ProjectUGroup::ANONYMOUS,
            'UGROUP_REGISTERED'         => ProjectUGroup::REGISTERED,
            'UGROUP_AUTHENTICATED'      => ProjectUGroup::AUTHENTICATED,
            'UGROUP_PROJECT_MEMBERS'    => ProjectUGroup::PROJECT_MEMBERS,
            'UGROUP_PROJECT_ADMIN'      => ProjectUGroup::PROJECT_ADMIN,
            'UGROUP_FILE_MANAGER_ADMIN' => ProjectUGroup::FILE_MANAGER_ADMIN,
            'UGROUP_WIKI_ADMIN'         => ProjectUGroup::WIKI_ADMIN,
            'UGROUP_TRACKER_ADMIN'      => ProjectUGroup::TRACKER_ADMIN,
        ];

        $ugroups['legroup'] = 42;

        self::assertSame($ugroups, $this->ugroup_retriever->getProjectUgroupIds($this->project));
    }
}
