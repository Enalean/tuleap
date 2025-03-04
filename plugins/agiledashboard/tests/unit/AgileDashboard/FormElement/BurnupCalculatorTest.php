<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement;

use AgileDashBoard_Semantic_InitialEffort;
use AgileDashboard_Semantic_InitialEffortFactory;
use Tracker_Artifact_ChangesetFactory;
use Tracker_ArtifactFactory;
use Tracker_Semantic_Status;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDone;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneFactory;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueIntegerTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BurnupCalculatorTest extends TestCase
{
    private const TIMESTAMP_1 = 1537187828;
    private const TIMESTAMP_2 = 1537189326;

    private BurnupCalculator $calculator;
    private array $plannable_trackers;

    public function testItCalculsBurnupWithFirstChangeset(): void
    {
        $this->setUpForTimestamp(self::TIMESTAMP_1);
        $effort = $this->calculator->getValue(101, self::TIMESTAMP_1, $this->plannable_trackers);

        self::assertSame(0.0, $effort->getTeamEffort());
        self::assertSame(9.0, $effort->getTotalEffort());
    }

    public function testItCalculsBurnupWithLastChangeset(): void
    {
        $this->setUpForTimestamp(self::TIMESTAMP_2);
        $effort = $this->calculator->getValue(101, self::TIMESTAMP_2, $this->plannable_trackers);

        self::assertSame(4.0, $effort->getTeamEffort());
        self::assertSame(9.0, $effort->getTotalEffort());
    }

    private function setUpForTimestamp(int $timestamp): void
    {
        $changeset_factory      = $this->createMock(Tracker_Artifact_ChangesetFactory::class);
        $artifact_factory       = $this->createMock(Tracker_ArtifactFactory::class);
        $burnup_dao             = $this->createMock(BurnupDataDAO::class);
        $initial_effort_factory = $this->createMock(AgileDashboard_Semantic_InitialEffortFactory::class);
        $semantic_done_factory  = $this->createMock(SemanticDoneFactory::class);

        $tracker                  = TrackerTestBuilder::aTracker()->withId(10)->build();
        $this->plannable_trackers = [$tracker->getId()];

        $this->calculator = new BurnupCalculator(
            $changeset_factory,
            $artifact_factory,
            $burnup_dao,
            $initial_effort_factory,
            $semantic_done_factory
        );

        $burnup_dao->method('searchLinkedArtifactsAtGivenTimestamp')
            ->with(101, $timestamp, $this->plannable_trackers)
            ->willReturn([
                ['id' => 102],
                ['id' => 103],
            ]);

        $user_story_status_semantic = $this->createMock(Tracker_Semantic_Status::class);
        Tracker_Semantic_Status::setInstance($user_story_status_semantic, $tracker);

        $user_story_01 = ArtifactTestBuilder::anArtifact(102)->inTracker($tracker)->build();
        $changeset_01  = ChangesetTestBuilder::aChangeset(1)->ofArtifact($user_story_01)->build();
        $changeset_02  = ChangesetTestBuilder::aChangeset(2)->ofArtifact($user_story_01)->build();

        $user_story_02 = ArtifactTestBuilder::anArtifact(103)->inTracker($tracker)->build();
        $changeset_03  = ChangesetTestBuilder::aChangeset(3)->ofArtifact($user_story_02)->build();
        $changeset_04  = ChangesetTestBuilder::aChangeset(4)->ofArtifact($user_story_02)->build();

        $artifact_factory->method('getArtifactById')
            ->willReturnMap([
                [102, $user_story_01],
                [103, $user_story_02],
            ]);

        $semantic_initial_effort = $this->createMock(AgileDashBoard_Semantic_InitialEffort::class);
        $initial_effort_field    = IntFieldBuilder::anIntField(324)->build();
        $semantic_initial_effort->method('getField')->willReturn($initial_effort_field);

        $initial_effort_factory->method('getByTracker')->with($tracker)->willReturn($semantic_initial_effort);

        $semantic_done = $this->createMock(SemanticDone::class);
        $semantic_done_factory->method('getInstanceByTracker')->with($tracker)->willReturn($semantic_done);

        if ($timestamp === self::TIMESTAMP_1) {
            $changeset_factory->expects(self::exactly(2))->method('getChangesetAtTimestamp')
                ->willReturnMap([
                    [$user_story_01, $timestamp, $changeset_01],
                    [$user_story_02, $timestamp, $changeset_03],
                ]);
            $semantic_done->method('isDone')->willReturnMap([
                [$changeset_01, false],
                [$changeset_03, false],
            ]);
            $user_story_status_semantic->method('isOpenAtGivenChangeset')
                ->willReturnMap([
                    [$changeset_01, true],
                    [$changeset_03, true],
                ]);
        } elseif ($timestamp === self::TIMESTAMP_2) {
            $changeset_factory->expects(self::exactly(2))->method('getChangesetAtTimestamp')
                ->willReturnMap([
                    [$user_story_01, $timestamp, $changeset_02],
                    [$user_story_02, $timestamp, $changeset_04],
                ]);
            $semantic_done->method('isDone')->willReturnMap([
                [$changeset_02, true],
                [$changeset_04, false],
            ]);
            $user_story_status_semantic->method('isOpenAtGivenChangeset')
                ->willReturnMap([
                    [$changeset_02, false],
                    [$changeset_04, true],
                ]);
        }

        ChangesetValueIntegerTestBuilder::aValue(1, $changeset_01, $initial_effort_field)->withValue(4)->build();
        ChangesetValueIntegerTestBuilder::aValue(2, $changeset_02, $initial_effort_field)->withValue(4)->build();

        ChangesetValueIntegerTestBuilder::aValue(3, $changeset_03, $initial_effort_field)->withValue(5)->build();
        ChangesetValueIntegerTestBuilder::aValue(4, $changeset_04, $initial_effort_field)->withValue(5)->build();
    }
}
