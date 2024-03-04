<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement\Burnup;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_Artifact_ChangesetFactory;
use Tracker_ArtifactFactory;
use Tracker_ArtifactLinkInfo;
use Tracker_FormElementFactory;
use Tracker_Semantic_Status;
use Tuleap\AgileDashboard\FormElement\BurnupDataDAO;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueArtifactLinkTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class CountElementsCalculatorTest extends TestCase
{
    private CountElementsCalculator $calculator;
    private Tracker_Artifact_ChangesetFactory&MockObject $changeset_factory;
    private Tracker_ArtifactFactory&MockObject $artifact_factory;
    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private BurnupDataDAO&MockObject $burnup_dao;
    private Tracker $task_tracker;
    private Tracker $user_story_tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->changeset_factory    = $this->createMock(Tracker_Artifact_ChangesetFactory::class);
        $this->artifact_factory     = $this->createMock(Tracker_ArtifactFactory::class);
        $this->form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $this->burnup_dao           = $this->createMock(BurnupDataDAO::class);
        $this->user_story_tracker   = TrackerTestBuilder::aTracker()->withId(10)->build();
        $this->task_tracker         = TrackerTestBuilder::aTracker()->withId(20)->build();

        $this->calculator = new CountElementsCalculator(
            $this->changeset_factory,
            $this->artifact_factory,
            $this->form_element_factory,
            $this->burnup_dao
        );
    }

    public function testItCountsDirectSubElementsOfMilestoneAndTheirChildren(): void
    {
        $artifact_id = 1;
        $timestamp   = 1565603104;

        $this->mockEpicUserStoriesAndTasks($artifact_id, $timestamp);

        $count_elements_cache_info = $this->calculator->getValue($artifact_id, $timestamp, [$this->user_story_tracker->getId(), $this->task_tracker->getId()]);

        self::assertSame(5, $count_elements_cache_info->getTotalElements());
        self::assertSame(3, $count_elements_cache_info->getClosedElements());
    }

    public function testItCountsDirectSubElementsOfMilestoneAndTheirChildrenWithoutCoutingTwiceElements(): void
    {
        $artifact_id = 1;
        $timestamp   = 1565603104;

        $this->mockEpicUserStoriesAndTasksWithMultipleLinksToTask($artifact_id, $timestamp);

        $count_elements_cache_info = $this->calculator->getValue($artifact_id, $timestamp, [10, 20]);

        self::assertSame(5, $count_elements_cache_info->getTotalElements());
        self::assertSame(3, $count_elements_cache_info->getClosedElements());
    }

    public function testItOnlyCountsDirectSubElementsOfMilestoneIfTheyDontHaveArtLinkField(): void
    {
        $artifact_id = 1;
        $timestamp   = 1565603104;

        $this->mockUserStoriesWithoutArtLinkField($artifact_id, $timestamp);

        $count_elements_cache_info = $this->calculator->getValue($artifact_id, $timestamp, [10, 20]);

        self::assertSame(3, $count_elements_cache_info->getTotalElements());
        self::assertSame(1, $count_elements_cache_info->getClosedElements());
    }

    public function testItOnlyCountsDirectSubElementsOfMilestoneIfTheyDontHaveChildren(): void
    {
        $artifact_id = 1;
        $timestamp   = 1565603104;

        $this->mockUserStoriesWithoutChildren($artifact_id, $timestamp);

        $count_elements_cache_info = $this->calculator->getValue($artifact_id, $timestamp, [10, 20]);

        self::assertSame(3, $count_elements_cache_info->getTotalElements());
        self::assertSame(1, $count_elements_cache_info->getClosedElements());
    }

    private function mockEpicUserStoriesAndTasks(int $artifact_id, int $timestamp): void
    {
        $this->burnup_dao->expects(self::once())->method('searchLinkedArtifactsAtGivenTimestamp')
            ->with($artifact_id, $timestamp, [$this->user_story_tracker->getId(), $this->task_tracker->getId()])
            ->willReturn([['id' => 2]]);

        $epic_tracker             = TrackerTestBuilder::aTracker()->build();
        $epic                     = ArtifactTestBuilder::anArtifact(2)->inTracker($epic_tracker)->build();
        $changeset_epic           = ChangesetTestBuilder::aChangeset('1')->build();
        $epic_artifact_link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();
        ChangesetValueArtifactLinkTestBuilder::aValue(1, $changeset_epic, $epic_artifact_link_field)
            ->withLinks([
                3 => new Tracker_ArtifactLinkInfo(3, '', 101, $this->user_story_tracker->getId(), 1, null),
                4 => new Tracker_ArtifactLinkInfo(4, '', 101, $this->user_story_tracker->getId(), 1, null),
            ])
            ->build();
        Tracker_Semantic_Status::setInstance(new Tracker_Semantic_Status(
            $epic_tracker
        ), $epic_tracker);

        $user_story_status_semantic = $this->createMock(Tracker_Semantic_Status::class);
        Tracker_Semantic_Status::setInstance($user_story_status_semantic, $this->user_story_tracker);
        $user_story_artifact_link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();

        $user_story_01           = ArtifactTestBuilder::anArtifact(3)
            ->inTracker($this->user_story_tracker)
            ->withParentWithoutPermissionChecking($epic)
            ->build();
        $changeset_user_story_01 = ChangesetTestBuilder::aChangeset('1')->build();
        ChangesetValueArtifactLinkTestBuilder::aValue(1, $changeset_user_story_01, $user_story_artifact_link_field)
            ->withLinks([])
            ->build();

        $user_story_02           = ArtifactTestBuilder::anArtifact(4)
            ->inTracker($this->user_story_tracker)
            ->withParentWithoutPermissionChecking($epic)
            ->build();
        $changeset_user_story_02 = ChangesetTestBuilder::aChangeset('1')->build();
        ChangesetValueArtifactLinkTestBuilder::aValue(1, $changeset_user_story_02, $user_story_artifact_link_field)
            ->withLinks([
                5 => new Tracker_ArtifactLinkInfo(5, '', 101, $this->task_tracker->getId(), 1, null),
                6 => new Tracker_ArtifactLinkInfo(6, '', 101, $this->task_tracker->getId(), 1, null),
            ])
            ->build();

        $task_status_semantic = $this->createMock(Tracker_Semantic_Status::class);
        Tracker_Semantic_Status::setInstance($task_status_semantic, $this->task_tracker);

        $task_01           = ArtifactTestBuilder::anArtifact(5)
            ->inTracker($this->task_tracker)
            ->withParentWithoutPermissionChecking($user_story_02)
            ->build();
        $changeset_task_01 = ChangesetTestBuilder::aChangeset('1')->build();

        $task_02           = ArtifactTestBuilder::anArtifact(6)
            ->inTracker($this->task_tracker)
            ->withParentWithoutPermissionChecking($user_story_02)
            ->build();
        $changeset_task_02 = ChangesetTestBuilder::aChangeset('1')->build();

        $this->artifact_factory->expects(self::exactly(5))->method('getArtifactById')
            ->withConsecutive([2], [3], [4], [5], [6])
            ->willReturn($epic, $user_story_01, $user_story_02, $task_01, $task_02);

        $this->changeset_factory->expects(self::exactly(5))->method('getChangesetAtTimestamp')
            ->withConsecutive(
                [$epic, $timestamp],
                [$user_story_01, $timestamp],
                [$user_story_02, $timestamp],
                [$task_01, $timestamp],
                [$task_02, $timestamp],
            )
            ->willReturnOnConsecutiveCalls(
                $changeset_epic,
                $changeset_user_story_01,
                $changeset_user_story_02,
                $changeset_task_01,
                $changeset_task_02,
            );

        $this->form_element_factory->expects(self::exactly(5))->method('getUsedArtifactLinkFields')
            ->withConsecutive(
                [$epic_tracker],
                [$this->user_story_tracker],
                [$this->user_story_tracker],
                [$this->task_tracker],
                [$this->task_tracker],
            )
            ->willReturnOnConsecutiveCalls(
                [$epic_artifact_link_field],
                [$user_story_artifact_link_field],
                [$user_story_artifact_link_field],
                [],
                [],
            );

        $user_story_status_semantic->expects(self::exactly(2))->method('isOpenAtGivenChangeset')
            ->withConsecutive([$changeset_user_story_01], [$changeset_user_story_02])
            ->willReturnOnConsecutiveCalls(true, false);

        $task_status_semantic->expects(self::exactly(2))->method('isOpenAtGivenChangeset')
            ->withConsecutive([$changeset_task_01], [$changeset_task_02])
            ->willReturn(false);
    }

    private function mockEpicUserStoriesAndTasksWithMultipleLinksToTask(int $artifact_id, int $timestamp): void
    {
        //This use case deal with the fact that epics and tasks can be planned into a Release.
        //So the tasks 01 must not be counted twice.

        $this->burnup_dao->expects(self::once())->method('searchLinkedArtifactsAtGivenTimestamp')
            ->with($artifact_id, $timestamp, [$this->user_story_tracker->getId(), $this->task_tracker->getId()])
            ->willReturn([
                ['id' => 2],
                ['id' => 5],
            ]);

        $epic_tracker         = TrackerTestBuilder::aTracker()->build();
        $epic_status_semantic = $this->createMock(Tracker_Semantic_Status::class);
        Tracker_Semantic_Status::setInstance($epic_status_semantic, $epic_tracker);
        $epic_artifact_link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();

        $epic           = ArtifactTestBuilder::anArtifact(2)->inTracker($epic_tracker)->build();
        $changeset_epic = ChangesetTestBuilder::aChangeset('1')->build();
        ChangesetValueArtifactLinkTestBuilder::aValue(1, $changeset_epic, $epic_artifact_link_field)
            ->withLinks([
                3 => new Tracker_ArtifactLinkInfo(3, '', 101, $this->user_story_tracker->getId(), 1, null),
                4 => new Tracker_ArtifactLinkInfo(4, '', 101, $this->user_story_tracker->getId(), 1, null),
            ])
            ->build();

        $user_story_status_semantic = $this->createMock(Tracker_Semantic_Status::class);
        Tracker_Semantic_Status::setInstance($user_story_status_semantic, $this->user_story_tracker);
        $user_story_artifact_link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();

        $user_story_01           = ArtifactTestBuilder::anArtifact(3)
            ->inTracker($this->user_story_tracker)
            ->withParentWithoutPermissionChecking($epic)
            ->build();
        $changeset_user_story_01 = ChangesetTestBuilder::aChangeset('1')->build();
        ChangesetValueArtifactLinkTestBuilder::aValue(1, $changeset_user_story_01, $user_story_artifact_link_field)
            ->withLinks([])
            ->build();

        $user_story_02           = ArtifactTestBuilder::anArtifact(4)
            ->inTracker($this->user_story_tracker)
            ->withParentWithoutPermissionChecking($epic)
            ->build();
        $changeset_user_story_02 = ChangesetTestBuilder::aChangeset('1')->build();
        ChangesetValueArtifactLinkTestBuilder::aValue(1, $changeset_user_story_02, $user_story_artifact_link_field)
            ->withLinks([
                5 => new Tracker_ArtifactLinkInfo(5, '', 101, $this->task_tracker->getId(), 1, null),
                6 => new Tracker_ArtifactLinkInfo(6, '', 101, $this->task_tracker->getId(), 1, null),
            ])
            ->build();

        $task_status_semantic = $this->createMock(Tracker_Semantic_Status::class);
        Tracker_Semantic_Status::setInstance($task_status_semantic, $this->task_tracker);

        $task_01           = ArtifactTestBuilder::anArtifact(5)
            ->inTracker($this->task_tracker)
            ->withParentWithoutPermissionChecking($user_story_02)
            ->build();
        $changeset_task_01 = ChangesetTestBuilder::aChangeset('1')->build();

        $task_02           = ArtifactTestBuilder::anArtifact(6)
            ->inTracker($this->task_tracker)
            ->withParentWithoutPermissionChecking($user_story_02)
            ->build();
        $changeset_task_02 = ChangesetTestBuilder::aChangeset('1')->build();

        $this->artifact_factory->expects(self::exactly(6))->method('getArtifactById')
            ->withConsecutive([2], [3], [4], [5], [6], [5])
            ->willReturn($epic, $user_story_01, $user_story_02, $task_01, $task_02, $task_01);

        $this->changeset_factory->expects(self::exactly(5))->method('getChangesetAtTimestamp')
            ->withConsecutive(
                [$epic, $timestamp],
                [$user_story_01, $timestamp],
                [$user_story_02, $timestamp],
                [$task_01, $timestamp],
                [$task_02, $timestamp],
            )
            ->willReturnOnConsecutiveCalls(
                $changeset_epic,
                $changeset_user_story_01,
                $changeset_user_story_02,
                $changeset_task_01,
                $changeset_task_02,
            );

        $this->form_element_factory->expects(self::exactly(5))->method('getUsedArtifactLinkFields')
            ->withConsecutive(
                [$epic_tracker],
                [$this->user_story_tracker],
                [$this->user_story_tracker],
                [$this->task_tracker],
                [$this->task_tracker],
            )
            ->willReturnOnConsecutiveCalls(
                [$epic_artifact_link_field],
                [$user_story_artifact_link_field],
                [$user_story_artifact_link_field],
                [],
                [],
            );

        $epic_status_semantic->expects(self::once())->method('isOpenAtGivenChangeset')
            ->with($changeset_epic)
            ->willReturn(true);

        $user_story_status_semantic->expects(self::exactly(2))->method('isOpenAtGivenChangeset')
            ->withConsecutive([$changeset_user_story_01], [$changeset_user_story_02])
            ->willReturnOnConsecutiveCalls(true, false);

        $task_status_semantic->expects(self::exactly(2))->method('isOpenAtGivenChangeset')
            ->withConsecutive([$changeset_task_01], [$changeset_task_02])
            ->willReturn(false);
    }

    private function mockUserStoriesWithoutArtLinkField(int $artifact_id, int $timestamp): void
    {
        $this->burnup_dao->expects(self::once())->method('searchLinkedArtifactsAtGivenTimestamp')
            ->with($artifact_id, $timestamp, [$this->user_story_tracker->getId(), $this->task_tracker->getId()])
            ->willReturn([
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
            ]);

        $user_story_status_semantic = $this->createMock(Tracker_Semantic_Status::class);
        Tracker_Semantic_Status::setInstance($user_story_status_semantic, $this->user_story_tracker);

        $user_story_01           = ArtifactTestBuilder::anArtifact(2)->inTracker($this->user_story_tracker)->build();
        $changeset_user_story_01 = ChangesetTestBuilder::aChangeset('1')->build();

        $user_story_02           = ArtifactTestBuilder::anArtifact(3)->inTracker($this->user_story_tracker)->build();
        $changeset_user_story_02 = ChangesetTestBuilder::aChangeset('1')->build();

        $user_story_03           = ArtifactTestBuilder::anArtifact(4)->inTracker($this->user_story_tracker)->build();
        $changeset_user_story_03 = ChangesetTestBuilder::aChangeset('1')->build();

        $this->artifact_factory->expects(self::exactly(3))->method('getArtifactById')
            ->withConsecutive([2], [3], [4])
            ->willReturnOnConsecutiveCalls($user_story_01, $user_story_02, $user_story_03);

        $this->changeset_factory->expects(self::exactly(3))->method('getChangesetAtTimestamp')
            ->withConsecutive(
                [$user_story_01, $timestamp],
                [$user_story_02, $timestamp],
                [$user_story_03, $timestamp],
            )
            ->willReturnOnConsecutiveCalls(
                $changeset_user_story_01,
                $changeset_user_story_02,
                $changeset_user_story_03,
            );

        $user_story_status_semantic->expects(self::exactly(3))->method('isOpenAtGivenChangeset')
            ->withConsecutive([$changeset_user_story_01], [$changeset_user_story_02], [$changeset_user_story_03])
            ->willReturnOnConsecutiveCalls(true, false, true);

        $this->form_element_factory->method('getUsedArtifactLinkFields')->willReturn([]);
    }

    private function mockUserStoriesWithoutChildren(int $artifact_id, int $timestamp): void
    {
        $this->burnup_dao->expects(self::once())->method('searchLinkedArtifactsAtGivenTimestamp')
            ->with($artifact_id, $timestamp, [$this->user_story_tracker->getId(), $this->task_tracker->getId()])
            ->willReturn([
                ['id' => 2],
                ['id' => 3],
                ['id' => 4],
            ]);

        $user_story_status_semantic = $this->createMock(Tracker_Semantic_Status::class);
        Tracker_Semantic_Status::setInstance($user_story_status_semantic, $this->user_story_tracker);
        $artifact_link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(1)->build();

        $user_story_01           = ArtifactTestBuilder::anArtifact(2)->inTracker($this->user_story_tracker)->build();
        $changeset_user_story_01 = ChangesetTestBuilder::aChangeset('1')->build();
        ChangesetValueArtifactLinkTestBuilder::aValue(1, $changeset_user_story_01, $artifact_link_field)
            ->withLinks([])
            ->build();

        $user_story_02           = ArtifactTestBuilder::anArtifact(3)->inTracker($this->user_story_tracker)->build();
        $changeset_user_story_02 = ChangesetTestBuilder::aChangeset('1')->build();
        ChangesetValueArtifactLinkTestBuilder::aValue(1, $changeset_user_story_02, $artifact_link_field)
            ->withLinks([])
            ->build();

        $user_story_03           = ArtifactTestBuilder::anArtifact(4)->inTracker($this->user_story_tracker)->build();
        $changeset_user_story_03 = ChangesetTestBuilder::aChangeset('1')->build();
        ChangesetValueArtifactLinkTestBuilder::aValue(1, $changeset_user_story_03, $artifact_link_field)
            ->withLinks([])
            ->build();

        $this->artifact_factory->expects(self::exactly(3))->method('getArtifactById')
            ->withConsecutive([2], [3], [4])
            ->willReturnOnConsecutiveCalls($user_story_01, $user_story_02, $user_story_03);

        $this->changeset_factory->expects(self::exactly(3))->method('getChangesetAtTimestamp')
            ->withConsecutive(
                [$user_story_01, $timestamp],
                [$user_story_02, $timestamp],
                [$user_story_03, $timestamp],
            )
            ->willReturnOnConsecutiveCalls(
                $changeset_user_story_01,
                $changeset_user_story_02,
                $changeset_user_story_03,
            );

        $this->form_element_factory->method('getUsedArtifactLinkFields')->willReturn([$artifact_link_field]);

        $user_story_status_semantic->expects(self::exactly(3))->method('isOpenAtGivenChangeset')
            ->withConsecutive([$changeset_user_story_01], [$changeset_user_story_02], [$changeset_user_story_03])
            ->willReturnOnConsecutiveCalls(true, false, true);
    }
}
