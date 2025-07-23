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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationHasNoProgramException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementHasNoProgramException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\IterationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramOfIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramOfProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramIdentifierTest extends TestCase
{
    private const PROGRAM_ID = 101;
    private BuildProgramStub $program_builder;
    private UserIdentifier $user;
    private ProgramIncrementIdentifier $program_increment;
    private IterationIdentifier $iteration;

    #[\Override]
    protected function setUp(): void
    {
        $this->program_builder   = BuildProgramStub::stubValidProgram();
        $this->user              = UserIdentifierStub::buildGenericUser();
        $this->program_increment = ProgramIncrementIdentifierBuilder::buildWithId(785);
        $this->iteration         = IterationIdentifierBuilder::buildWithId(712);
    }

    public function testItBuildsFromAProjectId(): void
    {
        $program = ProgramIdentifier::fromId($this->program_builder, self::PROGRAM_ID, $this->user);
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

    public function testItBuildsFromAnIteration(): void
    {
        $program = ProgramIdentifier::fromIteration(
            RetrieveProgramOfIterationStub::withProgram(self::PROGRAM_ID),
            $this->program_builder,
            $this->iteration,
            $this->user
        );
        self::assertSame(self::PROGRAM_ID, $program->getId());
    }

    public function testItThrowsWhenIterationHasNoProgram(): void
    {
        $this->expectException(IterationHasNoProgramException::class);
        ProgramIdentifier::fromIteration(
            RetrieveProgramOfIterationStub::withNoProgram(),
            $this->program_builder,
            $this->iteration,
            $this->user
        );
    }

    public function testItBuildsWhenProgramServiceIsEnabled(): void
    {
        $identifier = ProgramIdentifier::fromServiceEnabled(new ProgramServiceIsEnabledCertificate(173));
        self::assertSame(173, $identifier->getId());
    }
}
