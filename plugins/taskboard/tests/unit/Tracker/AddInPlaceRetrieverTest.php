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
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\Test\Builders\Fields\ArtifactLinkFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AddInPlaceRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const int SEMANTIC_TITLE_FIELD_ID = 1533;

    private MockObject&\Tracker_FormElementFactory $form_element_factory;
    private \Tracker_FormElement_Field_Selectbox&MockObject $mapped_field;
    private RetrieveSemanticTitleFieldStub $title_field_retriever;
    private \PFUser $user;
    private MockObject&Tracker $card_tracker;
    private MappedFieldsCollection $mapped_fields_collection;

    #[\Override]
    protected function setUp(): void
    {
        $this->user         = UserTestBuilder::aUser()->build();
        $this->card_tracker = $this->createMock(Tracker::class);
        $this->card_tracker->method('getId')->willReturn(135);

        $this->form_element_factory  = $this->createMock(\Tracker_FormElementFactory::class);
        $this->mapped_field          = $this->createMock(\Tracker_FormElement_Field_Selectbox::class);
        $this->title_field_retriever = RetrieveSemanticTitleFieldStub::build();

        $this->mapped_field->method('getId')->willReturn(1001);
        $this->mapped_fields_collection = new MappedFieldsCollection([42 => $this->mapped_field]);
    }

    private function retrieve(): ?AddInPlace
    {
        $milestone_tracker = TrackerTestBuilder::aTracker()->withId(989)->build();

        return new AddInPlaceRetriever($this->form_element_factory, $this->title_field_retriever)
            ->retrieveAddInPlace(
                new TaskboardTracker($milestone_tracker, $this->card_tracker),
                $this->user,
                $this->mapped_fields_collection
            );
    }

    public function testItReturnsNullWhenTrackerHasMoreThanOneChild(): void
    {
        $this->card_tracker->method('getChildren')->willReturn(
            [
                TrackerTestBuilder::aTracker()->build(),
                TrackerTestBuilder::aTracker()->build(),
            ]
        );

        $add_in_place = $this->retrieve();
        self::assertNull($add_in_place);
    }

    public function testItReturnsNullWhenTrackerHasNoChildren(): void
    {
        $this->card_tracker->method('getChildren')->willReturn([]);

        $add_in_place = $this->retrieve();
        self::assertNull($add_in_place);
    }

    public function testItReturnsNullWhenThereIsNoMappedFieldForChildTracker(): void
    {
        $child_tracker = TrackerTestBuilder::aTracker()->withId(42)->build();
        $this->card_tracker->method('getChildren')->willReturn([$child_tracker]);
        $this->mapped_fields_collection = new MappedFieldsCollection();

        $add_in_place = $this->retrieve();
        self::assertNull($add_in_place);
    }

    public function testItReturnsNullWhenMappedFieldIsNotSubmittable(): void
    {
        $this->mapped_field->expects($this->once())->method('userCanSubmit')->willReturn(false);
        $child_tracker = TrackerTestBuilder::aTracker()->withId(42)->build();
        $this->card_tracker->method('getChildren')->willReturn([$child_tracker]);

        $add_in_place = $this->retrieve();
        self::assertNull($add_in_place);
    }

    public function testItReturnsNullWhenTitleSemanticIsNotDefinedOnChildTracker(): void
    {
        $this->mapped_field->expects($this->once())->method('userCanSubmit')->willReturn(true);
        $child_tracker = TrackerTestBuilder::aTracker()->withId(42)->build();
        $this->card_tracker->method('getChildren')->willReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, false, false);

        $add_in_place = $this->retrieve();
        self::assertNull($add_in_place);
    }

    public function testItReturnsNullWhenUserCannotUpdateFieldBoundToSemanticTitle(): void
    {
        $this->mapped_field->expects($this->once())->method('userCanSubmit')->willReturn(true);
        $child_tracker = TrackerTestBuilder::aTracker()->withId(42)->build();
        $this->card_tracker->method('getChildren')->willReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, false);

        $add_in_place = $this->retrieve();
        self::assertNull($add_in_place);
    }

    public function testItReturnsNullWhenSemanticTitleAndMappedFieldAreNotTheOnlyFieldRequiredAtArtifactSubmission(): void
    {
        $this->mapped_field->expects($this->once())->method('userCanSubmit')->willReturn(true);
        $child_tracker = TrackerTestBuilder::aTracker()->withId(42)->build();
        $this->card_tracker->method('getChildren')->willReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, true);
        $this->mockTrackerFields($child_tracker, true);

        $add_in_place = $this->retrieve();
        self::assertNull($add_in_place);
    }

    public function testItReturnsNullWhenParentTrackerDoesNotHaveAnArtifactLinkField(): void
    {
        $this->mapped_field->expects($this->once())->method('userCanSubmit')->willReturn(true);
        $child_tracker = TrackerTestBuilder::aTracker()->withId(42)->build();
        $this->card_tracker->method('getChildren')->willReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, true);
        $this->mockTrackerFields($child_tracker, false);

        $this->form_element_factory
            ->expects($this->once())
            ->method('getAnArtifactLinkField')
            ->with($this->user, $this->card_tracker)
            ->willReturn(null);

        $add_in_place = $this->retrieve();
        self::assertNull($add_in_place);
    }

    public function testItReturnsNullWhenParentTrackerDoesNotHaveAnUpdatableArtifactLinkField(): void
    {
        $this->mapped_field->expects($this->once())->method('userCanSubmit')->willReturn(true);
        $child_tracker = TrackerTestBuilder::aTracker()->withId(42)->build();
        $this->card_tracker->method('getChildren')->willReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, true);
        $this->mockTrackerFields($child_tracker, false);

        $artifact_link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(98)
            ->inTracker($this->card_tracker)
            ->withUpdatePermission($this->user, false)
            ->build();

        $this->form_element_factory
            ->expects($this->once())
            ->method('getAnArtifactLinkField')
            ->with($this->user, $this->card_tracker)
            ->willReturn($artifact_link_field);

        $add_in_place = $this->retrieve();
        self::assertNull($add_in_place);
    }

    public function testItReturnsAnAddInPlaceObject(): void
    {
        $this->mapped_field->expects($this->once())->method('userCanSubmit')->willReturn(true);
        $child_tracker = TrackerTestBuilder::aTracker()->withId(42)->build();
        $this->card_tracker->method('getChildren')->willReturn([$child_tracker]);

        $this->mockSemanticTitle($child_tracker, true, true);
        $this->mockTrackerFields($child_tracker, false);

        $artifact_link_field = ArtifactLinkFieldBuilder::anArtifactLinkField(98)
            ->inTracker($this->card_tracker)
            ->withUpdatePermission($this->user, true)
            ->build();

        $this->form_element_factory
            ->expects($this->once())
            ->method('getAnArtifactLinkField')
            ->with($this->user, $this->card_tracker)
            ->willReturn($artifact_link_field);

        $add_in_place = $this->retrieve();

        self::assertNotNull($add_in_place);
        self::assertSame($child_tracker, $add_in_place->getChildTracker());
        self::assertSame($artifact_link_field, $add_in_place->getParentArtifactLinkField());
    }

    private function mockSemanticTitle(Tracker $child_tracker, bool $is_set, bool $user_can_submit): void
    {
        if (! $is_set) {
            return;
        }
        $title_field = StringFieldBuilder::aStringField(self::SEMANTIC_TITLE_FIELD_ID)
            ->inTracker($child_tracker)
            ->withSubmitPermission($this->user, $user_can_submit)
            ->build();
        $this->title_field_retriever->withTitleField($title_field);
    }

    private function mockTrackerFields(
        Tracker $tracker,
        bool $is_desc_field_required,
    ): void {
        $title_field = StringFieldBuilder::aStringField(self::SEMANTIC_TITLE_FIELD_ID)
            ->inTracker($tracker)
            ->thatIsRequired()
            ->build();

        $desc_field = $this->createMock(TextField::class);
        $desc_field->method('isRequired')->willReturn($is_desc_field_required);
        $desc_field->method('getId')->willReturn(1534);

        $this->mapped_field->method('isRequired')->willReturn(true);

        $this->form_element_factory->method('getUsedFields')->with($tracker)->willReturn(
            [$title_field, $desc_field, $this->mapped_field]
        );
    }
}
