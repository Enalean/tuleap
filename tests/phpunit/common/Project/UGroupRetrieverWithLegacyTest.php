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

namespace Tuleap\Project;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectUGroup;
use UGroupManager;

class UGroupRetrieverWithLegacyTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UGroupManager
     */
    private $ugroup_manager;

    /**
     * @var UGroupRetrieverWithLegacy
     */
    private $ugroup_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectUGroup
     */
    private $ugroup;

    protected function setUp(): void
    {
        $this->project          = Mockery::mock(Project::class);
        $this->ugroup           = Mockery::mock(ProjectUGroup::class);
        $this->ugroup_manager   = Mockery::mock(UGroupManager::class);
        $this->ugroup_retriever = new UGroupRetrieverWithLegacy($this->ugroup_manager);
    }

    public function testGetUGroupIdReturnLegacyUgroupId()
    {
        $this->assertEquals(100, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_NONE'));
        $this->assertEquals(1, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_ANONYMOUS'));
        $this->assertEquals(2, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_REGISTERED'));
        $this->assertEquals(5, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_AUTHENTICATED'));
        $this->assertEquals(3, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_PROJECT_MEMBERS'));
        $this->assertEquals(4, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_PROJECT_ADMIN'));
        $this->assertEquals(11, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_FILE_MANAGER_ADMIN'));
        $this->assertEquals(14, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_WIKI_ADMIN'));
        $this->assertEquals(15, $this->ugroup_retriever->getUGroupId($this->project, 'UGROUP_TRACKER_ADMIN'));
    }

    public function testGetUgroupReturnProjectUgroup()
    {
        $this->ugroup->shouldReceive('getId')->andReturn(102);
        $this->ugroup_manager->shouldReceive('getUGroupByName')->andReturn($this->ugroup);

        $this->assertEquals(102, $this->ugroup_retriever->getUGroupId($this->project, 'ugroup_project'));
    }

    public function testGetUgroupReturnNullIfGroupDoesntExist()
    {
        $this->ugroup->shouldNotReceive('getId');
        $this->ugroup_manager->shouldReceive('getUGroupByName')->andReturn(null);

        $this->assertNull($this->ugroup_retriever->getUGroupId($this->project, 'ugroup_project'));
    }

    public function testGetProjectUGroups()
    {
        $project_group = Mockery::mock(ProjectUGroup::class);
        $project_group->shouldReceive('getId')->andReturn(42);
        $project_group->shouldReceive('getName')->andReturn('legroup');

        $this->ugroup_manager->shouldReceive('getStaticUGroups')->andReturn([$project_group]);

        $ugroups = [
            'UGROUP_NONE'               => ProjectUGroup::NONE,
            'UGROUP_ANONYMOUS'          => ProjectUGroup::ANONYMOUS,
            'UGROUP_REGISTERED'         => ProjectUGroup::REGISTERED,
            'UGROUP_AUTHENTICATED'      => ProjectUGroup::AUTHENTICATED,
            'UGROUP_PROJECT_MEMBERS'    => ProjectUGroup::PROJECT_MEMBERS,
            'UGROUP_PROJECT_ADMIN'      => ProjectUGroup::PROJECT_ADMIN,
            'UGROUP_FILE_MANAGER_ADMIN' => ProjectUGroup::FILE_MANAGER_ADMIN,
            'UGROUP_WIKI_ADMIN'         => ProjectUGroup::WIKI_ADMIN,
            'UGROUP_TRACKER_ADMIN'      => ProjectUGroup::TRACKER_ADMIN
        ];

        $ugroups['legroup'] = 42;

        $this->assertSame($ugroups, $this->ugroup_retriever->getProjectUgroupIds($this->project));
    }
}
