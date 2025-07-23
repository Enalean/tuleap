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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Team\MirroredTimebox;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\IterationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\SearchMirroredTimeboxesStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MirroredIterationIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_MIRROR_ID  = 18;
    private const SECOND_MIRROR_ID = 28;

    private const ITERATION_ID = 8;

    private UserIdentifierStub $user;
    private IterationIdentifier $iteration;

    #[\Override]
    protected function setUp(): void
    {
        $this->user      =  UserIdentifierStub::buildGenericUser();
        $this->iteration = IterationIdentifierBuilder::buildWithId(self::ITERATION_ID);
    }

    public function testItBuildAnEmptyArrayWhenThereIsNoIterationsAtAll(): void
    {
        $mirrored_timeboxes_searcher  = SearchMirroredTimeboxesStub::withNoMirrors();
        $artifact_visibility_verifier = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();

        $mirrored_iterations =  MirroredIterationIdentifier::buildCollectionFromIteration(
            $mirrored_timeboxes_searcher,
            $artifact_visibility_verifier,
            $this->iteration,
            $this->user
        );

        self::assertEmpty($mirrored_iterations);
    }

    public function testItReturnsOnlyTheVisibleMirroredIteration(): void
    {
        $mirrored_timeboxes_searcher  = SearchMirroredTimeboxesStub::withIds(self::FIRST_MIRROR_ID, self::SECOND_MIRROR_ID);
        $artifact_visibility_verifier = VerifyIsVisibleArtifactStub::withVisibleIds(self::SECOND_MIRROR_ID);

        $mirrored_iterations     =  MirroredIterationIdentifier::buildCollectionFromIteration(
            $mirrored_timeboxes_searcher,
            $artifact_visibility_verifier,
            $this->iteration,
            $this->user
        );
        $mirrored_iterations_ids = array_map(
            static fn(MirroredIterationIdentifier $identifier) => $identifier->getId(),
            $mirrored_iterations
        );

        self::assertNotContains(self::FIRST_MIRROR_ID, $mirrored_iterations_ids);
        self::assertContains(self::SECOND_MIRROR_ID, $mirrored_iterations_ids);
    }
}
