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

use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class IterationIdentifierCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_ITERATION_ID  = 388;
    private const SECOND_ITERATION_ID = 540;
    private SearchIterationsStub $iteration_searcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->iteration_searcher = SearchIterationsStub::withIterations([
            ['id' => self::FIRST_ITERATION_ID, 'changeset_id' => 1],
            ['id' => self::SECOND_ITERATION_ID, 'changeset_id' => 2],
        ]);
    }

    private function getCollection(): IterationIdentifierCollection
    {
        return IterationIdentifierCollection::fromProgramIncrement(
            $this->iteration_searcher,
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            ProgramIncrementIdentifierBuilder::buildWithId(72),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItBuildsCollectionFromProgramIncrement(): void
    {
        $iterations    = $this->getCollection()->getIterations();
        $iteration_ids = array_map(static fn(IterationIdentifier $iteration) => $iteration->getId(), $iterations);

        self::assertCount(2, $iteration_ids);
        self::assertContains(self::FIRST_ITERATION_ID, $iteration_ids);
        self::assertContains(self::SECOND_ITERATION_ID, $iteration_ids);
    }

    public function testItBuildsEmptyCollectionWhenNoIterationInProgramIncrement(): void
    {
        $this->iteration_searcher = SearchIterationsStub::withNoIteration();
        self::assertCount(0, $this->getCollection()->getIterations());
    }
}
