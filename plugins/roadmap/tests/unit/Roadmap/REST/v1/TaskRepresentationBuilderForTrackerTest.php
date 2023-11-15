<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

use Psr\Log\NullLogger;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Semantic\Progress\MethodNotConfigured;
use Tuleap\Tracker\Semantic\Progress\SemanticProgress;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeImpliedFromAnotherTracker;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerColor;

final class TaskRepresentationBuilderForTrackerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \PFUser $user;
    private \PHPUnit\Framework\MockObject\MockObject&SemanticProgressBuilder $progress_builder;
    private \PHPUnit\Framework\MockObject\MockObject&IRetrieveDependencies $dependencies_retriever;
    private \PHPUnit\Framework\MockObject\MockObject&TimeframeImpliedFromAnotherTracker $timeframe_calculator;
    private \Tracker $tracker;

    protected function setUp(): void
    {
        $semantic = $this->createMock(SemanticProgress::class);
        $semantic->method('getComputationMethod')->willReturn(new MethodNotConfigured());

        $this->progress_builder = $this->createMock(SemanticProgressBuilder::class);
        $this->progress_builder->method('getSemantic')->willReturn($semantic);

        $this->dependencies_retriever = $this->createMock(IRetrieveDependencies::class);
        $this->timeframe_calculator   = $this->getMockBuilder(TimeframeImpliedFromAnotherTracker::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['buildDatePeriodWithoutWeekendForArtifactForREST'])
            ->getMock();

        $this->user = UserTestBuilder::aUser()->build();

        $this->tracker = TrackerTestBuilder::aTracker()
            ->withId(101)
            ->withName("bug")
            ->withColor(TrackerColor::fromName('fiesta-red'))
            ->build();

        $semantic_status = $this->createMock(\Tracker_Semantic_Status::class);
        $semantic_status->method('isOpen')->willReturn(true);
        \Tracker_Semantic_Status::setInstance($semantic_status, $this->tracker);
    }

    protected function tearDown(): void
    {
        \Tracker_Semantic_Status::clearInstances();
    }

    public function testBuildRepresentation(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(42)
            ->withTitle('There is a bug')
            ->inTracker($this->tracker)
            ->inProject(ProjectTestBuilder::aProject()->withPublicName('ACME Corp')->build())
            ->build();
        $builder  = new TaskRepresentationBuilderForTracker(
            $artifact->getTracker(),
            $this->timeframe_calculator,
            $this->dependencies_retriever,
            $this->progress_builder,
            new NullLogger()
        );

        $this->timeframe_calculator->method('buildDatePeriodWithoutWeekendForArtifactForREST')->willReturn(
            DatePeriodWithoutWeekEnd::buildFromEndDate(
                (new \DateTimeImmutable('@1234567890'))->getTimestamp(),
                1234567891,
                new NullLogger()
            )
        );

        $this->dependencies_retriever->method('getDependencies')->willReturn([]);

        $representation = $builder->buildRepresentation($artifact, $this->user);
        self::assertEquals('There is a bug', $representation->title);
        self::assertEquals('bug #42', $representation->xref);
        self::assertEquals(42, $representation->id);
        self::assertEquals('fiesta-red', $representation->color_name);
        self::assertEquals('roadmap_tasks/42/subtasks', $representation->subtasks_uri);
        self::assertEquals(null, $representation->progress);
        self::assertEquals('ACME Corp', $representation->project->label);
        self::assertTrue($representation->are_dates_implied);
        self::assertTrue($representation->is_open);
    }

    public function testArtifactBelongsToTheRightTracker(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(42)
            ->withTitle('There is a bug')
            ->inProject(new \Project(['group_id' => 101, 'group_name' => 'ACME Corp']))
            ->build();
        $builder  = new TaskRepresentationBuilderForTracker(
            TrackerTestBuilder::aTracker()->build(),
            $this->timeframe_calculator,
            $this->dependencies_retriever,
            $this->progress_builder,
            new NullLogger()
        );

        $this->expectException(\RuntimeException::class);

        $builder->buildRepresentation($artifact, $this->user);
    }
}
