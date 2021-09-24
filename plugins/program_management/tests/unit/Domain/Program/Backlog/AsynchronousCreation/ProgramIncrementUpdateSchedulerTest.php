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
use Tuleap\ProgramManagement\Tests\Stub\DispatchProgramIncrementUpdateStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveLastChangesetStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchIterationsStub;
use Tuleap\ProgramManagement\Tests\Stub\StoreProgramIncrementUpdateStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsVisibleArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationHasBeenLinkedBeforeStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIterationsFeatureActiveStub;

final class ProgramIncrementUpdateSchedulerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProgramIncrementUpdate $update;
    private StoreProgramIncrementUpdateStub $update_store;
    private DispatchProgramIncrementUpdateStub $update_dispatcher;

    protected function setUp(): void
    {
        $this->update            = ProgramIncrementUpdateBuilder::build();
        $this->update_store      = StoreProgramIncrementUpdateStub::withCount();
        $this->update_dispatcher = DispatchProgramIncrementUpdateStub::withCount();
    }

    private function getScheduler(): ProgramIncrementUpdateScheduler
    {
        return new ProgramIncrementUpdateScheduler(
            $this->update_store,
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
            $this->update_dispatcher
        );
    }

    public function testItSchedulesAnUpdateAndIterationCreations(): void
    {
        $this->getScheduler()->replicateProgramIncrementUpdate($this->update);
        self::assertSame(1, $this->update_store->getCallCount());
        self::assertSame(1, $this->update_dispatcher->getCallCount());
    }
}
