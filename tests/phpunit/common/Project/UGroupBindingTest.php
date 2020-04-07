<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use UGroupBinding;

final class UGroupBindingTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use GlobalResponseMock;

    /**
     * @var UGroupBinding
     */
    private $binding;
    /**
     * @var Mockery\MockInterface|\UGroupUserDao
     */
    private $ugroup_user_dao;
    /**
     * @var Mockery\MockInterface|\UGroupManager
     */
    private $ugroup_manager;

    protected function setUp(): void
    {
        $this->ugroup_user_dao = Mockery::mock(\UGroupUserDao::class);
        $this->ugroup_manager  = Mockery::mock(\UGroupManager::class);
        $this->binding         = new UGroupBinding($this->ugroup_user_dao, $this->ugroup_manager);
    }

    public function testRemoveUgroupBinding(): void
    {
        $this->ugroup_manager->shouldReceive('updateUgroupBinding')
            ->once()
            ->andReturnTrue();
        $GLOBALS['Language']->shouldReceive('getText')
            ->with('project_ugroup_binding', 'binding_removed')
            ->once();
        $GLOBALS['Response']->shouldReceive('addFeedback')->once();

        $this->assertTrue($this->binding->removeBinding(200));
    }

    public function testUpdateUGroupBinding(): void
    {
        $this->ugroup_manager->shouldReceive('updateUgroupBinding')
            ->once()
            ->andReturnTrue();

        $this->binding->updateUgroupBinding(200, 300);
    }

    public function testRemoveAllUGroupsBindingReturnsFalseWhenNotAllUGroupsCouldBeUpdated(): void
    {
        $bound_ugroups = [300 => [], 400 => [], 500 => [], 600 => []];
        $this->binding = Mockery::mock(UGroupBinding::class)->makePartial();
        $this->binding->shouldReceive('getUGroupsByBindingSource')
            ->with(200)
            ->once()
            ->andReturn($bound_ugroups);
        $this->binding->shouldReceive('getUGroupManager')
            ->andReturn($this->ugroup_manager);

        $this->ugroup_manager->shouldReceive('updateUgroupBinding')
            ->times(4)
            ->andReturn(true, true, false, false);

        $this->assertFalse($this->binding->removeAllUGroupsBinding(200));
    }

    public function testRemoveAllUGroupsBindingReturnsTrueWhenNoBoundUGroups(): void
    {
        $this->binding = Mockery::mock(UGroupBinding::class)->makePartial();
        $this->binding->shouldReceive('getUGroupsByBindingSource')
            ->andReturn([]);

        $this->ugroup_manager->shouldNotReceive('updateUgroupBinding');

        $this->assertTrue($this->binding->removeAllUGroupsBinding(200));
    }

    public function testGetUGroupsByBindingSourceReturnsAnEmptyArrayWhenDARisError(): void
    {
        $dar = Mockery::spy(LegacyDataAccessResultInterface::class);
        $this->ugroup_manager->shouldReceive('searchUGroupByBindingSource')
            ->once()
            ->with(200)
            ->andReturn($dar);
        $dar->shouldReceive('isError')->once()->andReturnTrue();

        $this->assertEmpty($this->binding->getUGroupsByBindingSource(200));
    }

    public function testGetUGroupsByBindingSourceReturnsAnArrayOfProjectIdsAndUGroupNames(): void
    {
        $dar = Mockery::spy(LegacyDataAccessResultInterface::class);
        $this->ugroup_manager->shouldReceive('searchUGroupByBindingSource')
            ->once()
            ->with(200)
            ->andReturn($dar);

        $dar->shouldReceive('isError')->once()->andReturnFalse();
        $first_row  = [
            'ugroup_id' => 300,
            'name'      => 'panicmongering',
            'group_id'  => 138
        ];
        $second_row = [
            'ugroup_id' => 400,
            'name'      => 'counteraverment',
            'group_id'  => 185
        ];
        $dar->shouldReceive('valid')->andReturn(true, true, false);
        $dar->shouldReceive('current')->andReturn($first_row, $second_row);

        $result = $this->binding->getUGroupsByBindingSource(200);

        $bound_ugroup_ids = array_keys($result);
        $this->assertEquals([300, 400], $bound_ugroup_ids);
        $first_bound_ugroup = [
            'cloneName' => 'panicmongering',
            'group_id'  => 138
        ];
        $this->assertEquals($first_bound_ugroup, $result[300]);
        $second_bound_ugroup = [
            'cloneName' => 'counteraverment',
            'group_id'  => 185
        ];
        $this->assertEquals($second_bound_ugroup, $result[400]);
    }

    public function testCheckUGroupValidityDelegates(): void
    {
        $this->ugroup_manager->shouldReceive('checkUGroupValidityByGroupId')
            ->with(105, 200)
            ->once()
            ->andReturnTrue();

        $this->assertTrue($this->binding->checkUGroupValidity(105, 200));
    }

    public function testReloadUGroupBindingInProject(): void
    {
        $first_bound_ugroup  = ['ugroup_id' => 400, 'source_id' => 200];
        $second_bound_ugroup = ['ugroup_id' => 500, 'source_id' => 200];
        $dar                 = Mockery::spy(LegacyDataAccessResultInterface::class);
        $dar->shouldReceive('current')
            ->andReturn($first_bound_ugroup, $second_bound_ugroup);
        $dar->shouldReceive('valid')
            ->andReturn(true, true, false);

        $project = Mockery::mock(\Project::class);
        $this->ugroup_manager->shouldReceive('searchBindedUgroupsInProject')
            ->with($project)
            ->once()
            ->andReturn($dar);
        $this->binding = Mockery::mock(
            UGroupBinding::class,
            [$this->ugroup_user_dao, $this->ugroup_manager]
        )->makePartial();
        $this->binding->shouldReceive('reloadUgroupBinding')
            ->with(400, 200)
            ->once();
        $this->binding->shouldReceive('reloadUgroupBinding')
            ->with(500, 200)
            ->once();

        $this->binding->reloadUgroupBindingInProject($project);
    }

    public function testUpdateBindedUGroups(): void
    {
        $bound_ugroups = [300 => [], 400 => [], 500 => [], 600 => []];
        $this->binding = Mockery::mock(UGroupBinding::class)->makePartial();
        $this->binding->shouldReceive('getUGroupsByBindingSource')
            ->with(200)
            ->once()
            ->andReturn($bound_ugroups);

        $this->binding->shouldReceive('reloadUgroupBinding');

        $this->assertTrue($this->binding->updateBindedUGroups(200));
    }

    public function testUpdateBindedUGroupsReturnsFalseWhenExceptionDuringReload(): void
    {
        $this->binding = Mockery::mock(UGroupBinding::class)->makePartial();
        $this->binding->shouldReceive('getUGroupsByBindingSource')
            ->andReturn([300 => [], 400 => []]);

        $this->binding->shouldReceive('reloadUgroupBinding')
            ->with(300, 200)
            ->once()
            ->andThrow(\Exception::class);
        $this->binding->shouldNotReceive('reloadUgroupBinding')
            ->with(400, 200);

        $this->assertFalse($this->binding->updateBindedUGroups(200));
    }

    public function testUpdateBindedUGroupsReturnsTrueWhenNoBoundUGroups(): void
    {
        $this->binding = Mockery::mock(UGroupBinding::class)->makePartial();
        $this->binding->shouldReceive('getUGroupsByBindingSource')
            ->andReturn([]);

        $this->binding->shouldNotReceive('reloadUgroupBinding');

        $this->assertTrue($this->binding->updateBindedUGroups(200));
    }
}
