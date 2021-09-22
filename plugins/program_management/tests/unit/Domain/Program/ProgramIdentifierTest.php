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

namespace Tuleap\ProgramManagement\Domain\Program;

use Tuleap\ProgramManagement\Adapter\Permissions\WorkflowUserPermissionBypass;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementHasNoProgramException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ReplicationDataBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramOfProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\PHPUnit\TestCase;

final class ProgramIdentifierTest extends TestCase
{
    private const PROGRAM_ID = 101;
    private BuildProgramStub $program_builder;
    private UserIdentifierStub $user;
    private ProgramIncrementIdentifier $program_increment;

    protected function setUp(): void
    {
        $this->program_builder   = BuildProgramStub::stubValidProgram();
        $this->user              = UserIdentifierStub::buildGenericUser();
        $this->program_increment = ProgramIncrementIdentifierBuilder::buildWithId(785);
    }

    public function testItBuildsFromAProjectId(): void
    {
        $program = ProgramIdentifier::fromId($this->program_builder, self::PROGRAM_ID, $this->user, null);
        self::assertSame(self::PROGRAM_ID, $program->getId());
    }

    public function testItBuildsWithBypass(): void
    {
        $program = ProgramIdentifier::fromId(
            $this->program_builder,
            self::PROGRAM_ID,
            $this->user,
            new WorkflowUserPermissionBypass()
        );
        self::assertSame(self::PROGRAM_ID, $program->getId());
    }

    public function testItBuildsFromReplicationData(): void
    {
        $replication_data = ReplicationDataBuilder::buildWithProjectId(self::PROGRAM_ID);
        $program          = ProgramIdentifier::fromReplicationData($replication_data);
        self::assertSame(self::PROGRAM_ID, $program->getId());
    }

    public function testItBuildsFromAProgramIncrement(): void
    {
        $program = ProgramIdentifier::fromProgramIncrement(
            RetrieveProgramOfProgramIncrementStub::withProgram(self::PROGRAM_ID),
            $this->program_builder,
            $this->program_increment,
            $this->user
        );
        self::assertSame(self::PROGRAM_ID, $program->getId());
    }

    public function testItThrowsWhenProgramIncrementHasNoProgram(): void
    {
        $this->expectException(ProgramIncrementHasNoProgramException::class);
        ProgramIdentifier::fromProgramIncrement(
            RetrieveProgramOfProgramIncrementStub::withNoProgram(),
            $this->program_builder,
            $this->program_increment,
            $this->user
        );
    }
}
