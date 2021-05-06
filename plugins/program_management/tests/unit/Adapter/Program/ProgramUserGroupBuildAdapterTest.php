<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\ProgramManagement\Domain\Program\ProgramForManagement;
use Tuleap\ProgramManagement\Domain\Program\ProgramUserGroupDoesNotExistException;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Project\REST\UserGroupRetriever;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProgramUserGroupBuildAdapterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UserGroupRetriever
     */
    private $user_group_retriever;
    /**
     * @var ProgramUserGroupBuildAdapter
     */
    private $build_program_user_group;

    protected function setUp(): void
    {
        $this->user_group_retriever     = \Mockery::mock(UserGroupRetriever::class);
        $this->build_program_user_group = new ProgramUserGroupBuildAdapter($this->user_group_retriever);
    }

    public function testBuildProgramUserGroups(): void
    {
        $program = ProgramForManagement::fromId(BuildProgramStub::stubValidProgramForManagement(), 102, UserTestBuilder::aUser()->build());

        $this->user_group_retriever->shouldReceive('getExistingUserGroup')->with('102_3')->andReturn(
            new \ProjectUGroup(['ugroup_id' => 3, 'group_id' => 102])
        );
        $this->user_group_retriever->shouldReceive('getExistingUserGroup')->with('102_4')->andReturn(
            new \ProjectUGroup(['ugroup_id' => 4, 'group_id' => 102])
        );

        $program_user_groups = $this->build_program_user_group->buildProgramUserGroups($program, ['102_3', '102_4']);

        self::assertEquals(3, $program_user_groups[0]->getId());
        self::assertEquals(4, $program_user_groups[1]->getId());
    }

    public function testRejectsUGroupOutsideOfTheProgram(): void
    {
        $program = ProgramForManagement::fromId(BuildProgramStub::stubValidProgramForManagement(), 102, UserTestBuilder::aUser()->build());

        $this->user_group_retriever->shouldReceive('getExistingUserGroup')->with('103_3')->andReturn(
            new \ProjectUGroup(['ugroup_id' => 3, 'group_id' => 103])
        );

        $this->expectException(ProgramUserGroupDoesNotExistException::class);
        $this->build_program_user_group->buildProgramUserGroups($program, ['103_3']);
    }

    public function testRejectIfUgroupIsNotInProgram(): void
    {
        $program = ProgramForManagement::fromId(BuildProgramStub::stubValidProgramForManagement(), 102, UserTestBuilder::aUser()->build());
        $this->user_group_retriever->shouldReceive('getExistingUserGroup')->with('123_3')->andReturn(
            new \ProjectUGroup(['ugroup_id' => 3, 'group_id' => 123])
        );
        $this->expectException(ProgramUserGroupDoesNotExistException::class);
        $this->build_program_user_group->getProjectUserGroupId('123_3', $program);
    }
}
