<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Tracker;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\Files\FilesField;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Artifact\GetFileUploadDataStub;
use Tuleap\Tracker\Test\Stub\Semantic\Description\RetrieveSemanticDescriptionFieldStub;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocumentTrackerRepresentationTest extends TestCase
{
    private const int TRACKER_ID = 101;
    private Tracker $tracker;

    #[\Override]
    protected function setUp(): void
    {
        $this->tracker = TrackerTestBuilder::aTracker()
            ->withId(self::TRACKER_ID)
            ->withName('Bugs')
            ->withProject(ProjectTestBuilder::aProject()->withId(132)->build())
            ->build();
    }

    public function testIdAndLabelAreExposed(): void
    {
        $representation = DocumentTrackerRepresentation::fromTracker(
            GetFileUploadDataStub::withoutField(),
            RetrieveSemanticTitleFieldStub::build(),
            RetrieveSemanticDescriptionFieldStub::build(),
            $this->tracker,
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertSame(self::TRACKER_ID, $representation->id);
        self::assertSame('Bugs', $representation->label);
    }

    public function testItExposesNullForTitleIfNoSemanticTitle(): void
    {
        $representation = DocumentTrackerRepresentation::fromTracker(
            GetFileUploadDataStub::withoutField(),
            RetrieveSemanticTitleFieldStub::build(),
            RetrieveSemanticDescriptionFieldStub::build(),
            $this->tracker,
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertNull($representation->title);
    }

    public function testItExposesNullForTitleFieldIfNotAStringField(): void
    {
        $representation = DocumentTrackerRepresentation::fromTracker(
            GetFileUploadDataStub::withoutField(),
            RetrieveSemanticTitleFieldStub::build()->withTitleField(TextFieldBuilder::aTextField(1004)->build()),
            RetrieveSemanticDescriptionFieldStub::build(),
            $this->tracker,
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertNull($representation->title);
    }

    public function testItExposesNullForTitleFieldIfNotSubmittable(): void
    {
        $representation = DocumentTrackerRepresentation::fromTracker(
            GetFileUploadDataStub::withoutField(),
            RetrieveSemanticTitleFieldStub::build()->withTitleField($this->getStringField(1004, false)),
            RetrieveSemanticDescriptionFieldStub::build(),
            $this->tracker,
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertNull($representation->title);
    }

    public function testItExposesTheTitleField(): void
    {
        $representation = DocumentTrackerRepresentation::fromTracker(
            GetFileUploadDataStub::withoutField(),
            RetrieveSemanticTitleFieldStub::build()->withTitleField($this->getStringField(1004, true)),
            RetrieveSemanticDescriptionFieldStub::build(),
            $this->tracker,
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertNotNull($representation->title);
        self::assertSame(1004, $representation->title->field_id);
        self::assertSame('A String Field', $representation->title->label);
    }

    public function testItExposesNullForDescriptionIfNoSemanticDescription(): void
    {
        $representation = DocumentTrackerRepresentation::fromTracker(
            GetFileUploadDataStub::withoutField(),
            RetrieveSemanticTitleFieldStub::build(),
            RetrieveSemanticDescriptionFieldStub::build(),
            $this->tracker,
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertNull($representation->description);
    }

    public function testItExposesNullForDescriptionFieldIfNotSubmittable(): void
    {
        $representation = DocumentTrackerRepresentation::fromTracker(
            GetFileUploadDataStub::withoutField(),
            RetrieveSemanticTitleFieldStub::build()->withTitleField($this->getStringField(1004, true)),
            RetrieveSemanticDescriptionFieldStub::build()->withDescriptionField($this->getTextField(1005, false)),
            $this->tracker,
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertNull($representation->description);
    }

    public function testItExposesTheDescriptionField(): void
    {
        $representation = DocumentTrackerRepresentation::fromTracker(
            GetFileUploadDataStub::withoutField(),
            RetrieveSemanticTitleFieldStub::build()->withTitleField($this->getStringField(1004, true)),
            RetrieveSemanticDescriptionFieldStub::build()->withDescriptionField($this->getTextField(1005, true)),
            $this->tracker,
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertNotNull($representation->description);
        self::assertSame(1005, $representation->description->field_id);
        self::assertSame('A Text Field', $representation->description->label);
    }

    public function testItExposesNullForFileFieldIfNoAttachmentField(): void
    {
        $representation = DocumentTrackerRepresentation::fromTracker(
            GetFileUploadDataStub::withoutField(),
            RetrieveSemanticTitleFieldStub::build()->withTitleField($this->getStringField(1004, true)),
            RetrieveSemanticDescriptionFieldStub::build()->withDescriptionField($this->getTextField(1005, true)),
            $this->tracker,
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertNull($representation->file);
    }

    public function testItExposesNullForFileFieldIfNotSubmittable(): void
    {
        $representation = DocumentTrackerRepresentation::fromTracker(
            GetFileUploadDataStub::withField($this->getFileField(1006, false)),
            RetrieveSemanticTitleFieldStub::build()->withTitleField($this->getStringField(1004, true)),
            RetrieveSemanticDescriptionFieldStub::build()->withDescriptionField($this->getTextField(1005, true)),
            $this->tracker,
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertNull($representation->file);
    }

    public function testItExposesTheFileUploadField(): void
    {
        $representation = DocumentTrackerRepresentation::fromTracker(
            GetFileUploadDataStub::withField($this->getFileField(1006, true)),
            RetrieveSemanticTitleFieldStub::build()->withTitleField($this->getStringField(1004, true)),
            RetrieveSemanticDescriptionFieldStub::build()->withDescriptionField($this->getTextField(1005, true)),
            $this->tracker,
            UserTestBuilder::buildWithDefaults(),
        );

        self::assertNotNull($representation->file);
        self::assertSame(1006, $representation->file->field_id);
        self::assertSame('A File Field', $representation->file->label);
    }

    private function getFileField(int $id, bool $submittable): FilesField
    {
        $field = $this->createMock(FilesField::class);
        $field->method('getId')->willReturn($id);
        $field->method('getLabel')->willReturn('A File Field');
        $field->method('userCanSubmit')->willReturn($submittable);

        return $field;
    }

    private function getStringField(int $id, bool $submittable): StringField
    {
        $field = $this->createMock(StringField::class);
        $field->method('getId')->willReturn($id);
        $field->method('getTrackerId')->willReturn(self::TRACKER_ID);
        $field->method('getLabel')->willReturn('A String Field');
        $field->method('userCanSubmit')->willReturn($submittable);
        $field->method('getDefaultRESTValue')->willReturn('');

        return $field;
    }

    private function getTextField(int $id, bool $submittable): TextField
    {
        $field = $this->createMock(TextField::class);
        $field->method('getId')->willReturn($id);
        $field->method('getTrackerId')->willReturn(self::TRACKER_ID);
        $field->method('getLabel')->willReturn('A Text Field');
        $field->method('userCanSubmit')->willReturn($submittable);
        $field->method('getDefaultRESTValue')->willReturn(['format' => 'html', 'content' => '']);

        return $field;
    }
}
