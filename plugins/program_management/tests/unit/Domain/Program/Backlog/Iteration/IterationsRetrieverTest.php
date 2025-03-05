<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration;

use Tuleap\ProgramManagement\Tests\Stub\RetrieveCrossRefStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveStatusValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTimeframeValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveTitleValueUserCanSeeStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUriStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyUserCanUpdateTimeboxStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class IterationsRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private IterationsRetriever $retriever;

    protected function setUp(): void
    {
        $verify_is_program_increment = VerifyIsProgramIncrementStub::withValidProgramIncrement();
        $verify_is_visible_artifact  = VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts();
        $search_iterations           = SearchIterationsStub::withIterations([['id' => 456, 'changeset_id' => 1]]);
        $tracker_factory             = $this->createStub(\Tracker_ArtifactFactory::class);
        $artifact                    = ArtifactTestBuilder::anArtifact(456)->build();

        $tracker_factory->method('getArtifactById')->willReturn($artifact);

        $this->retriever = new IterationsRetriever(
            $verify_is_program_increment,
            $verify_is_visible_artifact,
            $search_iterations,
            RetrieveStatusValueUserCanSeeStub::withValue('On going'),
            RetrieveTitleValueUserCanSeeStub::withValue('My iteration'),
            RetrieveTimeframeValueUserCanSeeStub::withValues(1633189968, 1635868368),
            RetrieveUriStub::withDefault(),
            RetrieveCrossRefStub::withDefault(),
            VerifyUserCanUpdateTimeboxStub::withAllowed()
        );
    }

    public function testItRetrievesIterations(): void
    {
        $iteration_list = $this->retriever->retrieveIterations(10, UserIdentifierStub::buildGenericUser());
        self::assertCount(1, $iteration_list);
        self::assertEquals(456, $iteration_list[0]->id);
    }
}
