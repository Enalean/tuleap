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
use Tracker_FormElement_Field_Selectbox;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\SearchMappedFieldStub;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedFieldRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\FormElement\Field\ListFields\RetrieveUsedListFieldStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerPresenterCollectionBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TrackerCollectionRetriever&MockObject $trackers_retriever;
    private SearchMappedFieldStub $search_mapped_field;
    private RetrieveUsedListFieldStub $field_retriever;
    private AddInPlaceRetriever&MockObject $add_in_place_tracker_retriever;
    private \Planning_ArtifactMilestone $milestone;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->trackers_retriever             = $this->createMock(TrackerCollectionRetriever::class);
        $this->search_mapped_field            = SearchMappedFieldStub::withNoField();
        $this->field_retriever                = RetrieveUsedListFieldStub::withNoField();
        $this->add_in_place_tracker_retriever = $this->createMock(AddInPlaceRetriever::class);

        $project_id      = 122;
        $this->milestone = new \Planning_ArtifactMilestone(
            ProjectTestBuilder::aProject()->withId($project_id)->build(),
            PlanningBuilder::aPlanning($project_id)->build(),
            ArtifactTestBuilder::anArtifact(56)->build()
        );
        $this->user      = UserTestBuilder::buildWithDefaults();
    }

    protected function tearDown(): void
    {
        \Tracker_Semantic_Title::clearInstances();
        \Tracker_Semantic_Contributor::clearInstances();
    }

    /** @return TrackerPresenter[] */
    private function getCollection(): array
    {
        $status_retriever = $this->createStub(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
        $status_retriever->method('getField')->willReturn(null);

        $builder = new TrackerPresenterCollectionBuilder(
            $this->trackers_retriever,
            new MappedFieldRetriever(
                $status_retriever,
                new FreestyleMappedFieldRetriever(
                    $this->search_mapped_field,
                    $this->field_retriever
                )
            ),
            $this->add_in_place_tracker_retriever
        );
        return $builder->buildCollection($this->milestone, $this->user);
    }

    public function testBuildCollectionReturnsEmptyArrayWhenNoTrackers(): void
    {
        $this->trackers_retriever
            ->expects(self::once())
            ->method('getTrackersForMilestone')
            ->with($this->milestone)
            ->willReturn(new TrackerCollection([]));

        self::assertEmpty($this->getCollection());
    }

    public function testBuildCollectionReturnsCannotUpdateWhenNoMappedFieldAndCannotUpdateTitle(): void
    {
        $taskboard_tracker = new TaskboardTracker(
            TrackerTestBuilder::aTracker()->build(),
            TrackerTestBuilder::aTracker()->withId(27)->build()
        );
        $this->trackers_retriever
            ->expects(self::once())
            ->method('getTrackersForMilestone')
            ->with($this->milestone)
            ->willReturn(new TrackerCollection([$taskboard_tracker]));

        $this->mockSemanticTitle($taskboard_tracker, true, false);
        $this->mockSemanticContributor($taskboard_tracker, true, true);
        $this->add_in_place_tracker_retriever
            ->expects(self::once())
            ->method('retrieveAddInPlace')
            ->with($taskboard_tracker, $this->user, self::isInstanceOf(MappedFieldsCollection::class))
            ->willReturn(null);

        $result = $this->getCollection();
        self::assertCount(1, $result);
        self::assertNotNull($result[0]);
        self::assertFalse($result[0]->can_update_mapped_field);
        self::assertNull($result[0]->title_field);
    }

    public function testBuildCollectionReturnsTrackerPresenters(): void
    {
        $milestone_tracker        = TrackerTestBuilder::aTracker()->build();
        $first_taskboard_tracker  = new TaskboardTracker(
            $milestone_tracker,
            TrackerTestBuilder::aTracker()->withId(27)->build()
        );
        $second_taskboard_tracker = new TaskboardTracker(
            $milestone_tracker,
            TrackerTestBuilder::aTracker()->withId(85)->build()
        );
        $third_taskboard_tracker  = new TaskboardTracker(
            $milestone_tracker,
            TrackerTestBuilder::aTracker()->withId(96)->build()
        );
        $fourth_taskboard_tracker = new TaskboardTracker(
            $milestone_tracker,
            TrackerTestBuilder::aTracker()->withId(99)->build()
        );

        $this->trackers_retriever
            ->expects(self::once())
            ->method('getTrackersForMilestone')
            ->with($this->milestone)
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

        $field_01 = $this->mockMappedField(1770, $this->user, true);
        $field_02 = $this->mockMappedField(2341, $this->user, false);
        $field_03 = $this->mockMappedField(2875, $this->user, false);
        $field_04 = $this->mockMappedField(2508, $this->user, false);

        $this->search_mapped_field = SearchMappedFieldStub::withMappedFields(
            [$first_taskboard_tracker, 1770],
            [$second_taskboard_tracker, 2341],
            [$third_taskboard_tracker, 2875],
            [$fourth_taskboard_tracker, 2508],
        );
        $this->field_retriever     = RetrieveUsedListFieldStub::withFields($field_01, $field_02, $field_03, $field_04);

        $this->mockSemanticTitle($first_taskboard_tracker, false, true);
        $this->mockSemanticTitle($second_taskboard_tracker, true, true);
        $this->mockSemanticTitle($third_taskboard_tracker, true, true, \Tracker_FormElement_Field_String::class);
        $this->mockSemanticTitle($fourth_taskboard_tracker, true, true);

        $this->mockSemanticContributor($first_taskboard_tracker, false, false);
        $this->mockSemanticContributor($second_taskboard_tracker, true, false);
        $this->mockSemanticContributor($third_taskboard_tracker, true, true);
        $this->mockSemanticContributor(
            $fourth_taskboard_tracker,
            true,
            true,
            \Tracker_FormElement_Field_MultiSelectbox::class
        );

        $field_art_link = ArtifactLinkFieldBuilder::anArtifactLinkField(999)->build();

        $this->add_in_place_tracker_retriever
            ->method('retrieveAddInPlace')
            ->willReturnCallback(
                fn(
                    TaskboardTracker $taskboard_tracker,
                    PFUser $arguser,
                    MappedFieldsCollection $mapped_fields_collection,
                ) => match (true) {
                    $taskboard_tracker === $first_taskboard_tracker
                    || $taskboard_tracker === $second_taskboard_tracker
                    || $taskboard_tracker === $third_taskboard_tracker
                    => null,
                    $taskboard_tracker === $fourth_taskboard_tracker
                    && $arguser === $this->user
                    => new AddInPlace(
                        TrackerTestBuilder::aTracker()->withId(666)->build(),
                        $field_art_link,
                    )
                }
            );

        $result = $this->getCollection();

        self::assertCount(4, $result);
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
        self::assertEquals($field_art_link->getId(), $forth_result->add_in_place->parent_artifact_link_field_id);

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
        int $field_id,
        PFUser $user,
        bool $can_user_update,
    ): MockObject&Tracker_FormElement_Field_Selectbox {
        $sb_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $sb_field->method('getId')->willReturn($field_id);
        $sb_field->expects(self::once())
            ->method('userCanUpdate')
            ->with($user)
            ->willReturn($can_user_update);

        return $sb_field;
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
