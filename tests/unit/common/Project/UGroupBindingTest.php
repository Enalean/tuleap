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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use UGroupBinding;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UGroupBindingTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use GlobalResponseMock;

    private UGroupBinding $binding;
    private \UGroupUserDao&MockObject $ugroup_user_dao;
    private \UGroupManager&MockObject $ugroup_manager;

    protected function setUp(): void
    {
        $this->ugroup_user_dao = $this->createMock(\UGroupUserDao::class);
        $this->ugroup_manager  = $this->createMock(\UGroupManager::class);
        $this->binding         = new UGroupBinding($this->ugroup_user_dao, $this->ugroup_manager);
    }

    public function testRemoveUgroupBinding(): void
    {
        $this->ugroup_manager->expects($this->once())->method('updateUgroupBinding');
        $GLOBALS['Language']->expects($this->once())->method('getText')
            ->with('project_ugroup_binding', 'binding_removed');
        $GLOBALS['Response']->expects($this->once())->method('addFeedback');

        self::assertTrue($this->binding->removeBinding(200));
    }

    public function testUpdateUGroupBinding(): void
    {
        $this->ugroup_manager->expects($this->once())->method('updateUgroupBinding');

        $this->binding->updateUgroupBinding(200, 300);
    }

    public function testRemoveAllUGroupsBindingReturnsTrueWhenNoBoundUGroups(): void
    {
        $this->binding = $this->createPartialMock(UGroupBinding::class, [
            'getUGroupsByBindingSource',
        ]);
        $this->binding->method('getUGroupsByBindingSource')
            ->willReturn([]);

        $this->ugroup_manager->expects($this->never())->method('updateUgroupBinding');

        self::assertTrue($this->binding->removeAllUGroupsBinding(200));
    }

    public function testGetUGroupsByBindingSourceReturnsAnArrayOfProjectIdsAndUGroupNames(): void
    {
        $first_row  = [
            'ugroup_id' => 300,
            'name'      => 'panicmongering',
            'group_id'  => 138,
        ];
        $second_row = [
            'ugroup_id' => 400,
            'name'      => 'counteraverment',
            'group_id'  => 185,
        ];

        $this->ugroup_manager->expects($this->once())->method('searchUGroupByBindingSource')
            ->with(200)
            ->willReturn([$first_row, $second_row]);

        $result = $this->binding->getUGroupsByBindingSource(200);

        $bound_ugroup_ids = array_keys($result);
        self::assertEquals([300, 400], $bound_ugroup_ids);
        $first_bound_ugroup = [
            'cloneName' => 'panicmongering',
            'group_id'  => 138,
        ];
        self::assertEquals($first_bound_ugroup, $result[300]);
        $second_bound_ugroup = [
            'cloneName' => 'counteraverment',
            'group_id'  => 185,
        ];
        self::assertEquals($second_bound_ugroup, $result[400]);
    }

    public function testCheckUGroupValidityDelegates(): void
    {
        $this->ugroup_manager->expects($this->once())->method('checkUGroupValidityByGroupId')
            ->with(105, 200)
            ->willReturn(true);

        self::assertTrue($this->binding->checkUGroupValidity(105, 200));
    }

    public function testReloadUGroupBindingInProject(): void
    {
        $first_bound_ugroup  = ['ugroup_id' => 400, 'source_id' => 200];
        $second_bound_ugroup = ['ugroup_id' => 500, 'source_id' => 200];

        $project = ProjectTestBuilder::aProject()->build();
        $this->ugroup_manager->expects($this->once())->method('searchBindedUgroupsInProject')
            ->with($project)
            ->willReturn([$first_bound_ugroup, $second_bound_ugroup]);
        $this->binding = $this->getMockBuilder(UGroupBinding::class)
            ->setConstructorArgs([$this->ugroup_user_dao, $this->ugroup_manager])
            ->onlyMethods(['reloadUgroupBinding'])
            ->getMock();
        $matcher       = self::exactly(2);
        $this->binding->expects($matcher)->method('reloadUgroupBinding')->willReturnCallback(function (...$parameters) use ($matcher) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame(400, $parameters[0]);
                self::assertSame(200, $parameters[1]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame(500, $parameters[0]);
                self::assertSame(200, $parameters[1]);
            }
        });

        $this->binding->reloadUgroupBindingInProject($project);
    }

    public function testUpdateBindedUGroups(): void
    {
        $bound_ugroups = [300 => [], 400 => [], 500 => [], 600 => []];
        $this->binding = $this->createPartialMock(UGroupBinding::class, [
            'getUGroupsByBindingSource',
            'reloadUgroupBinding',
        ]);
        $this->binding->expects($this->once())->method('getUGroupsByBindingSource')
            ->with(200)
            ->willReturn($bound_ugroups);

        $this->binding->method('reloadUgroupBinding');

        self::assertTrue($this->binding->updateBindedUGroups(200));
    }

    public function testUpdateBindedUGroupsReturnsFalseWhenExceptionDuringReload(): void
    {
        $this->binding = $this->createPartialMock(UGroupBinding::class, [
            'getUGroupsByBindingSource',
            'reloadUgroupBinding',
        ]);
        $this->binding->method('getUGroupsByBindingSource')
            ->willReturn([300 => [], 400 => []]);

        $this->binding->expects($this->once())->method('reloadUgroupBinding')
            ->with(300, 200)
            ->willThrowException(new \Exception());

        self::assertFalse($this->binding->updateBindedUGroups(200));
    }

    public function testUpdateBindedUGroupsReturnsTrueWhenNoBoundUGroups(): void
    {
        $this->binding = $this->createPartialMock(UGroupBinding::class, [
            'getUGroupsByBindingSource',
            'reloadUgroupBinding',
        ]);
        $this->binding->method('getUGroupsByBindingSource')->willReturn([]);

        $this->binding->expects($this->never())->method('reloadUgroupBinding');

        self::assertTrue($this->binding->updateBindedUGroups(200));
    }
}
