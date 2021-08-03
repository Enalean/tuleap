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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Stub\CheckProgramIncrementStub;
use Tuleap\ProgramManagement\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Stub\VerifyIterationHasBeenLinkedBeforeStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class JustLinkedIterationCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_PREVIOUS_ITERATION_ID    = 465;
    private const SECOND_PREVIOUS_ITERATION_ID   = 730;
    private const FIRST_JUST_ADDED_ITERATION_ID  = 878;
    private const SECOND_JUST_ADDED_ITERATION_ID = 182;
    private ProgramIncrementIdentifier $program_increment;
    /**
     * @var IterationIdentifier[]
     */
    private array $iterations;

    protected function setUp(): void
    {
        $user                    = UserTestBuilder::aUser()->build();
        $this->program_increment = ProgramIncrementIdentifier::fromId(
            CheckProgramIncrementStub::buildProgramIncrementChecker(),
            10,
            $user
        );
        $iterations_searcher     = SearchIterationsStub::withIterationIds(
            self::FIRST_PREVIOUS_ITERATION_ID,
            self::SECOND_PREVIOUS_ITERATION_ID,
            self::FIRST_JUST_ADDED_ITERATION_ID,
            self::SECOND_JUST_ADDED_ITERATION_ID
        );
        $visibility_verifier     = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();
        $this->iterations        = IterationIdentifier::buildCollectionFromProgramIncrement(
            $iterations_searcher,
            $visibility_verifier,
            $this->program_increment,
            UserIdentifier::fromPFUser($user)
        );
    }

    public function testItFiltersIterationsThatHaveBeenLinkedInPreviousChangesets(): void
    {
        $link_verifier = VerifyIterationHasBeenLinkedBeforeStub::withIterationIds(
            self::FIRST_PREVIOUS_ITERATION_ID,
            self::SECOND_PREVIOUS_ITERATION_ID
        );
        $collection    = JustLinkedIterationCollection::fromIterations(
            $link_verifier,
            $this->program_increment,
            ...$this->iterations
        );

        $ids = array_map(static fn(IterationIdentifier $iteration): int => $iteration->id, $collection->ids);
        self::assertNotContains(self::FIRST_PREVIOUS_ITERATION_ID, $ids);
        self::assertNotContains(self::SECOND_PREVIOUS_ITERATION_ID, $ids);
        self::assertContains(self::FIRST_JUST_ADDED_ITERATION_ID, $ids);
        self::assertContains(self::SECOND_JUST_ADDED_ITERATION_ID, $ids);
        self::assertFalse($collection->isEmpty());
        self::assertSame($this->program_increment, $collection->program_increment);
    }

    public function testItCanBuildAnEmptyCollection(): void
    {
        $link_verifier = VerifyIterationHasBeenLinkedBeforeStub::withIterationIds(
            self::FIRST_PREVIOUS_ITERATION_ID,
            self::SECOND_PREVIOUS_ITERATION_ID,
            self::FIRST_JUST_ADDED_ITERATION_ID,
            self::SECOND_JUST_ADDED_ITERATION_ID
        );
        $collection    = JustLinkedIterationCollection::fromIterations(
            $link_verifier,
            $this->program_increment,
            ...$this->iterations
        );

        self::assertTrue($collection->isEmpty());
        self::assertSame($this->program_increment, $collection->program_increment);
    }
}
