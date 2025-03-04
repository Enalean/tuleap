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

namespace Tuleap\ProgramManagement\Domain\Team\MirroredTimebox;

use Tuleap\ProgramManagement\Tests\Builder\IterationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\IterationIdentifierCollectionBuilder;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirroredTimeboxesStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MirroredIterationIdentifierCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_MIRROR_ITERATION_ID  = 678;
    private const SECOND_MIRROR_ITERATION_ID = 55;
    private const THIRD_MIRROR_ITERATION_ID  = 808;
    private const FOURTH_MIRROR_ITERATION_ID = 249;
    private SearchMirroredTimeboxesStub $mirror_searcher;
    private \Closure $getId;

    protected function setUp(): void
    {
        $this->getId           = static fn(MirroredIterationIdentifier $identifier): int => $identifier->getId();
        $this->mirror_searcher = SearchMirroredTimeboxesStub::withIds(
            self::FIRST_MIRROR_ITERATION_ID,
            self::SECOND_MIRROR_ITERATION_ID
        );
    }

    private function getCollectionFromIteration(): MirroredIterationIdentifierCollection
    {
        return MirroredIterationIdentifierCollection::fromIteration(
            $this->mirror_searcher,
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            IterationIdentifierBuilder::buildWithId(770),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItBuildsACollectionFromIteration(): void
    {
        $mirrored_iterations = $this->getCollectionFromIteration()->getMirroredIterations();
        $ids                 = array_map($this->getId, $mirrored_iterations);
        self::assertCount(2, $ids);
        self::assertContains(self::FIRST_MIRROR_ITERATION_ID, $ids);
        self::assertContains(self::SECOND_MIRROR_ITERATION_ID, $ids);
    }

    public function testItBuildsEmptyCollectionWhenIterationHasNoMirrors(): void
    {
        $this->mirror_searcher = SearchMirroredTimeboxesStub::withNoMirrors();
        self::assertCount(0, $this->getCollectionFromIteration()->getMirroredIterations());
    }

    private function getCollectionFromIterationCollection(): MirroredIterationIdentifierCollection
    {
        return MirroredIterationIdentifierCollection::fromIterationCollection(
            $this->mirror_searcher,
            VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
            IterationIdentifierCollectionBuilder::buildWithIterations([
                ['id' => 516, 'chanegset_id' => 1],
                ['id' => 541, 'chanegset_id' => 2],
            ]),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItBuildsFlatCollectionOfMirrorsFromCollectionOfIterations(): void
    {
        $this->mirror_searcher = SearchMirroredTimeboxesStub::withSuccessiveIds([
            [self::FIRST_MIRROR_ITERATION_ID, self::SECOND_MIRROR_ITERATION_ID],
            [self::THIRD_MIRROR_ITERATION_ID, self::FOURTH_MIRROR_ITERATION_ID],
        ]);
        $mirrored_iterations   = $this->getCollectionFromIterationCollection()->getMirroredIterations();
        $ids                   = array_map($this->getId, $mirrored_iterations);
        self::assertCount(4, $ids);
        self::assertContains(self::FIRST_MIRROR_ITERATION_ID, $ids);
        self::assertContains(self::SECOND_MIRROR_ITERATION_ID, $ids);
        self::assertContains(self::THIRD_MIRROR_ITERATION_ID, $ids);
        self::assertContains(self::FOURTH_MIRROR_ITERATION_ID, $ids);
    }

    public function testItReturnsEmptyArrayWhenNoMirrorInAnyOfTheIterations(): void
    {
        $this->mirror_searcher = SearchMirroredTimeboxesStub::withNoMirrors();
        self::assertCount(0, $this->getCollectionFromIterationCollection()->getMirroredIterations());
    }
}
