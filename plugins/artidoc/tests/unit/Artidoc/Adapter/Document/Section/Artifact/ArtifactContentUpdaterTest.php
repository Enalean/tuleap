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
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Artifact\ArtifactContent;
use Tuleap\Artidoc\Domain\Document\Section\Identifier\SectionIdentifier;
use Tuleap\Artidoc\Domain\Document\Section\Level;
use Tuleap\Artidoc\Stubs\Domain\Document\Section\UpdateLevelStub;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Artifact\GetFileUploadDataStub;
use Tuleap\Tracker\Test\Stub\REST\Artifact\HandlePUTStub;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;
use Tuleap\Tracker\Test\Stub\Semantic\Description\RetrieveSemanticDescriptionFieldStub;
use Tuleap\Tracker\Test\Stub\Semantic\Title\RetrieveSemanticTitleFieldStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactContentUpdaterTest extends TestCase
{
    private const ARTIFACT_ID    = 123;
    private const TITLE_ID       = 1001;
    private const DESCRIPTION_ID = 1002;
    private const FILES_ID       = 1002;

    private SectionIdentifier $section_identifier;
    private PFUser $user;
    private Tracker $tracker;
    private StringField $readonly_title_field;
    private StringField $updatable_title_field;
    private TextField $readonly_description_field;
    private TextField $updatable_description_field;
    private RetrieveArtifactStub $artifact_retriever;
    private GetFileUploadDataStub $file_upload_data_provider;
    private RetrieveSemanticDescriptionFieldStub $retrieve_description_field;
    private RetrieveSemanticTitleFieldStub $retrieve_title_field;

    #[\Override]
    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithDefaults();

        $this->section_identifier = (new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();

        $this->tracker = TrackerTestBuilder::aTracker()->withId(854)->build();

        $this->readonly_title_field  = $this->getStringField(self::TITLE_ID, false);
        $this->updatable_title_field = $this->getStringField(self::TITLE_ID, true);

        $this->readonly_description_field  = $this->getTextField(self::DESCRIPTION_ID, false);
        $this->updatable_description_field = $this->getTextField(self::DESCRIPTION_ID, true);

        $artifact = ArtifactTestBuilder::anArtifact(self::ARTIFACT_ID)->inTracker($this->tracker)
            ->userCanView($this->user)
            ->build();

        $this->artifact_retriever         = RetrieveArtifactStub::withArtifacts($artifact);
        $this->file_upload_data_provider  = GetFileUploadDataStub::withoutField();
        $this->retrieve_description_field = RetrieveSemanticDescriptionFieldStub::build()->withDescriptionField($this->updatable_description_field);
        $this->retrieve_title_field       = RetrieveSemanticTitleFieldStub::build()->withTitleField($this->tracker, $this->updatable_title_field);
    }

    private function getUpdater(UpdateLevelStub $update_level, HandlePUTStub $update_artifact): ArtifactContentUpdater
    {
        return new ArtifactContentUpdater(
            $this->artifact_retriever,
            $this->file_upload_data_provider,
            $update_level,
            $update_artifact,
            $this->retrieve_description_field,
            $this->retrieve_title_field,
            $this->user,
        );
    }

    public function testFaultWhenArtifactCannotBeFound(): void
    {
        $update_artifact          = HandlePUTStub::build();
        $update_level             = UpdateLevelStub::build();
        $this->artifact_retriever = RetrieveArtifactStub::withNoArtifact();

        $updater = $this->getUpdater($update_level, $update_artifact);

        $result = $updater->updateArtifactContent(
            $this->section_identifier,
            self::ARTIFACT_ID,
            new ArtifactContent(
                'Le title',
                'Le description',
                [],
                Level::One,
            )
        );

        self::assertTrue(Result::isErr($result));
        self::assertStringContainsString('User cannot update artifact', (string) $result->error);
        self::assertFalse($update_artifact->isCalled());
        self::assertFalse($update_level->isCalled());
    }

    public function testFaultWhenNoTitleField(): void
    {
        $update_artifact            = HandlePUTStub::build();
        $update_level               = UpdateLevelStub::build();
        $this->retrieve_title_field = RetrieveSemanticTitleFieldStub::build();

        $updater = $this->getUpdater($update_level, $update_artifact);

        $result = $updater->updateArtifactContent(
            $this->section_identifier,
            self::ARTIFACT_ID,
            new ArtifactContent(
                'Le title',
                'Le description',
                [],
                Level::One,
            )
        );

        self::assertTrue(Result::isErr($result));
        self::assertStringContainsString('There is no title field', (string) $result->error);
        self::assertFalse($update_artifact->isCalled());
        self::assertFalse($update_level->isCalled());
    }

    public function testFaultWhenTitleFieldIsNotUpdatable(): void
    {
        $update_artifact = HandlePUTStub::build();
        $update_level    = UpdateLevelStub::build();
        $this->retrieve_title_field->withTitleField($this->tracker, $this->readonly_title_field);

        $updater = $this->getUpdater($update_level, $update_artifact);

        $result = $updater->updateArtifactContent(
            $this->section_identifier,
            self::ARTIFACT_ID,
            new ArtifactContent(
                'Le title',
                'Le description',
                [],
                Level::One,
            )
        );

        self::assertTrue(Result::isErr($result));
        self::assertStringContainsString('User cannot update title', (string) $result->error);
        self::assertFalse($update_artifact->isCalled());
        self::assertFalse($update_level->isCalled());
    }

    public function testFaultWhenNoDescriptionField(): void
    {
        $update_artifact                  = HandlePUTStub::build();
        $update_level                     = UpdateLevelStub::build();
        $this->retrieve_description_field = RetrieveSemanticDescriptionFieldStub::build();

        $updater = $this->getUpdater($update_level, $update_artifact);

        $result = $updater->updateArtifactContent(
            $this->section_identifier,
            self::ARTIFACT_ID,
            new ArtifactContent(
                'Le title',
                'Le description',
                [],
                Level::One,
            )
        );

        self::assertTrue(Result::isErr($result));
        self::assertStringContainsString('There is no description field', (string) $result->error);
        self::assertFalse($update_artifact->isCalled());
        self::assertFalse($update_level->isCalled());
    }

    public function testFaultWhenDescriptionFieldIsNotUpdatable(): void
    {
        $update_artifact = HandlePUTStub::build();
        $update_level    = UpdateLevelStub::build();
        $this->retrieve_description_field->withDescriptionField($this->readonly_description_field);

        $updater = $this->getUpdater($update_level, $update_artifact);

        $result = $updater->updateArtifactContent(
            $this->section_identifier,
            self::ARTIFACT_ID,
            new ArtifactContent(
                'Le title',
                'Le description',
                [],
                Level::One,
            )
        );

        self::assertTrue(Result::isErr($result));
        self::assertStringContainsString('User cannot update description', (string) $result->error);
        self::assertFalse($update_artifact->isCalled());
        self::assertFalse($update_level->isCalled());
    }

    public function testItDoesNotCatchExceptionRaisedByPutHandler(): void
    {
        $update_artifact = HandlePUTStub::buildWithException();
        $update_level    = UpdateLevelStub::build();

        $updater = $this->getUpdater($update_level, $update_artifact);

        $this->expectException(RestException::class);

        $updater->updateArtifactContent(
            $this->section_identifier,
            self::ARTIFACT_ID,
            new ArtifactContent(
                'Le title',
                'Le description',
                [],
                Level::One,
            )
        );

        self::assertTrue($update_artifact->isCalled());
        self::assertFalse($update_level->isCalled());
    }

    public function testHappyPath(): void
    {
        $update_artifact = HandlePUTStub::build();
        $update_level    = UpdateLevelStub::build();

        $updater = $this->getUpdater($update_level, $update_artifact);

        $result = $updater->updateArtifactContent(
            $this->section_identifier,
            self::ARTIFACT_ID,
            new ArtifactContent(
                'Le title',
                'Le description',
                [],
                Level::One,
            )
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($update_artifact->isCalled());
        $payload = $update_artifact->getPayload();
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
        self::assertTrue($update_level->isCalled());
    }

    public function testHappyPathPayloadWhenTitleIsATextField(): void
    {
        $update_artifact = HandlePUTStub::build();
        $update_level    = UpdateLevelStub::build();
        $this->retrieve_title_field->withTitleField($this->tracker, $this->getTextField(self::TITLE_ID, true));

        $updater = $this->getUpdater($update_level, $update_artifact);

        $result = $updater->updateArtifactContent(
            $this->section_identifier,
            self::ARTIFACT_ID,
            new ArtifactContent(
                'Le title',
                'Le description',
                [],
                Level::One,
            )
        );

        self::assertTrue(Result::isOk($result));
        self::assertTrue($update_artifact->isCalled());
        $payload = $update_artifact->getPayload();
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
        self::assertTrue($update_level->isCalled());
    }

    public function testHappyPathPayloadWhenUpdatableAttachmentField(): void
    {
        $update_artifact                 = HandlePUTStub::build();
        $update_level                    = UpdateLevelStub::build();
        $this->file_upload_data_provider = GetFileUploadDataStub::withField(
            FileFieldBuilder::aFileField(self::FILES_ID)->build(),
        );

        $updater = $this->getUpdater($update_level, $update_artifact);

        $result = $updater->updateArtifactContent(
            $this->section_identifier,
            self::ARTIFACT_ID,
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
        self::assertTrue($update_artifact->isCalled());
        $payload = $update_artifact->getPayload();
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
        self::assertTrue($update_level->isCalled());
    }

    private function getStringField(int $id, bool $submittable): StringField
    {
        return StringFieldBuilder::aStringField($id)->inTracker($this->tracker)
            ->withUpdatePermission($this->user, $submittable)
            ->build();
    }

    private function getTextField(int $id, bool $submittable): TextField
    {
        return TextFieldBuilder::aTextField($id)->inTracker($this->tracker)
            ->withUpdatePermission($this->user, $submittable)
            ->build();
    }
}
