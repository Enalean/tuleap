<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\ArtifactLinks;

use Tuleap\ProgramManagement\Tests\Stub\ArtifactIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\ForbidArtifactMoveActionStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchLinkedArtifactsStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsIterationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;

class ArtifactMoveConditionCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ARTIFACT_ID = 105;

    public function testItDoesNotForbidArtifactMoveWhenTheArtifactIsNotAnProgramEntityNorAMirror(): void
    {
        $artifact_move = ForbidArtifactMoveActionStub::build();

        (new ArtifactMoveConditionChecker(
            SearchLinkedArtifactsStub::withoutMirroredMilestones(),
            VerifyIsProgramIncrementStub::withNotProgramIncrement(),
            VerifyIsIterationStub::withNotIteration(),
            $artifact_move,
        ))->checkArtifactCanBeMoved(
            ArtifactIdentifierStub::withId(self::ARTIFACT_ID),
            [106, 107]
        );

        self::assertFalse($artifact_move->isIsMoveForbidden());
    }

    public function testItForbidsArtifactMoveWhenTheArtifactIsAMirror(): void
    {
        $artifact_move = ForbidArtifactMoveActionStub::build();

        (new ArtifactMoveConditionChecker(
            SearchLinkedArtifactsStub::withMirroredMilestones(),
            VerifyIsProgramIncrementStub::withNotProgramIncrement(),
            VerifyIsIterationStub::withNotIteration(),
            $artifact_move,
        ))->checkArtifactCanBeMoved(
            ArtifactIdentifierStub::withId(self::ARTIFACT_ID),
            [106, 107]
        );

        self::assertTrue($artifact_move->isIsMoveForbidden());
    }

    public function testItForbidsArtifactMoveWhenTheArtifactIsAProgramIncrement(): void
    {
        $artifact_move = ForbidArtifactMoveActionStub::build();

        (new ArtifactMoveConditionChecker(
            SearchLinkedArtifactsStub::withoutMirroredMilestones(),
            VerifyIsProgramIncrementStub::withValidProgramIncrement(),
            VerifyIsIterationStub::withNotIteration(),
            $artifact_move,
        ))->checkArtifactCanBeMoved(
            ArtifactIdentifierStub::withId(self::ARTIFACT_ID),
            [106, 107]
        );

        self::assertTrue($artifact_move->isIsMoveForbidden());
    }

    public function testItForbidsArtifactMoveWhenTheArtifactIsAProgramIteration(): void
    {
        $artifact_move = ForbidArtifactMoveActionStub::build();

        (new ArtifactMoveConditionChecker(
            SearchLinkedArtifactsStub::withoutMirroredMilestones(),
            VerifyIsProgramIncrementStub::withValidProgramIncrement(),
            VerifyIsIterationStub::withNotIteration(),
            $artifact_move,
        ))->checkArtifactCanBeMoved(
            ArtifactIdentifierStub::withId(self::ARTIFACT_ID),
            [106, 107]
        );

        self::assertTrue($artifact_move->isIsMoveForbidden());
    }
}
