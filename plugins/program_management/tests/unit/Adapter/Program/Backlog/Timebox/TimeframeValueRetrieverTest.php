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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox;

use Psr\Log\NullLogger;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\TimeboxIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithEndDate;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TimeframeValueRetrieverTest extends TestCase
{
    private Artifact $artifact;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;
    private TimeboxIdentifier $artifact_identifier;
    private UserIdentifier $user_identifier;

    protected function setUp(): void
    {
        $this->semantic_timeframe_builder = $this->createStub(SemanticTimeframeBuilder::class);

        $tracker        = TrackerTestBuilder::aTracker()->build();
        $changeset      = ChangesetTestBuilder::aChangeset('1')->build();
        $this->artifact = ArtifactTestBuilder::anArtifact(100)
            ->inTracker($tracker)
            ->withChangesets($changeset)
            ->build();

        $this->artifact_identifier = TimeboxIdentifierStub::withId(1);
        $this->user_identifier     = UserIdentifierStub::buildGenericUser();
    }

    private function getRetriever(): TimeframeValueRetriever
    {
        return new TimeframeValueRetriever(
            RetrieveFullArtifactStub::withArtifact($this->artifact),
            RetrieveUserStub::withGenericUser(),
            $this->semantic_timeframe_builder,
            new NullLogger()
        );
    }

    public function testItReturnsValue(): void
    {
        $timeframe_semantic   = $this->createStub(SemanticTimeframe::class);
        $timeframe_calculator = $this->createStub(TimeframeWithEndDate::class);
        $timeframe_calculator->method('buildDatePeriodWithoutWeekendForChangeset')->willReturn(
            DatePeriodWithoutWeekEnd::buildFromDuration(1635412289, 20)
        );
        $timeframe_semantic->method('getTimeframeCalculator')->willReturn($timeframe_calculator);

        $this->semantic_timeframe_builder->method('getSemantic')->willReturn($timeframe_semantic);

        self::assertSame(1635412289, $this->getRetriever()->getStartDateValueTimestamp($this->artifact_identifier, $this->user_identifier));
        self::assertSame(1637835089, $this->getRetriever()->getEndDateValueTimestamp($this->artifact_identifier, $this->user_identifier));
    }
}
