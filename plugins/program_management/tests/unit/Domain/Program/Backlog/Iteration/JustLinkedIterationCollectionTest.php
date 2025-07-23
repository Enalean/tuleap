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
use Tuleap\ProgramManagement\Tests\Builder\IterationIdentifierCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationHasBeenLinkedBeforeStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class JustLinkedIterationCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_PREVIOUS_ITERATION_ID    = 465;
    private const SECOND_PREVIOUS_ITERATION_ID   = 730;
    private const FIRST_JUST_ADDED_ITERATION_ID  = 878;
    private const SECOND_JUST_ADDED_ITERATION_ID = 182;
    private VerifyIterationHasBeenLinkedBeforeStub $link_verifier;
    private ProgramIncrementIdentifier $program_increment;

    #[\Override]
    protected function setUp(): void
    {
        $this->program_increment = ProgramIncrementIdentifierBuilder::buildWithId(10);
        $this->link_verifier     = VerifyIterationHasBeenLinkedBeforeStub::withIterationIds(
            self::FIRST_PREVIOUS_ITERATION_ID,
            self::SECOND_PREVIOUS_ITERATION_ID
        );
    }

    private function getCollection(): JustLinkedIterationCollection
    {
        return JustLinkedIterationCollection::fromIterations(
            $this->link_verifier,
            $this->program_increment,
            IterationIdentifierCollectionBuilder::buildWithIterations([
                ['id' => self::FIRST_PREVIOUS_ITERATION_ID, 'changeset_id' => 1],
                ['id' => self::SECOND_PREVIOUS_ITERATION_ID, 'changeset_id' => 2],
                ['id' => self::FIRST_JUST_ADDED_ITERATION_ID, 'changeset_id' => 3],
                ['id' => self::SECOND_JUST_ADDED_ITERATION_ID, 'changeset_id' => 4],
            ])
        );
    }

    public function testItFiltersIterationsThatHaveBeenLinkedInPreviousChangesets(): void
    {
        $collection = $this->getCollection();
        $ids        = array_map(
            static fn(IterationIdentifier $iteration): int => $iteration->getId(),
            $collection->ids
        );
        self::assertNotContains(self::FIRST_PREVIOUS_ITERATION_ID, $ids);
        self::assertNotContains(self::SECOND_PREVIOUS_ITERATION_ID, $ids);
        self::assertContains(self::FIRST_JUST_ADDED_ITERATION_ID, $ids);
        self::assertContains(self::SECOND_JUST_ADDED_ITERATION_ID, $ids);
        self::assertFalse($collection->isEmpty());
        self::assertSame($this->program_increment, $collection->program_increment);
    }

    public function testItCanBuildAnEmptyCollection(): void
    {
        $this->link_verifier = VerifyIterationHasBeenLinkedBeforeStub::withIterationIds(
            self::FIRST_PREVIOUS_ITERATION_ID,
            self::SECOND_PREVIOUS_ITERATION_ID,
            self::FIRST_JUST_ADDED_ITERATION_ID,
            self::SECOND_JUST_ADDED_ITERATION_ID
        );

        $collection = $this->getCollection();
        self::assertTrue($collection->isEmpty());
        self::assertSame($this->program_increment, $collection->program_increment);
    }
}
