<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;

class AddInPlaceRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var \Tracker_FormElementFactory */
    private $form_element_factory;
    /** @var AddInPlaceRetriever */
    private $add_in_place_retriever;
    /** @var \Tracker_Semantic_Title */
    private $semantic_title;

    private const SEMANTIC_TITLE_FIELD_ID = 1533;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker_FormElement_Field_Selectbox
     */
    private $mapped_field;

    protected function setUp(): void
    {
        $this->semantic_title         = M::mock(\Tracker_Semantic_Title::class);
        $this->form_element_factory   = M::mock(\Tracker_FormElementFactory::class);
        $this->mapped_field           = M::mock(\Tracker_FormElement_Field_Selectbox::class);
        $this->add_in_place_retriever = new AddInPlaceRetriever(
            $this->form_element_factory
        );

        $this->mapped_field->shouldReceive('getId')->andReturn(1001);
    }

    protected function tearDown(): void
    {
        \Tracker_Semantic_Title::clearInstances();
    }

    public function testItReturnsNullWhenTrackerHasMoreThanOneChild(): void
    {
        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = M::mock(\PFUser::class);
        $tracker = $taskboard_tracker->getTracker();
        $tracker->shouldReceive('getChildren')->andReturn(
            [
                M::mock(Tracker::class),
                M::mock(Tracker::class)
            ]
        );

        $add_in_place = $this->add_in_place_retriever->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            []
        );

        $this->assertNull($add_in_place);
    }

    public function testItReturnsNullWhenTrackerHasNoChildren(): void
    {
        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = M::mock(\PFUser::class);
        $tracker = $taskboard_tracker->getTracker();
        $tracker->shouldReceive('getChildren')->andReturn([]);

        $add_in_place = $this->add_in_place_retriever->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            []
        );

        $this->assertNull($add_in_place);
    }

    public function testItReturnsNullWhenThereIsNoMappedFieldForChildTracker(): void
    {
        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = M::mock(\PFUser::class);
        $tracker = $taskboard_tracker->getTracker();

        $child_tracker = M::mock(Tracker::class);
        $child_tracker->shouldReceive('getId')->andReturn(42);

        $tracker->shouldReceive('getChildren')->andReturn([$child_tracker]);

        $add_in_place = $this->add_in_place_retriever->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            []
        );

        $this->assertNull($add_in_place);
    }

    public function testItReturnsNullWhenMappeFieldIsNotSubmitable(): void
    {
        $this->mapped_field->shouldReceive(['userCanSubmit' => false])->once();

        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = M::mock(\PFUser::class);
        $tracker = $taskboard_tracker->getTracker();

        $child_tracker = M::mock(Tracker::class);
        $child_tracker->shouldReceive('getId')->andReturn(42);

        $tracker->shouldReceive('getChildren')->andReturn([$child_tracker]);

        $add_in_place = $this->add_in_place_retriever->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            [42 => $this->mapped_field]
        );

        $this->assertNull($add_in_place);
    }

    public function testItReturnsNullWhenTitleSemanticIsNotDefinedOnChildTracker(): void
    {
        $this->mapped_field->shouldReceive(['userCanSubmit' => true])->once();

        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = M::mock(\PFUser::class);
        $tracker = $taskboard_tracker->getTracker();

        $child_tracker = M::mock(Tracker::class);
        $child_tracker->shouldReceive('getId')->andReturn(42);

        $tracker->shouldReceive('getChildren')->andReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, false, false);

        $add_in_place = $this->add_in_place_retriever->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            [42 => $this->mapped_field]
        );

        $this->assertNull($add_in_place);
    }

    public function testItReturnsNullWhenUserCannotUpdateFieldBoundToSemanticTitle(): void
    {
        $this->mapped_field->shouldReceive(['userCanSubmit' => true])->once();

        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = M::mock(\PFUser::class);
        $tracker = $taskboard_tracker->getTracker();

        $child_tracker = M::mock(Tracker::class);
        $child_tracker->shouldReceive('getId')->andReturn(42);

        $tracker->shouldReceive('getChildren')->andReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, false);

        $add_in_place = $this->add_in_place_retriever->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            [42 => $this->mapped_field]
        );

        $this->assertNull($add_in_place);
    }

    public function testItReturnsNullWhenSemanticTitleAndMappedFieldAreNotTheOnlyFieldRequiredAtArtifactSubmission(): void
    {
        $this->mapped_field->shouldReceive(['userCanSubmit' => true])->once();

        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = M::mock(\PFUser::class);
        $tracker = $taskboard_tracker->getTracker();

        $child_tracker = M::mock(Tracker::class);
        $child_tracker->shouldReceive('getId')->andReturn(42);

        $tracker->shouldReceive('getChildren')->andReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, true);
        $this->mockTrackerFields($child_tracker, true, true, true);

        $add_in_place = $this->add_in_place_retriever->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            [42 => $this->mapped_field]
        );

        $this->assertNull($add_in_place);
    }

    public function testItReturnsNullWhenParentTrackerDoesNotHaveAnArtifactLinkField(): void
    {
        $this->mapped_field->shouldReceive(['userCanSubmit' => true])->once();

        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = M::mock(\PFUser::class);
        $tracker = $taskboard_tracker->getTracker();

        $child_tracker = M::mock(Tracker::class);
        $child_tracker->shouldReceive('getId')->andReturn(42);

        $tracker->shouldReceive('getChildren')->andReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, true);
        $this->mockTrackerFields($child_tracker, true, false, true);

        $this->form_element_factory
            ->shouldReceive('getAnArtifactLinkField')
            ->with($user, $tracker)
            ->once()
            ->andReturnNull();

        $add_in_place = $this->add_in_place_retriever->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            [42 => $this->mapped_field]
        );

        $this->assertNull($add_in_place);
    }

    public function testItReturnsNullWhenParentTrackerDoesNotHaveAnUpdatableArtifactLinkField(): void
    {
        $this->mapped_field->shouldReceive(['userCanSubmit' => true])->once();

        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = M::mock(\PFUser::class);
        $tracker = $taskboard_tracker->getTracker();

        $child_tracker = M::mock(Tracker::class);
        $child_tracker->shouldReceive('getId')->andReturn(42);

        $tracker->shouldReceive('getChildren')->andReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, true);
        $this->mockTrackerFields($child_tracker, true, false, true);

        $artifact_link_field = M::mock(\Tracker_FormElement_Field_ArtifactLink::class);
        $this->form_element_factory
            ->shouldReceive('getAnArtifactLinkField')
            ->with($user, $tracker)
            ->once()
            ->andReturn($artifact_link_field);
        $artifact_link_field
            ->shouldReceive('userCanUpdate')
            ->with($user)
            ->once()
            ->andReturn(false);

        $add_in_place = $this->add_in_place_retriever->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            [42 => $this->mapped_field]
        );

        $this->assertNull($add_in_place);
    }

    public function testItReturnsAnAddInPlaceObject(): void
    {
        $this->mapped_field->shouldReceive(['userCanSubmit' => true])->once();

        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = M::mock(\PFUser::class);
        $tracker = $taskboard_tracker->getTracker();

        $child_tracker = M::mock(Tracker::class);
        $child_tracker->shouldReceive('getId')->andReturn(42);

        $tracker->shouldReceive('getChildren')->andReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, true);
        $this->mockTrackerFields($child_tracker, true, false, true);

        $artifact_link_field = M::mock(\Tracker_FormElement_Field_ArtifactLink::class);
        $this->form_element_factory
            ->shouldReceive('getAnArtifactLinkField')
            ->with($user, $tracker)
            ->once()
            ->andReturn($artifact_link_field);
        $artifact_link_field
            ->shouldReceive('userCanUpdate')
            ->with($user)
            ->once()
            ->andReturn(true);

        $this->form_element_factory
            ->shouldReceive('getAnArtifactLinkField')
            ->andReturnNull();

        $add_in_place = $this->add_in_place_retriever->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            [42 => $this->mapped_field]
        );

        $this->assertSame($child_tracker, $add_in_place->getChildTracker());
        $this->assertSame($artifact_link_field, $add_in_place->getParentArtifactLinkField());
    }

    private function mockSemanticTitle(\Tracker $child_tracker, bool $is_set, bool $user_can_submit): void
    {
        \Tracker_Semantic_Title::setInstance($this->semantic_title, $child_tracker);

        $title_field = null;

        if ($is_set) {
            $title_field = M::mock(\Tracker_FormElement_Field_String::class);
            $title_field->shouldReceive('userCanSubmit')->andReturn($user_can_submit);
            $title_field->shouldReceive('getId')->andReturn(self::SEMANTIC_TITLE_FIELD_ID);
        }

        $this->semantic_title->shouldReceive('getField')->andReturn($title_field)->once();
    }

    /**
     * @return array
     */
    private function getTaskboardTracker(): TaskboardTracker
    {
        $milestone_tracker = M::mock(Tracker::class);
        $tracker           = M::mock(Tracker::class)->shouldReceive('getId')->andReturn("12")->getMock();

        return new TaskboardTracker(
            $milestone_tracker,
            $tracker
        );
    }

    /**
     * @return array
     */
    private function mockTrackerFields(
        \Tracker $tracker,
        bool $is_title_field_required,
        bool $is_desc_field_required,
        bool $is_mapped_field_required
    ): void {
        $title_field = M::mock(\Tracker_FormElement_Field_String::class);
        $title_field->shouldReceive('isRequired')->andReturn($is_title_field_required);
        $title_field->shouldReceive('getId')->andReturn(self::SEMANTIC_TITLE_FIELD_ID);

        $desc_field = M::mock(\Tracker_FormElement_Field_Text::class);
        $desc_field->shouldReceive('isRequired')->andReturn($is_desc_field_required);
        $desc_field->shouldReceive('getId')->andReturn(1534);

        $this->mapped_field->shouldReceive('isRequired')->andReturn($is_mapped_field_required);

        $this->form_element_factory->shouldReceive('getUsedFields')->with($tracker)->andReturn(
            [$title_field, $desc_field, $this->mapped_field]
        );
    }
}
