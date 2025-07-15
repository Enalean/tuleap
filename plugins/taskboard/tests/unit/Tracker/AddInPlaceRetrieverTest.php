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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Semantic\Title\RetrieveSemanticTitleField;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class AddInPlaceRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const SEMANTIC_TITLE_FIELD_ID = 1533;

    private MockObject&\Tracker_FormElementFactory $form_element_factory;
    private \Tracker_FormElement_Field_Selectbox&MockObject $mapped_field;
    private RetrieveSemanticTitleField $title_field_retriever;

    protected function setUp(): void
    {
        $this->form_element_factory  = $this->createMock(\Tracker_FormElementFactory::class);
        $this->mapped_field          = $this->createMock(\Tracker_FormElement_Field_Selectbox::class);
        $this->title_field_retriever = RetrieveSemanticTitleFieldStub::build();

        $this->mapped_field->method('getId')->willReturn(1001);
    }

    private function getRetriever(): AddInPlaceRetriever
    {
        return new AddInPlaceRetriever($this->form_element_factory, $this->title_field_retriever);
    }

    public function testItReturnsNullWhenTrackerHasMoreThanOneChild(): void
    {
        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = UserTestBuilder::aUser()->build();
        $tracker = $taskboard_tracker->getTracker();
        self::assertInstanceOf(MockObject::class, $tracker);
        $tracker->method('getChildren')->willReturn(
            [
                TrackerTestBuilder::aTracker()->build(),
                TrackerTestBuilder::aTracker()->build(),
            ]
        );

        $add_in_place = $this->getRetriever()->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            new MappedFieldsCollection()
        );

        self::assertNull($add_in_place);
    }

    public function testItReturnsNullWhenTrackerHasNoChildren(): void
    {
        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = UserTestBuilder::aUser()->build();
        $tracker = $taskboard_tracker->getTracker();
        self::assertInstanceOf(MockObject::class, $tracker);
        $tracker->method('getChildren')->willReturn([]);

        $add_in_place = $this->getRetriever()->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            new MappedFieldsCollection()
        );

        self::assertNull($add_in_place);
    }

    public function testItReturnsNullWhenThereIsNoMappedFieldForChildTracker(): void
    {
        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = UserTestBuilder::aUser()->build();
        $tracker = $taskboard_tracker->getTracker();
        self::assertInstanceOf(MockObject::class, $tracker);

        $child_tracker = TrackerTestBuilder::aTracker()->withId(42)->build();
        $tracker->method('getChildren')->willReturn([$child_tracker]);

        $add_in_place = $this->getRetriever()->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            new MappedFieldsCollection()
        );

        self::assertNull($add_in_place);
    }

    public function testItReturnsNullWhenMappeFieldIsNotSubmitable(): void
    {
        $this->mapped_field->expects($this->once())->method('userCanSubmit')->willReturn(false);

        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = UserTestBuilder::aUser()->build();
        $tracker = $taskboard_tracker->getTracker();
        self::assertInstanceOf(MockObject::class, $tracker);

        $child_tracker = TrackerTestBuilder::aTracker()->withId(42)->build();
        $tracker->method('getChildren')->willReturn([$child_tracker]);

        $add_in_place = $this->getRetriever()->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            new MappedFieldsCollection([42 => $this->mapped_field])
        );

        self::assertNull($add_in_place);
    }

    public function testItReturnsNullWhenTitleSemanticIsNotDefinedOnChildTracker(): void
    {
        $this->mapped_field->expects($this->once())->method('userCanSubmit')->willReturn(true);

        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = UserTestBuilder::aUser()->build();
        $tracker = $taskboard_tracker->getTracker();
        self::assertInstanceOf(MockObject::class, $tracker);

        $child_tracker = TrackerTestBuilder::aTracker()->withId(42)->build();
        $tracker->method('getChildren')->willReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, false, false);

        $add_in_place = $this->getRetriever()->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            new MappedFieldsCollection([42 => $this->mapped_field])
        );

        self::assertNull($add_in_place);
    }

    public function testItReturnsNullWhenUserCannotUpdateFieldBoundToSemanticTitle(): void
    {
        $this->mapped_field->expects($this->once())->method('userCanSubmit')->willReturn(true);

        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = UserTestBuilder::aUser()->build();
        $tracker = $taskboard_tracker->getTracker();
        self::assertInstanceOf(MockObject::class, $tracker);

        $child_tracker = TrackerTestBuilder::aTracker()->withId(42)->build();
        $tracker->method('getChildren')->willReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, false);

        $add_in_place = $this->getRetriever()->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            new MappedFieldsCollection([42 => $this->mapped_field])
        );

        self::assertNull($add_in_place);
    }

    public function testItReturnsNullWhenSemanticTitleAndMappedFieldAreNotTheOnlyFieldRequiredAtArtifactSubmission(): void
    {
        $this->mapped_field->expects($this->once())->method('userCanSubmit')->willReturn(true);

        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = UserTestBuilder::aUser()->build();
        $tracker = $taskboard_tracker->getTracker();
        self::assertInstanceOf(MockObject::class, $tracker);

        $child_tracker = TrackerTestBuilder::aTracker()->withId(42)->build();
        $tracker->method('getChildren')->willReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, true);
        $this->mockTrackerFields($child_tracker, true, true, true);

        $add_in_place = $this->getRetriever()->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            new MappedFieldsCollection([42 => $this->mapped_field])
        );

        self::assertNull($add_in_place);
    }

    public function testItReturnsNullWhenParentTrackerDoesNotHaveAnArtifactLinkField(): void
    {
        $this->mapped_field->expects($this->once())->method('userCanSubmit')->willReturn(true);

        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = UserTestBuilder::aUser()->build();
        $tracker = $taskboard_tracker->getTracker();
        self::assertInstanceOf(MockObject::class, $tracker);

        $child_tracker = TrackerTestBuilder::aTracker()->withId(42)->build();
        $tracker->method('getChildren')->willReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, true);
        $this->mockTrackerFields($child_tracker, true, false, true);

        $this->form_element_factory
            ->expects($this->once())
            ->method('getAnArtifactLinkField')
            ->with($user, $tracker)
            ->willReturn(null);

        $add_in_place = $this->getRetriever()->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            new MappedFieldsCollection([42 => $this->mapped_field])
        );

        self::assertNull($add_in_place);
    }

    public function testItReturnsNullWhenParentTrackerDoesNotHaveAnUpdatableArtifactLinkField(): void
    {
        $this->mapped_field->expects($this->once())->method('userCanSubmit')->willReturn(true);

        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = UserTestBuilder::aUser()->build();
        $tracker = $taskboard_tracker->getTracker();
        self::assertInstanceOf(MockObject::class, $tracker);

        $child_tracker = TrackerTestBuilder::aTracker()->withId(42)->build();
        $tracker->method('getChildren')->willReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, true);
        $this->mockTrackerFields($child_tracker, true, false, true);

        $artifact_link_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class);
        $this->form_element_factory
            ->expects($this->once())
            ->method('getAnArtifactLinkField')
            ->with($user, $tracker)
            ->willReturn($artifact_link_field);
        $artifact_link_field
            ->expects($this->once())
            ->method('userCanUpdate')
            ->with($user)
            ->willReturn(false);

        $add_in_place = $this->getRetriever()->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            new MappedFieldsCollection([42 => $this->mapped_field])
        );

        self::assertNull($add_in_place);
    }

    public function testItReturnsAnAddInPlaceObject(): void
    {
        $this->mapped_field->expects($this->once())->method('userCanSubmit')->willReturn(true);

        $taskboard_tracker = $this->getTaskboardTracker();

        $user    = UserTestBuilder::aUser()->build();
        $tracker = $taskboard_tracker->getTracker();
        self::assertInstanceOf(MockObject::class, $tracker);

        $child_tracker = TrackerTestBuilder::aTracker()->withId(42)->build();
        $tracker->method('getChildren')->willReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, true);
        $this->mockTrackerFields($child_tracker, true, false, true);

        $artifact_link_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class);
        $this->form_element_factory
            ->expects($this->once())
            ->method('getAnArtifactLinkField')
            ->with($user, $tracker)
            ->willReturn($artifact_link_field);
        $artifact_link_field
            ->expects($this->once())
            ->method('userCanUpdate')
            ->with($user)
            ->willReturn(true);

        $this->form_element_factory
            ->method('getAnArtifactLinkField')
            ->willReturn(null);

        $add_in_place = $this->getRetriever()->retrieveAddInPlace(
            $taskboard_tracker,
            $user,
            new MappedFieldsCollection([42 => $this->mapped_field])
        );

        self::assertNotNull($add_in_place);
        self::assertSame($child_tracker, $add_in_place->getChildTracker());
        self::assertSame($artifact_link_field, $add_in_place->getParentArtifactLinkField());
    }

    private function mockSemanticTitle(\Tuleap\Tracker\Tracker $child_tracker, bool $is_set, bool $user_can_submit): void
    {
        if (! $is_set) {
            return;
        }
        $title_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\String\StringField::class);
        $title_field->method('userCanSubmit')->willReturn($user_can_submit);
        $title_field->method('getId')->willReturn(self::SEMANTIC_TITLE_FIELD_ID);
        $this->title_field_retriever = RetrieveSemanticTitleFieldStub::build()->withTitleField($child_tracker, $title_field);
    }

    private function getTaskboardTracker(): TaskboardTracker
    {
        $milestone_tracker = $this->createMock(Tracker::class);
        $tracker           = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(12);

        return new TaskboardTracker(
            $milestone_tracker,
            $tracker
        );
    }

    private function mockTrackerFields(
        \Tuleap\Tracker\Tracker $tracker,
        bool $is_title_field_required,
        bool $is_desc_field_required,
        bool $is_mapped_field_required,
    ): void {
        $title_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\String\StringField::class);
        $title_field->method('isRequired')->willReturn($is_title_field_required);
        $title_field->method('getId')->willReturn(self::SEMANTIC_TITLE_FIELD_ID);

        $desc_field = $this->createMock(\Tuleap\Tracker\FormElement\Field\Text\TextField::class);
        $desc_field->method('isRequired')->willReturn($is_desc_field_required);
        $desc_field->method('getId')->willReturn(1534);

        $this->mapped_field->method('isRequired')->willReturn($is_mapped_field_required);

        $this->form_element_factory->method('getUsedFields')->with($tracker)->willReturn(
            [$title_field, $desc_field, $this->mapped_field]
        );
    }
}
