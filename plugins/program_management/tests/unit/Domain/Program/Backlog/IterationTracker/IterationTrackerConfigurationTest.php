<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker;

use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationLabelsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class IterationTrackerConfigurationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ITERATION_TRACKER_ID = 94;
    private const LABEL                = 'Iterations';
    private const SUB_LABEL            = 'iteration';
    private RetrieveVisibleIterationTrackerStub $tracker_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->tracker_retriever = RetrieveVisibleIterationTrackerStub::withValidTracker(
            TrackerReferenceStub::withId(self::ITERATION_TRACKER_ID)
        );
    }

    private function getConfiguration(): ?IterationTrackerConfiguration
    {
        return IterationTrackerConfiguration::fromProgram(
            $this->tracker_retriever,
            RetrieveIterationLabelsStub::buildLabels(self::LABEL, self::SUB_LABEL),
            ProgramIdentifierBuilder::build(),
            UserIdentifierStub::buildGenericUser()
        );
    }

    public function testItBuildsFromProgram(): void
    {
        $configuration = $this->getConfiguration();
        self::assertNotNull($configuration);
        self::assertSame(self::ITERATION_TRACKER_ID, $configuration->iteration_tracker->getId());
        self::assertSame(self::LABEL, $configuration->labels->label);
        self::assertSame(self::SUB_LABEL, $configuration->labels->sub_label);
    }

    public function testItReturnsNullWhenThereIsNoIterationTrackerInThisProgramOrUserCannotSeeIt(): void
    {
        $this->tracker_retriever = RetrieveVisibleIterationTrackerStub::withNotVisibleIterationTracker();
        self::assertNull($this->getConfiguration());
    }
}
