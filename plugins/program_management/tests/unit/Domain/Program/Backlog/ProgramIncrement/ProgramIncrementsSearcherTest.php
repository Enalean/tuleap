<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchProgramIncrementsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramIncrementsSearcherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_ID                  = 12;
    private const FIRST_PROGRAM_INCREMENT_ID  = 18;
    private const SECOND_PROGRAM_INCREMENT_ID = 83;
    private UserIdentifierStub $user;
    private ProgramIncrement $first_program_increment;
    private ProgramIncrement $second_program_increment;
    private VerifyIsVisibleArtifactStub $visibility_verifier;
    private RetrieveProgramIncrementStub $program_increment_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->user                     = UserIdentifierStub::buildGenericUser();
        $this->first_program_increment  = ProgramIncrementBuilder::buildWithId(self::FIRST_PROGRAM_INCREMENT_ID);
        $this->second_program_increment = ProgramIncrementBuilder::buildWithId(self::SECOND_PROGRAM_INCREMENT_ID);

        $this->visibility_verifier         = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();
        $this->program_increment_retriever = RetrieveProgramIncrementStub::withSuccessiveProgramIncrements(
            $this->first_program_increment,
            $this->second_program_increment
        );
    }

    private function getSearcher(): ProgramIncrementsSearcher
    {
        return new ProgramIncrementsSearcher(
            BuildProgramStub::stubValidProgram(),
            SearchProgramIncrementsOfProgramStub::withIds(
                self::FIRST_PROGRAM_INCREMENT_ID,
                self::SECOND_PROGRAM_INCREMENT_ID
            ),
            VerifyIsProgramIncrementStub::withValidProgramIncrement(),
            $this->visibility_verifier,
            $this->program_increment_retriever,
        );
    }

    public function testSearchReturnsOpenProgramIncrements(): void
    {
        $program_increments = $this->getSearcher()->searchOpenProgramIncrements(self::PROGRAM_ID, $this->user);
        self::assertCount(2, $program_increments);
        self::assertContains($this->first_program_increment, $program_increments);
        self::assertContains($this->second_program_increment, $program_increments);
    }

    public function testSearchDoesNotReturnArtifactsTheUserCannotRead(): void
    {
        $this->visibility_verifier = VerifyIsVisibleArtifactStub::withNoVisibleArtifact();

        self::assertEmpty(
            $this->getSearcher()->searchOpenProgramIncrements(self::PROGRAM_ID, $this->user)
        );
    }

    public function testSearchDoesNotReturnArtifactsWhereTheUserCannotReadTheTitle(): void
    {
        $this->program_increment_retriever = RetrieveProgramIncrementStub::withNoVisibleProgramIncrement();

        self::assertEmpty(
            $this->getSearcher()->searchOpenProgramIncrements(self::PROGRAM_ID, $this->user)
        );
    }
}
