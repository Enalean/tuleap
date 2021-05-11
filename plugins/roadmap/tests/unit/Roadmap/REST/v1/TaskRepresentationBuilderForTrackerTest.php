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
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Semantic\Progress\MethodNotConfigured;
use Tuleap\Tracker\Semantic\Progress\SemanticProgress;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

class TaskRepresentationBuilderForTrackerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|SemanticProgressBuilder
     */
    private $progress_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|TimeframeBuilder
     */
    private $timeframe_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|IRetrieveDependencies
     */
    private $dependencies_retriever;

    protected function setUp(): void
    {
        $semantic = $this->createMock(SemanticProgress::class);
        $semantic->method('getComputationMethod')->willReturn(new MethodNotConfigured());

        $this->progress_builder = $this->createMock(SemanticProgressBuilder::class);
        $this->progress_builder->method('getSemantic')->willReturn($semantic);

        $this->timeframe_builder      = $this->createMock(TimeframeBuilder::class);
        $this->dependencies_retriever = $this->createMock(IRetrieveDependencies::class);

        $this->user = UserTestBuilder::aUser()->build();
    }

    public function testBuildRepresentation(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(42)
            ->withTitle('There is a bug')
            ->inProject(new \Project(['group_id' => 101, 'group_name' => 'ACME Corp']))
            ->build();
        $builder  = new TaskRepresentationBuilderForTracker(
            $artifact->getTracker(),
            $this->timeframe_builder,
            $this->dependencies_retriever,
            $this->progress_builder
        );

        $this->timeframe_builder->method('buildTimePeriodWithoutWeekendForArtifactForREST')->willReturn(
            \TimePeriodWithoutWeekEnd::buildFromEndDate(
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
    }

    public function testArtifactBelongsToTheRightTracker(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(42)
            ->withTitle('There is a bug')
            ->inProject(new \Project(['group_id' => 101, 'group_name' => 'ACME Corp']))
            ->build();
        $builder  = new TaskRepresentationBuilderForTracker(
            TrackerTestBuilder::aTracker()->build(),
            $this->timeframe_builder,
            $this->dependencies_retriever,
            $this->progress_builder
        );

        $this->expectException(\RuntimeException::class);

        $builder->buildRepresentation($artifact, $this->user);
    }
}
