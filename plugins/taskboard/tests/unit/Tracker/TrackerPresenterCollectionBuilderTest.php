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

declare(strict_types=1);

namespace Tuleap\Taskboard\Tracker;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_Milestone;
use Tracker;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedFieldRetriever;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TrackerPresenterCollectionBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TrackerPresenterCollectionBuilder $trackers_builder;
    private TrackerCollectionRetriever&MockObject $trackers_retriever;
    private MappedFieldRetriever&MockObject $mapped_field_retriever;
    private AddInPlaceRetriever&MockObject $add_in_place_tracker_retriever;

    protected function setUp(): void
    {
        $this->trackers_retriever             = $this->createMock(TrackerCollectionRetriever::class);
        $this->mapped_field_retriever         = $this->createMock(MappedFieldRetriever::class);
        $this->add_in_place_tracker_retriever = $this->createMock(AddInPlaceRetriever::class);
        $this->trackers_builder               = new TrackerPresenterCollectionBuilder(
            $this->trackers_retriever,
            $this->mapped_field_retriever,
            $this->add_in_place_tracker_retriever
        );
    }

    protected function tearDown(): void
    {
        \Tracker_Semantic_Title::clearInstances();
        \Tracker_Semantic_Contributor::clearInstances();
    }

    public function testBuildCollectionReturnsEmptyArrayWhenNoTrackers(): void
    {
        $milestone = $this->createMock(Planning_Milestone::class);
        $user      = UserTestBuilder::aUser()->build();
        $this->trackers_retriever
            ->expects(self::once())
            ->method('getTrackersForMilestone')
            ->with($milestone)
            ->willReturn(new TrackerCollection([]));

        $this->mapped_field_retriever->expects(self::never())->method('getField');

        $result = $this->trackers_builder->buildCollection($milestone, $user);
        self::assertSame(0, count($result));
    }

    public function testBuildCollectionReturnsCannotUpdateWhenNoMappedFieldAndCannotUpdateTitle(): void
    {
        $milestone         = $this->createMock(Planning_Milestone::class);
        $user              = UserTestBuilder::aUser()->build();
        $milestone_tracker = TrackerTestBuilder::aTracker()->build();
        $tracker           = $this->mockTracker(27);
        $taskboard_tracker = new TaskboardTracker($milestone_tracker, $tracker);
        $this->trackers_retriever
            ->expects(self::once())
            ->method('getTrackersForMilestone')
            ->with($milestone)
            ->willReturn(new TrackerCollection([$taskboard_tracker]));

        $this->mapped_field_retriever
            ->expects(self::once())
            ->method('getField')
            ->with($taskboard_tracker)
            ->willReturn(null);

        $this->mockSemanticTitle($taskboard_tracker, true, false);
        $this->mockSemanticContributor($taskboard_tracker, true, true);
        $this->add_in_place_tracker_retriever
            ->expects(self::once())
            ->method('retrieveAddInPlace')
            ->with($taskboard_tracker, $user, self::isInstanceOf(MappedFieldsCollection::class))
            ->willReturn(null);

        $result = $this->trackers_builder->buildCollection($milestone, $user);
        self::assertNotNull($result[0]);
        self::assertFalse($result[0]->can_update_mapped_field);
        self::assertNull($result[0]->title_field);
    }

    public function testBuildCollectionReturnsTrackerPresenters(): void
    {
        $milestone                = $this->createMock(Planning_Milestone::class);
        $user                     = UserTestBuilder::aUser()->build();
        $milestone_tracker        = TrackerTestBuilder::aTracker()->build();
        $first_tracker            = $this->mockTracker(27);
        $second_tracker           = $this->mockTracker(85);
        $third_tracker            = $this->mockTracker(96);
        $fourth_tracker           = $this->mockTracker(99);
        $first_taskboard_tracker  = new TaskboardTracker($milestone_tracker, $first_tracker);
        $second_taskboard_tracker = new TaskboardTracker($milestone_tracker, $second_tracker);
        $third_taskboard_tracker  = new TaskboardTracker($milestone_tracker, $third_tracker);
        $fourth_taskboard_tracker = new TaskboardTracker($milestone_tracker, $fourth_tracker);

        $this->trackers_retriever
            ->expects(self::once())
            ->method('getTrackersForMilestone')
            ->with($milestone)
            ->willReturn(
                new TrackerCollection(
                    [
                        $first_taskboard_tracker,
                        $second_taskboard_tracker,
                        $third_taskboard_tracker,
                        $fourth_taskboard_tracker,
                    ]
                )
            );

        $field_01 = $this->mockMappedField($user, true);
        $field_02 = $this->mockMappedField($user, false);
        $field_03 = $this->mockMappedField($user, false);
        $field_04 = $this->mockMappedField($user, false);

        $this->mapped_field_retriever
            ->method('getField')
            ->willReturnMap([
                [$first_taskboard_tracker, $field_01],
                [$second_taskboard_tracker, $field_02],
                [$third_taskboard_tracker, $field_03],
                [$fourth_taskboard_tracker, $field_04],
            ]);

        $this->mockSemanticTitle($first_taskboard_tracker, false, true);
        $this->mockSemanticTitle($second_taskboard_tracker, true, true);
        $this->mockSemanticTitle($third_taskboard_tracker, true, true, \Tracker_FormElement_Field_String::class);
        $this->mockSemanticTitle($fourth_taskboard_tracker, true, true);

        $this->mockSemanticContributor($first_taskboard_tracker, false, false);
        $this->mockSemanticContributor($second_taskboard_tracker, true, false);
        $this->mockSemanticContributor($third_taskboard_tracker, true, true);
        $this->mockSemanticContributor($fourth_taskboard_tracker, true, true, \Tracker_FormElement_Field_MultiSelectbox::class);

        $field_art_link = $this->createMock(\Tracker_FormElement_Field_ArtifactLink::class);
        $field_art_link->method('getId')->willReturn(999);

        $this->add_in_place_tracker_retriever
            ->method('retrieveAddInPlace')
            ->willReturnCallback(
                static fn (TaskboardTracker $taskboard_tracker, PFUser $arguser, MappedFieldsCollection $mapped_fields_collection) => match (true) {
                    $taskboard_tracker === $first_taskboard_tracker
                    || $taskboard_tracker === $second_taskboard_tracker
                    || $taskboard_tracker === $third_taskboard_tracker
                    => null,
                    $taskboard_tracker === $fourth_taskboard_tracker
                    && $arguser === $user
                    => new AddInPlace(
                        TrackerTestBuilder::aTracker()->withId(666)->build(),
                        $field_art_link,
                    )
                }
            );

        $result = $this->trackers_builder->buildCollection($milestone, $user);

        $first_result = $result[0];
        self::assertNotNull($first_result);

        $second_result = $result[1];
        self::assertNotNull($second_result);

        $third_result = $result[2];
        self::assertNotNull($third_result);

        $forth_result = $result[3];
        self::assertNotNull($forth_result);

        self::assertSame(27, $first_result->id);
        self::assertTrue($first_result->can_update_mapped_field);
        self::assertNull($first_result->title_field);
        self::assertSame(85, $second_result->id);
        self::assertFalse($second_result->can_update_mapped_field);
        self::assertNotNull($second_result->title_field);
        self::assertEquals(1533, $second_result->title_field->id);
        self::assertFalse($second_result->title_field->is_string_field);
        self::assertNotNull($third_result->title_field);
        self::assertTrue($third_result->title_field->is_string_field);
        self::assertNull($first_result->add_in_place);
        self::assertNull($second_result->add_in_place);
        self::assertNull($third_result->add_in_place);
        self::assertNotNull($forth_result->add_in_place);
        self::assertEquals(666, $forth_result->add_in_place->child_tracker_id);
        self::assertEquals(999, $forth_result->add_in_place->parent_artifact_link_field_id);

        self::assertNull($first_result->assigned_to_field);
        self::assertNull($second_result->assigned_to_field);
        self::assertNotNull($third_result->assigned_to_field);
        self::assertEquals(1534, $third_result->assigned_to_field->id);
        self::assertFalse($third_result->assigned_to_field->is_multiple);
        self::assertNotNull($forth_result->assigned_to_field);
        self::assertEquals(1534, $forth_result->assigned_to_field->id);
        self::assertTrue($forth_result->assigned_to_field->is_multiple);
    }

    private function mockMappedField(
        PFUser $user,
        bool $can_user_update,
    ): MockObject&Tracker_FormElement_Field_Selectbox {
        $sb_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $sb_field
            ->expects(self::once())
            ->method('userCanUpdate')
            ->with($user)
            ->willReturn($can_user_update);

        return $sb_field;
    }

    private function mockTracker(int $id): MockObject&Tracker
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn($id);

        return $tracker;
    }

    /**
     * @param class-string $classname
     */
    private function mockSemanticTitle(
        TaskboardTracker $taskboard_tracker,
        bool $is_semantic_set,
        bool $can_user_update,
        string $classname = \Tracker_FormElement_Field_Text::class,
    ): void {
        $semantic_title = $this->createMock(\Tracker_Semantic_Title::class);
        \Tracker_Semantic_Title::setInstance($semantic_title, $taskboard_tracker->getTracker());

        $title_field = null;

        if ($is_semantic_set) {
            $title_field = $this->createMock($classname);
            $title_field->method('getId')->willReturn(1533);
            $title_field->method('userCanUpdate')->willReturn($can_user_update);
        }

        $semantic_title->method('getField')->willReturn($title_field);
    }

    /**
     * @param class-string $classname
     */
    private function mockSemanticContributor(
        TaskboardTracker $taskboard_tracker,
        bool $is_semantic_set,
        bool $can_user_update,
        string $classname = \Tracker_FormElement_Field_Selectbox::class,
    ): void {
        $semantic_contributor = $this->createMock(\Tracker_Semantic_Contributor::class);
        \Tracker_Semantic_Contributor::setInstance($semantic_contributor, $taskboard_tracker->getTracker());

        $contributor_field = null;

        if ($is_semantic_set) {
            $contributor_field = $this->createMock($classname);
            $contributor_field->method('getId')->willReturn(1534);
            $contributor_field->method('userCanUpdate')->willReturn($can_user_update);
            $contributor_field->method('isMultiple')->willReturn(
                $classname === \Tracker_FormElement_Field_MultiSelectbox::class || $classname === \Tracker_FormElement_Field_Checkbox::class
            );
        }

        $semantic_contributor->method('getField')->willReturn($contributor_field);
    }
}
