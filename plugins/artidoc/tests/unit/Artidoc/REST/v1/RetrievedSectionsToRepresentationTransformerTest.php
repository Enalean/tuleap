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

namespace Tuleap\Artidoc\REST\v1;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field_File;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_Text;
use Tracker_Semantic_Description;
use Tracker_Semantic_Title;
use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\Section\RequiredSectionInformationCollector;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\PaginatedRetrievedSections;
use Tuleap\Artidoc\Domain\Document\Section\RetrievedSection;
use Tuleap\Artidoc\Stubs\Document\FreetextIdentifierStub;
use Tuleap\Artidoc\Stubs\Document\SectionIdentifierStub;
use Tuleap\Artidoc\Stubs\REST\v1\BuildSectionFieldsStub;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\FileUploadDataProvider;
use Tuleap\Tracker\Artifact\UploadDataAttributesForRichTextEditorBuilder;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFileFullRepresentation;
use Tuleap\Tracker\REST\Artifact\FileInfoRepresentation;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueTextTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Artifact\GetFileUploadDataStub;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RetrievedSectionsToRepresentationTransformerTest extends TestCase
{
    private Tracker $tracker;
    private Tracker_Semantic_Title&MockObject $semantic_title;
    private Tracker_Semantic_Description&MockObject $semantic_description;

    protected function setUp(): void
    {
        $this->tracker = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->build())
            ->build();

        $this->semantic_title       = $this->createMock(Tracker_Semantic_Title::class);
        $this->semantic_description = $this->createMock(Tracker_Semantic_Description::class);

        Tracker_Semantic_Title::setInstance($this->semantic_title, $this->tracker);
        Tracker_Semantic_Description::setInstance($this->semantic_description, $this->tracker);
    }

    protected function tearDown(): void
    {
        Tracker_Semantic_Title::clearInstances();
    }

    private function getArtifact(
        int $id,
        Tracker_FormElement_Field_String $title,
        Tracker_FormElement_Field_Text $description,
        string $format,
        PFUser $user,
    ): Artifact {
        $artifact = ArtifactTestBuilder::anArtifact($id)
            ->inTracker($this->tracker)
            ->userCanView($user)
            ->build();

        $changeset = ChangesetTestBuilder::aChangeset(1000 + $id)
            ->ofArtifact($artifact)
            ->build();

        $this->setTitleValue($title, $changeset, $id);
        $this->setDescriptionValue($description, $changeset, $format, $id);

        $artifact->setChangesets([$changeset]);

        return $artifact;
    }

    private function getArtifactUserCannotView(int $id, PFUser $user): Artifact
    {
        return ArtifactTestBuilder::anArtifact($id)->userCannotView($user)->build();
    }

    private function setTitleValue(Tracker_FormElement_Field_String $title, Tracker_Artifact_Changeset $changeset, int $id): void
    {
        $changeset->setFieldValue(
            $title,
            ChangesetValueTextTestBuilder::aValue(1, $changeset, $title)->withValue("Title for {$id}")->build()
        );
    }

    private function setDescriptionValue(
        Tracker_FormElement_Field_Text $description,
        Tracker_Artifact_Changeset $changeset,
        string $format,
        int $id,
    ): void {
        $changeset->setFieldValue(
            $description,
            ChangesetValueTextTestBuilder::aValue(1, $changeset, $description)
                ->withFormat($format)
                ->withValue("Desc *for* {$id}")
                ->build()
        );
    }

    /**
     * @testWith [false, false, false]
     *           [false, true, false]
     *           [true, false, false]
     *           [true, true, true]
     */
    public function testHappyPath(
        bool $can_user_edit_title,
        bool $can_user_edit_description,
        bool $expected_can_user_edit_section,
    ): void {
        $user = UserTestBuilder::buildWithDefaults();

        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->withUpdatePermission($user, $can_user_edit_title)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->withUpdatePermission($user, $can_user_edit_description)
            ->build();
        $this->semantic_description->method('getField')->willReturn($description);

        $file = $this->createMock(Tracker_FormElement_Field_File::class);
        $file->method('getId')->willReturn(600);
        $file->method('getLabel')->willReturn('Attachments');

        $art1 = $this->getArtifact(1, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art2 = $this->getArtifact(2, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art3 = $this->getArtifact(3, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art4 = $this->getArtifact(4, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);

        $file_upload_provider = GetFileUploadDataStub::withField($file);

        $file->method('getRESTValue')
            ->willReturnCallback(fn(PFUser $user, Tracker_Artifact_Changeset $changeset) => match ($changeset) {
                $art1->getLastChangeset() => $this->getFileValue($file, $art1),
                $art2->getLastChangeset() => $this->getFileValue($file, $art2),
                $art3->getLastChangeset() => $this->getFileValue($file, $art3),
                $art4->getLastChangeset() => $this->getFileValue($file, $art4),
            });

        $transformer = new RetrievedSectionsToRepresentationTransformer(
            new SectionRepresentationBuilder(
                new ArtifactSectionRepresentationBuilder($file_upload_provider, BuildSectionFieldsStub::withoutFields()),
            ),
            new RequiredSectionInformationCollector(
                $user,
                new RequiredArtifactInformationBuilder(RetrieveArtifactStub::withArtifacts($art1, $art2, $art3, $art4))
            ),
        );
        $result      = $transformer->getRepresentation(
            new PaginatedRetrievedSections(
                new ArtidocWithContext(new ArtidocDocument(['item_id' => 101])),
                [
                    RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 2, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 3, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 2, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 4, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 3, 'level' => 1]),
                ],
                10,
            ),
            $user
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame(10, $result->value->total);
        self::assertCount(4, $result->value->sections);

        $expected = [
            ['id' => 1, 'title' => 'Title for 1', 'description' => 'Desc *for* 1', 'can_user_edit_section' => $expected_can_user_edit_section],
            ['id' => 2, 'title' => 'Title for 2', 'description' => 'Desc *for* 2', 'can_user_edit_section' => $expected_can_user_edit_section],
            ['id' => 3, 'title' => 'Title for 3', 'description' => 'Desc *for* 3', 'can_user_edit_section' => $expected_can_user_edit_section],
            ['id' => 4, 'title' => 'Title for 4', 'description' => 'Desc *for* 4', 'can_user_edit_section' => $expected_can_user_edit_section],
        ];

        array_walk(
            $expected,
            static function (array $expected, int $index) use ($result) {
                self::assertInstanceOf(ArtifactSectionRepresentation::class, $result->value->sections[$index]);
                self::assertSame($expected['id'], $result->value->sections[$index]->artifact->id);
                self::assertSame($expected['title'], $result->value->sections[$index]->title);
                self::assertSame($expected['description'], $result->value->sections[$index]->description);
                self::assertSame($expected['can_user_edit_section'], $result->value->sections[$index]->can_user_edit_section);
                self::assertInstanceOf(ArtifactSectionAttachmentsRepresentation::class, $result->value->sections[$index]->attachments);
            }
        );
    }

    public function testHappyPathWithDescriptionInMarkdownFormat(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->withUpdatePermission($user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->withUpdatePermission($user, true)
            ->build();
        $this->semantic_description->method('getField')->willReturn($description);

        $file = $this->createMock(Tracker_FormElement_Field_File::class);
        $file->method('getId')->willReturn(600);
        $file->method('getLabel')->willReturn('Attachments');

        $art1 = $this->getArtifact(1, $title, $description, \Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT, $user);

        $file_upload_provider = GetFileUploadDataStub::withField($file);

        $file->method('getRESTValue')
            ->willReturnCallback(fn(PFUser $user, Tracker_Artifact_Changeset $changeset) => match ($changeset) {
                $art1->getLastChangeset() => $this->getFileValue($file, $art1),
            });

        $transformer = new RetrievedSectionsToRepresentationTransformer(
            new SectionRepresentationBuilder(
                new ArtifactSectionRepresentationBuilder($file_upload_provider, BuildSectionFieldsStub::withoutFields()),
            ),
            new RequiredSectionInformationCollector(
                $user,
                new RequiredArtifactInformationBuilder(RetrieveArtifactStub::withArtifacts($art1))
            ),
        );
        $result      = $transformer->getRepresentation(
            new PaginatedRetrievedSections(
                new ArtidocWithContext(new ArtidocDocument(['item_id' => 101])),
                [
                    RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1, 'level' => 1]),
                ],
                10,
            ),
            $user
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame(10, $result->value->total);
        self::assertCount(1, $result->value->sections);

        $second = $result->value->sections[0];
        self::assertInstanceOf(ArtifactSectionRepresentation::class, $second);
        self::assertSame(1, $second->artifact->id);
        self::assertSame("<p>Desc <em>for</em> 1</p>\n", $second->description);
    }

    public function testHappyPathWithDescriptionInTextFormat(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->withUpdatePermission($user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->withUpdatePermission($user, true)
            ->build();
        $this->semantic_description->method('getField')->willReturn($description);

        $file = $this->createMock(Tracker_FormElement_Field_File::class);
        $file->method('getId')->willReturn(600);
        $file->method('getLabel')->willReturn('Attachments');

        $art1 = $this->getArtifact(1, $title, $description, \Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT, $user);

        $file_upload_provider = GetFileUploadDataStub::withField($file);

        $file->method('getRESTValue')
            ->willReturnCallback(fn(PFUser $user, Tracker_Artifact_Changeset $changeset) => match ($changeset) {
                $art1->getLastChangeset() => $this->getFileValue($file, $art1),
            });

        $transformer = new RetrievedSectionsToRepresentationTransformer(
            new SectionRepresentationBuilder(
                new ArtifactSectionRepresentationBuilder($file_upload_provider, BuildSectionFieldsStub::withoutFields()),
            ),
            new RequiredSectionInformationCollector(
                $user,
                new RequiredArtifactInformationBuilder(RetrieveArtifactStub::withArtifacts($art1))
            ),
        );
        $result      = $transformer->getRepresentation(
            new PaginatedRetrievedSections(
                new ArtidocWithContext(new ArtidocDocument(['item_id' => 101])),
                [
                    RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1, 'level' => 1]),
                ],
                10,
            ),
            $user
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame(10, $result->value->total);
        self::assertCount(1, $result->value->sections);

        $second = $result->value->sections[0];
        self::assertInstanceOf(ArtifactSectionRepresentation::class, $second);
        self::assertSame(1, $second->artifact->id);
        self::assertSame("<p>Desc <em>for</em> 1</p>\n", $second->description);
    }

    public function testHappyPathWithFreetext(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->withUpdatePermission($user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->withUpdatePermission($user, true)
            ->build();
        $this->semantic_description->method('getField')->willReturn($description);

        $file = $this->createMock(Tracker_FormElement_Field_File::class);
        $file->method('getId')->willReturn(600);
        $file->method('getLabel')->willReturn('Attachments');

        $art1 = $this->getArtifact(1, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);

        $file_upload_provider = GetFileUploadDataStub::withField($file);

        $file->method('getRESTValue')
            ->willReturnCallback(fn(PFUser $user, Tracker_Artifact_Changeset $changeset) => match ($changeset) {
                $art1->getLastChangeset() => $this->getFileValue($file, $art1),
            });

        $transformer = new RetrievedSectionsToRepresentationTransformer(
            new SectionRepresentationBuilder(
                new ArtifactSectionRepresentationBuilder($file_upload_provider, BuildSectionFieldsStub::withoutFields()),
            ),
            new RequiredSectionInformationCollector(
                $user,
                new RequiredArtifactInformationBuilder(RetrieveArtifactStub::withArtifacts($art1))
            ),
        );
        $result      = $transformer->getRepresentation(
            new PaginatedRetrievedSections(
                new ArtidocWithContext(new ArtidocDocument(['item_id' => 101])),
                [
                    RetrievedSection::fromFreetext([
                        'freetext_title'       => 'Requirements',
                        'freetext_description' => 'Lorem ipsum',
                        'freetext_id'          => FreetextIdentifierStub::create(),
                        'id'                   => SectionIdentifierStub::create(),
                        'item_id'              => 101,
                        'rank'                 => 0,
                        'level' => 1,
                    ]),
                    RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1, 'level' => 1]),
                ],
                10,
            ),
            $user
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame(10, $result->value->total);
        self::assertCount(2, $result->value->sections);

        $first = $result->value->sections[0];
        self::assertInstanceOf(FreetextSectionRepresentation::class, $first);
        self::assertSame('Requirements', $first->title);
        self::assertSame('Lorem ipsum', $first->description);

        $second = $result->value->sections[1];
        self::assertInstanceOf(ArtifactSectionRepresentation::class, $second);
        self::assertSame(1, $second->artifact->id);
        self::assertSame('Title for 1', $second->title);
    }

    public function testArtifactHasEmptyAttachmentFieldThatHasBeenCreatedAfterArtifactCreation(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->withUpdatePermission($user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->withUpdatePermission($user, true)
            ->build();
        $this->semantic_description->method('getField')->willReturn($description);

        $file = $this->createMock(Tracker_FormElement_Field_File::class);
        $file->method('getId')->willReturn(600);
        $file->method('getLabel')->willReturn('Attachments');

        $art1 = $this->getArtifact(1, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);

        $file_upload_provider = GetFileUploadDataStub::withField($file);

        $file->method('getRESTValue')->willReturn(null);

        $transformer = new RetrievedSectionsToRepresentationTransformer(
            new SectionRepresentationBuilder(
                new ArtifactSectionRepresentationBuilder($file_upload_provider, BuildSectionFieldsStub::withoutFields()),
            ),
            new RequiredSectionInformationCollector(
                $user,
                new RequiredArtifactInformationBuilder(RetrieveArtifactStub::withArtifacts($art1))
            ),
        );
        $result      = $transformer->getRepresentation(
            new PaginatedRetrievedSections(
                new ArtidocWithContext(new ArtidocDocument(['item_id' => 101])),
                [
                    RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
                ],
                10,
            ),
            $user
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame(10, $result->value->total);
        self::assertCount(1, $result->value->sections);
        self::assertInstanceOf(ArtifactSectionRepresentation::class, $result->value->sections[0]);
        self::assertInstanceOf(ArtifactSectionAttachmentsRepresentation::class, $result->value->sections[0]->attachments);
    }

    public function testArtifactHasNoAttachmentField(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->withUpdatePermission($user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->withUpdatePermission($user, true)
            ->build();
        $this->semantic_description->method('getField')->willReturn($description);

        $art1 = $this->getArtifact(1, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);

        $file_upload_provider = GetFileUploadDataStub::withoutField();

        $editor_builder = $this->createMock(UploadDataAttributesForRichTextEditorBuilder::class);
        $editor_builder->method('getDataAttributes')->willReturn([]);

        $transformer = new RetrievedSectionsToRepresentationTransformer(
            new SectionRepresentationBuilder(
                new ArtifactSectionRepresentationBuilder($file_upload_provider, BuildSectionFieldsStub::withoutFields()),
            ),
            new RequiredSectionInformationCollector(
                $user,
                new RequiredArtifactInformationBuilder(RetrieveArtifactStub::withArtifacts($art1))
            ),
        );
        $result      = $transformer->getRepresentation(
            new PaginatedRetrievedSections(
                new ArtidocWithContext(new ArtidocDocument(['item_id' => 101])),
                [
                    RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
                ],
                10,
            ),
            $user
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame(10, $result->value->total);
        self::assertCount(1, $result->value->sections);

        $expected = [
            ['id' => 1, 'title' => 'Title for 1', 'description' => 'Desc *for* 1', 'can_user_edit_section' => true],
        ];

        array_walk(
            $expected,
            static function (array $expected, int $index) use ($result) {
                self::assertInstanceOf(ArtifactSectionRepresentation::class, $result->value->sections[$index]);
                self::assertSame($expected['id'], $result->value->sections[$index]->artifact->id);
                self::assertSame($expected['title'], $result->value->sections[$index]->title);
                self::assertSame($expected['description'], $result->value->sections[$index]->description);
                self::assertSame($expected['can_user_edit_section'], $result->value->sections[$index]->can_user_edit_section);
                self::assertnull($result->value->sections[$index]->attachments);
            }
        );
    }

    public function testWhenOrderOfArtifactRowsDoesNotMatchOrderOfGivenArtifactIds(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->withUpdatePermission($user, false)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->withUpdatePermission($user, false)
            ->build();
        $this->semantic_description->method('getField')->willReturn($description);

        $art1 = $this->getArtifact(1, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art2 = $this->getArtifact(2, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art3 = $this->getArtifact(3, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art4 = $this->getArtifact(4, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);

        $file_upload_provider = $this->createMock(FileUploadDataProvider::class);
        $file_upload_provider->method('getFileUploadData')->willReturn(
            null
        );

        $editor_builder = $this->createMock(UploadDataAttributesForRichTextEditorBuilder::class);
        $editor_builder->method('getDataAttributes')->willReturn([]);

        $transformer = new RetrievedSectionsToRepresentationTransformer(
            new SectionRepresentationBuilder(
                new ArtifactSectionRepresentationBuilder($file_upload_provider, BuildSectionFieldsStub::withoutFields()),
            ),
            new RequiredSectionInformationCollector(
                $user,
                new RequiredArtifactInformationBuilder(RetrieveArtifactStub::withArtifacts($art1, $art2, $art3, $art4))
            ),
        );
        $result      = $transformer->getRepresentation(
            new PaginatedRetrievedSections(
                new ArtidocWithContext(new ArtidocDocument(['item_id' => 101])),
                [
                    RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 2, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 3, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 2, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 4, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 3, 'level' => 1]),
                ],
                10,
            ),
            $user
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame(10, $result->value->total);
        self::assertCount(4, $result->value->sections);

        $expected = [
            ['id' => 1, 'title' => 'Title for 1', 'description' => 'Desc *for* 1'],
            ['id' => 2, 'title' => 'Title for 2', 'description' => 'Desc *for* 2'],
            ['id' => 3, 'title' => 'Title for 3', 'description' => 'Desc *for* 3'],
            ['id' => 4, 'title' => 'Title for 4', 'description' => 'Desc *for* 4'],
        ];
        array_walk(
            $expected,
            static function (array $expected, int $index) use ($result) {
                self::assertInstanceOf(ArtifactSectionRepresentation::class, $result->value->sections[$index]);
                self::assertSame($expected['id'], $result->value->sections[$index]->artifact->id);
                self::assertSame($expected['title'], $result->value->sections[$index]->title);
                self::assertSame($expected['description'], $result->value->sections[$index]->description);
            }
        );
    }

    public function testWhenTitleSemanticIsNotSet(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn(null);

        $description = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->build();
        $this->semantic_description->method('getField')->willReturn($description);

        $art1 = $this->getArtifact(1, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art2 = $this->getArtifact(2, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art3 = $this->getArtifact(3, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art4 = $this->getArtifact(4, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);

        $file_upload_provider = $this->createMock(FileUploadDataProvider::class);
        $file_upload_provider->method('getFileUploadData')->willReturn(
            null
        );
        $transformer = new RetrievedSectionsToRepresentationTransformer(
            new SectionRepresentationBuilder(
                new ArtifactSectionRepresentationBuilder($file_upload_provider, BuildSectionFieldsStub::withoutFields()),
            ),
            new RequiredSectionInformationCollector(
                $user,
                new RequiredArtifactInformationBuilder(RetrieveArtifactStub::withArtifacts($art1, $art2, $art3, $art4))
            ),
        );
        $result      = $transformer->getRepresentation(
            new PaginatedRetrievedSections(
                new ArtidocWithContext(new ArtidocDocument(['item_id' => 101])),
                [
                    RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 2, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 3, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 2, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 4, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 3, 'level' => 1]),
                ],
                10,
            ),
            $user
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testWhenTitleSemanticIsNotReadableByCurrentUser(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($user, false)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->build();
        $this->semantic_description->method('getField')->willReturn($description);

        $art1 = $this->getArtifact(1, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art2 = $this->getArtifact(2, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art3 = $this->getArtifact(3, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art4 = $this->getArtifact(4, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);

        $file_upload_provider = $this->createMock(FileUploadDataProvider::class);
        $file_upload_provider->method('getFileUploadData')->willReturn(
            null
        );
        $transformer = new RetrievedSectionsToRepresentationTransformer(
            new SectionRepresentationBuilder(
                new ArtifactSectionRepresentationBuilder($file_upload_provider, BuildSectionFieldsStub::withoutFields()),
            ),
            new RequiredSectionInformationCollector(
                $user,
                new RequiredArtifactInformationBuilder(RetrieveArtifactStub::withArtifacts($art1, $art2, $art3, $art4))
            ),
        );
        $result      = $transformer->getRepresentation(
            new PaginatedRetrievedSections(
                new ArtidocWithContext(new ArtidocDocument(['item_id' => 101])),
                [
                    RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 2, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 3, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 2, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 4, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 3, 'level' => 1]),
                ],
                10,
            ),
            $user
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testWhenDescriptionSemanticIsNotReadableByCurrentUser(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($user, false)
            ->build();
        $this->semantic_description->method('getField')->willReturn($description);

        $art1 = $this->getArtifact(1, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art2 = $this->getArtifact(2, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art3 = $this->getArtifact(3, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art4 = $this->getArtifact(4, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);

        $file_upload_provider = $this->createMock(FileUploadDataProvider::class);
        $file_upload_provider->method('getFileUploadData')->willReturn(
            null
        );
        $transformer = new RetrievedSectionsToRepresentationTransformer(
            new SectionRepresentationBuilder(
                new ArtifactSectionRepresentationBuilder($file_upload_provider, BuildSectionFieldsStub::withoutFields()),
            ),
            new RequiredSectionInformationCollector(
                $user,
                new RequiredArtifactInformationBuilder(RetrieveArtifactStub::withArtifacts($art1, $art2, $art3, $art4))
            ),
        );
        $result      = $transformer->getRepresentation(
            new PaginatedRetrievedSections(
                new ArtidocWithContext(new ArtidocDocument(['item_id' => 101])),
                [
                    RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 2, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 3, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 2, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 4, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 3, 'level' => 1]),
                ],
                10,
            ),
            $user
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testWhenDescriptionSemanticIsNotSet(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->build();
        $this->semantic_description->method('getField')->willReturn(null);

        $art1 = $this->getArtifact(1, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art2 = $this->getArtifact(2, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art3 = $this->getArtifact(3, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art4 = $this->getArtifact(4, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);

        $file_upload_provider = $this->createMock(FileUploadDataProvider::class);
        $file_upload_provider->method('getFileUploadData')->willReturn(
            null
        );
        $transformer = new RetrievedSectionsToRepresentationTransformer(
            new SectionRepresentationBuilder(
                new ArtifactSectionRepresentationBuilder($file_upload_provider, BuildSectionFieldsStub::withoutFields()),
            ),
            new RequiredSectionInformationCollector(
                $user,
                new RequiredArtifactInformationBuilder(RetrieveArtifactStub::withArtifacts($art1, $art2, $art3, $art4))
            ),
        );
        $result      = $transformer->getRepresentation(
            new PaginatedRetrievedSections(
                new ArtidocWithContext(new ArtidocDocument(['item_id' => 101])),
                [
                    RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 2, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 3, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 2, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 4, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 3, 'level' => 1]),
                ],
                10,
            ),
            $user
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testFaultWhenThereIsAtLeastOneArtifactThatCurrentUserCannotRead(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($user, true)
            ->build();
        $this->semantic_description->method('getField')->willReturn($description);

        $art1 = $this->getArtifact(1, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art2 = $this->getArtifact(2, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);
        $art3 = $this->getArtifactUserCannotView(3, $user);
        $art4 = $this->getArtifact(4, $title, $description, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT, $user);

        $file_upload_provider = $this->createMock(FileUploadDataProvider::class);
        $file_upload_provider->method('getFileUploadData')->willReturn(
            null
        );
        $transformer = new RetrievedSectionsToRepresentationTransformer(
            new SectionRepresentationBuilder(
                new ArtifactSectionRepresentationBuilder($file_upload_provider, BuildSectionFieldsStub::withoutFields()),
            ),
            new RequiredSectionInformationCollector(
                $user,
                new RequiredArtifactInformationBuilder(RetrieveArtifactStub::withArtifacts($art1, $art2, $art3, $art4))
            ),
        );
        $result      = $transformer->getRepresentation(
            new PaginatedRetrievedSections(
                new ArtidocWithContext(new ArtidocDocument(['item_id' => 101])),
                [
                    RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 2, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 3, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 2, 'level' => 1]),
                    RetrievedSection::fromArtifact(['artifact_id' => 4, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 3, 'level' => 1]),
                ],
                10,
            ),
            $user
        );

        self::assertTrue(Result::isErr($result));
    }

    public function testWhenThereIsNotAnyArtifacts(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $file_upload_provider = $this->createMock(FileUploadDataProvider::class);
        $file_upload_provider->method('getFileUploadData')->willReturn(
            null
        );
        $transformer = new RetrievedSectionsToRepresentationTransformer(
            new SectionRepresentationBuilder(
                new ArtifactSectionRepresentationBuilder($file_upload_provider, BuildSectionFieldsStub::withoutFields()),
            ),
            new RequiredSectionInformationCollector(
                $user,
                new RequiredArtifactInformationBuilder(RetrieveArtifactStub::withNoArtifact())
            ),
        );
        $result      = $transformer->getRepresentation(
            new PaginatedRetrievedSections(
                new ArtidocWithContext(new ArtidocDocument(['item_id' => 101])),
                [],
                10,
            ),
            $user
        );

        self::assertTrue(Result::isOk($result));
        self::assertSame(10, $result->value->total);
        self::assertCount(0, $result->value->sections);
    }

    private function getFileValue(Tracker_FormElement_Field_File $field, Artifact $artifact): ArtifactFieldValueFileFullRepresentation
    {
        return ArtifactFieldValueFileFullRepresentation::fromValues($field, [
            new FileInfoRepresentation(
                100 + $artifact->getId(),
                101,
                '',
                'toto.gif',
                123,
                'image/webp',
                '/path/to/image.png',
                '/preview/image.png',
            ),
        ]);
    }
}
