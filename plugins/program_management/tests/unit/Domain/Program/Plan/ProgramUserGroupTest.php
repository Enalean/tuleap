<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

use Luracast\Restler\RestException;
use Tuleap\ProgramManagement\Domain\Program\ProgramForManagement;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\Test\Builders\UserTestBuilder;
use function PHPUnit\Framework\assertTrue;

class ProgramUserGroupTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testRejectIfUgroupDoesNotExist(): void
    {
        $program = ProgramForManagement::fromId(BuildProgramStub::stubValidProgramForManagement(), 101, UserTestBuilder::aUser()->build());

        $this->expectException(RestException::class);
        ProgramUserGroup::buildProgramUserGroup($this->getStubBuildProgramUserGroup(false), "123", $program);
    }

    public function testItBuildAProgramUserGroup(): void
    {
        $program = ProgramForManagement::fromId(BuildProgramStub::stubValidProgramForManagement(), 101, UserTestBuilder::aUser()->build());

        $group = ProgramUserGroup::buildProgramUserGroup($this->getStubBuildProgramUserGroup(), "123", $program);
        self::assertEquals($program, $group->getProgram());
        self::assertEquals(123, $group->getId());
    }

    private function getStubBuildProgramUserGroup(bool $return_project = true): BuildProgramUserGroup
    {
        return new class ($return_project) implements BuildProgramUserGroup {
            /* @var bool */
            private $return_project;

            public function __construct(bool $return_project)
            {
                $this->return_project = $return_project;
            }

            public function buildProgramUserGroups(ProgramForManagement $program, array $raw_user_group_ids): array
            {
                throw new \Exception("Should not passed here");
            }

            public function getProjectUserGroupId(string $raw_user_group_id, ProgramForManagement $program): int
            {
                assertTrue($raw_user_group_id === "123");
                if (! $this->return_project) {
                    throw new RestException(404, "not found");
                }

                return 123;
            }
        };
    }
}
