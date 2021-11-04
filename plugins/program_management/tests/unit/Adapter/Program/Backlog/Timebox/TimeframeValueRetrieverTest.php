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
use TimePeriodWithoutWeekEnd;
use Tracker;
use Tracker_ArtifactFactory;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\TimeboxIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithEndDate;

final class TimeframeValueRetrieverTest extends TestCase
{
    private RetrieveUserStub $retrieve_user;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&Artifact
     */
    private $artifact;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&Tracker
     */
    private $tracker;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&SemanticTimeframeBuilder
     */
    private $semantic_timeframe_builder;
    private TimeboxIdentifier $artifact_identifier;
    private UserIdentifier $user_identifier;
    private TimeframeValueRetriever $timeframe_value_retriever;


    protected function setUp(): void
    {
        $this->artifact_factory           = $this->createStub(Tracker_ArtifactFactory::class);
        $this->retrieve_user              = RetrieveUserStub::withGenericUser();
        $this->semantic_timeframe_builder = $this->createStub(SemanticTimeframeBuilder::class);

        $this->timeframe_value_retriever = new TimeframeValueRetriever(
            $this->artifact_factory,
            $this->retrieve_user,
            $this->semantic_timeframe_builder,
            new NullLogger()
        );

        $this->tracker = $this->createStub(Tracker::class);

        $this->artifact = $this->createStub(Artifact::class);
        $this->artifact->method('getTracker')->willReturn($this->tracker);

        $this->artifact_identifier = TimeboxIdentifierStub::withId(1);
        $this->user_identifier     = UserIdentifierStub::buildGenericUser();
    }

    public function testItReturnsNullWhenArtifactIsNotFound(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn(null);
        self::assertNull($this->timeframe_value_retriever->getStartDateValueTimestamp($this->artifact_identifier, $this->user_identifier));
        self::assertNull($this->timeframe_value_retriever->getEndDateValueTimestamp($this->artifact_identifier, $this->user_identifier));
    }


    public function testItReturnsValue(): void
    {
        $this->artifact_factory->method('getArtifactById')->willReturn($this->artifact);
        $timeframe_semantic   = $this->createStub(SemanticTimeframe::class);
        $timeframe_calculator = $this->createStub(TimeframeWithEndDate::class);
        $timeframe_calculator->method('buildTimePeriodWithoutWeekendForArtifact')->willReturn(
            TimePeriodWithoutWeekEnd::buildFromDuration(1635412289, 20)
        );
        $timeframe_semantic->method('getTimeframeCalculator')->willReturn($timeframe_calculator);

        $this->semantic_timeframe_builder->method('getSemantic')->willReturn($timeframe_semantic);

        self::assertEquals(1635412289, $this->timeframe_value_retriever->getStartDateValueTimestamp($this->artifact_identifier, $this->user_identifier));
        self::assertEquals(1637835089, $this->timeframe_value_retriever->getEndDateValueTimestamp($this->artifact_identifier, $this->user_identifier));
    }
}
