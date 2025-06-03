<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Adapter\Document\Section\Artifact;

use Luracast\Restler\RestException;
use PFUser;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_Text;
use Tracker_Semantic_Description;
use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\Artifact\ArtifactContent;
use Tuleap\Artidoc\Domain\Document\Section\Level;
use Tuleap\Artidoc\Stubs\Document\RetrieveConfiguredTrackerStub;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Artifact\GetFileUploadDataStub;
use Tuleap\Tracker\Test\Stub\REST\Artifact\CreateArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactContentCreatorTest extends TestCase
{
    private const TITLE_ID       = 1001;
    private const DESCRIPTION_ID = 1002;
    private const FILES_ID       = 1002;

    private PFUser $user;
    private Tracker_FormElement_Field_String $readonly_title_field;
    private Tracker_FormElement_Field_String $submitatable_title_field;
    private Tracker_FormElement_Field_Text $readonly_description_field;
    private Tracker_FormElement_Field_Text $submitatable_description_field;
    private ArtidocWithContext $artidoc;
    private \Tracker $tracker;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithDefaults();

        $this->readonly_title_field     = $this->getStringField(self::TITLE_ID, false);
        $this->submitatable_title_field = $this->getStringField(self::TITLE_ID, true);

        $this->readonly_description_field     = $this->getTextField(self::DESCRIPTION_ID, false);
        $this->submitatable_description_field = $this->getTextField(self::DESCRIPTION_ID, true);

        $this->artidoc = new ArtidocWithContext(new ArtidocDocument(['item_id' => 123]));
        $this->tracker = TrackerTestBuilder::aTracker()
            ->withProject(
                ProjectTestBuilder::aProject()->build(),
            )->build();
    }

    protected function tearDown(): void
    {
        TrackerSemanticTitle::clearInstances();
        Tracker_Semantic_Description::clearInstances();
    }

    public function testFaultWhenDocumentDoesNotHaveATracker(): void
    {
        $create_artifact = CreateArtifactStub::shouldNotBeCalled();

        $creator = new ArtifactContentCreator(
            RetrieveConfiguredTrackerStub::withoutTracker(),
            GetFileUploadDataStub::withoutField(),
            $create_artifact,
            $this->user,
        );

        $result = $creator->createArtifact(
            $this->artidoc,
            new ArtifactContent(
                'Le title',
                'Le description',
                [],
                Level::One,
            )
        );

        self::assertTrue(Result::isErr($result));
        self::assertStringContainsString('Document #123 does not have a configured tracker', (string) $result->error);
        self::assertFalse($create_artifact->isCalled());
    }

    public function testFaultWhenNoTitleField(): void
    {
        $create_artifact = CreateArtifactStub::shouldNotBeCalled();

        TrackerSemanticTitle::setInstance(
            new TrackerSemanticTitle($this->tracker, null),
            $this->tracker,
        );

        $creator = new ArtifactContentCreator(
            RetrieveConfiguredTrackerStub::withTracker($this->tracker),
            GetFileUploadDataStub::withoutField(),
            $create_artifact,
            $this->user,
        );

        $result = $creator->createArtifact(
            $this->artidoc,
            new ArtifactContent(
                'Le title',
                'Le description',
                [],
                Level::One,
            )
        );

        self::assertTrue(Result::isErr($result));
        self::assertStringContainsString('There is no title field', (string) $result->error);
        self::assertFalse($create_artifact->isCalled());
    }

    public function testFaultWhenTitleFieldIsNotSubmittable(): void
    {
        $create_artifact = CreateArtifactStub::shouldNotBeCalled();

        TrackerSemanticTitle::setInstance(
            new TrackerSemanticTitle($this->tracker, $this->readonly_title_field),
            $this->tracker,
        );

        $creator = new ArtifactContentCreator(
            RetrieveConfiguredTrackerStub::withTracker($this->tracker),
            GetFileUploadDataStub::withoutField(),
            $create_artifact,
            $this->user,
        );

        $result = $creator->createArtifact(
            $this->artidoc,
            new ArtifactContent(
                'Le title',
                'Le description',
                [],
                Level::One,
            )
        );

        self::assertTrue(Result::isErr($result));
        self::assertStringContainsString('User cannot submit title', (string) $result->error);
        self::assertFalse($create_artifact->isCalled());
    }

    public function testFaultWhenNoDescriptionField(): void
    {
        $create_artifact = CreateArtifactStub::shouldNotBeCalled();

        TrackerSemanticTitle::setInstance(
            new TrackerSemanticTitle($this->tracker, $this->submitatable_title_field),
            $this->tracker,
        );
        Tracker_Semantic_Description::setInstance(
            new Tracker_Semantic_Description($this->tracker, null),
            $this->tracker,
        );

        $creator = new ArtifactContentCreator(
            RetrieveConfiguredTrackerStub::withTracker($this->tracker),
            GetFileUploadDataStub::withoutField(),
            $create_artifact,
            $this->user,
        );

        $result = $creator->createArtifact(
            $this->artidoc,
            new ArtifactContent(
                'Le title',
                'Le description',
                [],
                Level::One,
            )
        );

        self::assertTrue(Result::isErr($result));
        self::assertStringContainsString('There is no description field', (string) $result->error);
        self::assertFalse($create_artifact->isCalled());
    }

    public function testFaultWhenDescriptionFieldIsNotSubmittable(): void
    {
        $create_artifact = CreateArtifactStub::shouldNotBeCalled();

        TrackerSemanticTitle::setInstance(
            new TrackerSemanticTitle($this->tracker, $this->submitatable_title_field),
            $this->tracker,
        );
        Tracker_Semantic_Description::setInstance(
            new Tracker_Semantic_Description($this->tracker, $this->readonly_description_field),
            $this->tracker,
        );

        $creator = new ArtifactContentCreator(
            RetrieveConfiguredTrackerStub::withTracker($this->tracker),
            GetFileUploadDataStub::withoutField(),
            $create_artifact,
            $this->user,
        );

        $result = $creator->createArtifact(
            $this->artidoc,
            new ArtifactContent(
                'Le title',
                'Le description',
                [],
                Level::One,
            )
        );

        self::assertTrue(Result::isErr($result));
        self::assertStringContainsString('User cannot submit description', (string) $result->error);
        self::assertFalse($create_artifact->isCalled());
    }

    public function testItDoesNotCatchExceptionRaisedByPutHandler(): void
    {
        $create_artifact = CreateArtifactStub::withException();

        TrackerSemanticTitle::setInstance(
            new TrackerSemanticTitle($this->tracker, $this->submitatable_title_field),
            $this->tracker,
        );
        Tracker_Semantic_Description::setInstance(
            new Tracker_Semantic_Description($this->tracker, $this->submitatable_description_field),
            $this->tracker,
        );

        $creator = new ArtifactContentCreator(
            RetrieveConfiguredTrackerStub::withTracker($this->tracker),
            GetFileUploadDataStub::withoutField(),
            $create_artifact,
            $this->user,
        );

        $this->expectException(RestException::class);

        $creator->createArtifact(
            $this->artidoc,
            new ArtifactContent(
                'Le title',
                'Le description',
                [],
                Level::One,
            )
        );

        self::assertTrue($create_artifact->isCalled());
    }

    public function testHappyPath(): void
    {
        $create_artifact = CreateArtifactStub::withCreatedArtifact(
            ArtifactTestBuilder::anArtifact(123)->build(),
        );

        TrackerSemanticTitle::setInstance(
            new TrackerSemanticTitle($this->tracker, $this->submitatable_title_field),
            $this->tracker,
        );
        Tracker_Semantic_Description::setInstance(
            new Tracker_Semantic_Description($this->tracker, $this->submitatable_description_field),
            $this->tracker,
        );

        $creator = new ArtifactContentCreator(
            RetrieveConfiguredTrackerStub::withTracker($this->tracker),
            GetFileUploadDataStub::withoutField(),
            $create_artifact,
            $this->user,
        );

        $result = $creator->createArtifact(
            $this->artidoc,
            new ArtifactContent(
                'Le title',
                'Le description',
                [],
                Level::One,
            )
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame(123, $result->value);
        self::assertTrue($create_artifact->isCalled());
        $payload = $create_artifact->getPayload();
        self::assertNotNull($payload);
        self::assertCount(2, $payload);
        self::assertEquals(self::TITLE_ID, $payload[0]->field_id);
        self::assertEquals('Le title', $payload[0]->value);
        self::assertEquals(self::DESCRIPTION_ID, $payload[1]->field_id);
        self::assertEquals(
            [
                'content' => 'Le description',
                'format'  => 'html',
            ],
            $payload[1]->value,
        );
    }

    public function testHappyPathPayloadWhenTitleIsATextField(): void
    {
        $create_artifact = CreateArtifactStub::withCreatedArtifact(
            ArtifactTestBuilder::anArtifact(123)->build(),
        );

        TrackerSemanticTitle::setInstance(
            new TrackerSemanticTitle($this->tracker, $this->getTextField(self::TITLE_ID, true)),
            $this->tracker,
        );
        Tracker_Semantic_Description::setInstance(
            new Tracker_Semantic_Description($this->tracker, $this->submitatable_description_field),
            $this->tracker,
        );

        $creator = new ArtifactContentCreator(
            RetrieveConfiguredTrackerStub::withTracker($this->tracker),
            GetFileUploadDataStub::withoutField(),
            $create_artifact,
            $this->user,
        );

        $result = $creator->createArtifact(
            $this->artidoc,
            new ArtifactContent(
                'Le title',
                'Le description',
                [],
                Level::One,
            )
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame(123, $result->value);
        self::assertTrue($create_artifact->isCalled());
        $payload = $create_artifact->getPayload();
        self::assertNotNull($payload);
        self::assertCount(2, $payload);
        self::assertEquals(self::TITLE_ID, $payload[0]->field_id);
        self::assertEquals(
            [
                'content' => 'Le title',
                'format'  => 'text',
            ],
            $payload[0]->value,
        );
        self::assertEquals(self::DESCRIPTION_ID, $payload[1]->field_id);
        self::assertEquals(
            [
                'content' => 'Le description',
                'format'  => 'html',
            ],
            $payload[1]->value,
        );
    }

    public function testHappyPathPayloadWhenUpdatableAttachmentField(): void
    {
        $create_artifact = CreateArtifactStub::withCreatedArtifact(
            ArtifactTestBuilder::anArtifact(123)->build(),
        );

        TrackerSemanticTitle::setInstance(
            new TrackerSemanticTitle($this->tracker, $this->submitatable_title_field),
            $this->tracker,
        );
        Tracker_Semantic_Description::setInstance(
            new Tracker_Semantic_Description($this->tracker, $this->submitatable_description_field),
            $this->tracker,
        );

        $creator = new ArtifactContentCreator(
            RetrieveConfiguredTrackerStub::withTracker($this->tracker),
            GetFileUploadDataStub::withField(
                FileFieldBuilder::aFileField(self::FILES_ID)->build(),
            ),
            $create_artifact,
            $this->user,
        );

        $result = $creator->createArtifact(
            $this->artidoc,
            new ArtifactContent(
                'Le title',
                'Le description',
                [
                    123,
                    124,
                ],
                Level::One,
            )
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame(123, $result->value);
        self::assertTrue($create_artifact->isCalled());
        $payload = $create_artifact->getPayload();
        self::assertNotNull($payload);
        self::assertCount(3, $payload);
        self::assertEquals(self::TITLE_ID, $payload[0]->field_id);
        self::assertEquals('Le title', $payload[0]->value);
        self::assertEquals(self::DESCRIPTION_ID, $payload[1]->field_id);
        self::assertEquals(
            [
                'content' => 'Le description',
                'format'  => 'html',
            ],
            $payload[1]->value,
        );
        self::assertEquals(self::FILES_ID, $payload[2]->field_id);
        self::assertEquals([123, 124], $payload[2]->value);
    }

    private function getStringField(int $id, bool $submittable): Tracker_FormElement_Field_String
    {
        return StringFieldBuilder::aStringField($id)
            ->withSubmitPermission($this->user, $submittable)
            ->build();
    }

    private function getTextField(int $id, bool $submittable): Tracker_FormElement_Field_Text
    {
        return TextFieldBuilder::aTextField($id)
            ->withSubmitPermission($this->user, $submittable)
            ->build();
    }
}
