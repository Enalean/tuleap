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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

use Psr\Log\NullLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\ProgramIncrementUpdate;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementUpdateBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationHasBeenLinkedBeforeStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationsFeatureActiveStub;

final class ProgramIncrementUpdateSchedulerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProgramIncrementUpdate $update;

    protected function setUp(): void
    {
        $this->update = ProgramIncrementUpdateBuilder::build();
    }

    private function getScheduler(): ProgramIncrementUpdateScheduler
    {
        return new ProgramIncrementUpdateScheduler(
            new class implements StoreProgramIncrementUpdate {
                public function storeUpdate(ProgramIncrementUpdate $update): void
                {
                    // Side effects
                }
            },
            new IterationCreationDetector(
                VerifyIterationsFeatureActiveStub::withActiveFeature(),
                SearchIterationsStub::withIterationIds(101, 102),
                VerifyIsVisibleArtifactStub::withAlwaysVisibleArtifacts(),
                VerifyIterationHasBeenLinkedBeforeStub::withNoIteration(),
                new NullLogger(),
                RetrieveLastChangesetStub::withLastChangesetIds(457, 4915),
            ),
            new class implements StoreIterationCreations {
                public function storeCreations(IterationCreation ...$creations): void
                {
                    // Side effects
                }
            },
            new class implements DispatchProgramIncrementUpdate {
                public function dispatchUpdate(ProgramIncrementUpdate $update, IterationCreation ...$creations): void
                {
                    // Side effects
                }
            }
        );
    }

    public function testItSchedulesAnUpdateAndIterationCreations(): void
    {
        $this->getScheduler()->replicateProgramIncrementUpdate($this->update);
        $this->expectNotToPerformAssertions();
    }
}
