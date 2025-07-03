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
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field_File;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_Text;
use Tuleap\Artidoc\Adapter\Document\ArtidocDocument;
use Tuleap\Artidoc\Adapter\Document\Section\RequiredSectionInformationCollector;
use Tuleap\Artidoc\Document\Field\ConfiguredFieldCollection;
use Tuleap\Artidoc\Document\Field\FieldsWithValuesBuilder;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\PaginatedRetrievedSections;
use Tuleap\Artidoc\Domain\Document\Section\RetrievedSection;
use Tuleap\Artidoc\REST\v1\ArtifactSection\ArtifactSectionAttachmentsRepresentation;
use Tuleap\Artidoc\REST\v1\ArtifactSection\ArtifactSectionRepresentation;
use Tuleap\Artidoc\REST\v1\ArtifactSection\ArtifactSectionRepresentationBuilder;
use Tuleap\Artidoc\REST\v1\ArtifactSection\RequiredArtifactInformationBuilder;
use Tuleap\Artidoc\Stubs\Document\Field\List\BuildListFieldWithValueStub;
use Tuleap\Artidoc\Stubs\Document\FreetextIdentifierStub;
use Tuleap\Artidoc\Stubs\Document\SectionIdentifierStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\UploadDataAttributesForRichTextEditorBuilder;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFileFullRepresentation;
use Tuleap\Tracker\REST\Artifact\FileInfoRepresentation;
use Tuleap\Tracker\Semantic\Description\RetrieveSemanticDescriptionField;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueTextTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Artifact\GetFileUploadDataStub;
use Tuleap\Tracker\Test\Stub\RetrieveArtifactStub;
use Tuleap\Tracker\Test\Stub\RetrieveSemanticDescriptionFieldStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RetrievedSectionsToRepresentationTransformerTest extends TestCase
{
    private PFUser $user;
    private Tracker $tracker;
    private TrackerSemanticTitle&MockObject $semantic_title;
    private RetrieveArtifactStub $artifact_retriever;
    private GetFileUploadDataStub $file_upload_provider;

    protected function setUp(): void
    {
        $this->user    = UserTestBuilder::buildWithDefaults();
        $this->tracker = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->build())
            ->build();

        $this->semantic_title = $this->createMock(TrackerSemanticTitle::class);

        TrackerSemanticTitle::setInstance($this->semantic_title, $this->tracker);
        $this->artifact_retriever   = RetrieveArtifactStub::withNoArtifact();
        $this->file_upload_provider = GetFileUploadDataStub::withoutField();
    }

    protected function tearDown(): void
    {
        TrackerSemanticTitle::clearInstances();
    }

    /**
     * @param list<RetrievedSection> $sections
     * @return Ok<PaginatedArtidocSectionRepresentationCollection>|Err<Fault>
     */
    private function getRepresentation(array $sections, RetrieveSemanticDescriptionField $retrieve_description_field): Ok|Err
    {
        $transformer = new RetrievedSectionsToRepresentationTransformer(
            new SectionRepresentationBuilder(
                new ArtifactSectionRepresentationBuilder(
                    $this->file_upload_provider,
                    new FieldsWithValuesBuilder(
                        new ConfiguredFieldCollection([]),
                        BuildListFieldWithValueStub::withCallback(
                            static function () {
                                throw new \Exception('This test was not supposed to build list fields.');
                            },
                        ),
                    )
                ),
            ),
            new RequiredSectionInformationCollector(
                $this->user,
                new RequiredArtifactInformationBuilder(
                    $this->artifact_retriever,
                    $retrieve_description_field,
                ),
            ),
        );
        return $transformer->getRepresentation(
            new PaginatedRetrievedSections(
                new ArtidocWithContext(new ArtidocDocument(['item_id' => 101])),
                $sections,
                10,
            ),
            $this->user
        );
    }

    /**
     * @psalm-param \Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT|\Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT|\Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT $format
     */
    private function getArtifact(
        int $id,
        Tracker_FormElement_Field_String $title,
        Tracker_FormElement_Field_Text $description_field,
        string $format,
    ): Artifact {
        $artifact = ArtifactTestBuilder::anArtifact($id)
            ->inTracker($this->tracker)
            ->userCanView($this->user)
            ->build();

        $changeset = ChangesetTestBuilder::aChangeset(1000 + $id)
            ->ofArtifact($artifact)
            ->build();

        $this->setTitleValue($title, $changeset, $id);
        $changeset->setFieldValue(
            $description_field,
            ChangesetValueTextTestBuilder::aValue(1, $changeset, $description_field)
                ->withValue("Desc *for* $id", $format)
                ->build()
        );

        $artifact->setChangesets([$changeset]);

        return $artifact;
    }

    private function getArtifactUserCannotView(int $id): Artifact
    {
        return ArtifactTestBuilder::anArtifact($id)->userCannotView($this->user)->build();
    }

    private function setTitleValue(Tracker_FormElement_Field_String $title, Tracker_Artifact_Changeset $changeset, int $id): void
    {
        $changeset->setFieldValue(
            $title,
            ChangesetValueTextTestBuilder::aValue(1, $changeset, $title)
                ->withValue("Title for {$id}", \Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT)
                ->build()
        );
    }

    #[TestWith([false, false, false])]
    #[TestWith([false, true, false])]
    #[TestWith([true, false, false])]
    #[TestWith([true, true, true])]
    public function testHappyPath(
        bool $can_user_edit_title,
        bool $can_user_edit_description,
        bool $expected_can_user_edit_section,
    ): void {
        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->withUpdatePermission($this->user, $can_user_edit_title)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description_field = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->withUpdatePermission($this->user, $can_user_edit_description)
            ->build();

        $file = $this->createMock(Tracker_FormElement_Field_File::class);
        $file->method('getId')->willReturn(600);
        $file->method('getLabel')->willReturn('Attachments');
        $this->file_upload_provider = GetFileUploadDataStub::withField($file);

        $art1 = $this->getArtifact(1, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art2 = $this->getArtifact(2, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art3 = $this->getArtifact(3, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art4 = $this->getArtifact(4, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);

        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($art1, $art2, $art3, $art4);

        $file->method('getRESTValue')
            ->willReturnCallback(fn(PFUser $user, Tracker_Artifact_Changeset $changeset) => match ($changeset) {
                $art1->getLastChangeset() => $this->getFileValue($file, $art1),
                $art2->getLastChangeset() => $this->getFileValue($file, $art2),
                $art3->getLastChangeset() => $this->getFileValue($file, $art3),
                $art4->getLastChangeset() => $this->getFileValue($file, $art4),
            });

        $result = $this->getRepresentation([
            RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id'   => 101, 'rank' => 0, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 2, 'id' => SectionIdentifierStub::create(), 'item_id'   => 101, 'rank' => 1, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 3, 'id' => SectionIdentifierStub::create(), 'item_id'   => 101, 'rank' => 2, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 4, 'id' => SectionIdentifierStub::create(), 'item_id'   => 101, 'rank' => 3, 'level' => 1]),
        ], RetrieveSemanticDescriptionFieldStub::withTextField($description_field));

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
        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->withUpdatePermission($this->user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description_field = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->withUpdatePermission($this->user, true)
            ->build();

        $file = $this->createMock(Tracker_FormElement_Field_File::class);
        $file->method('getId')->willReturn(600);
        $file->method('getLabel')->willReturn('Attachments');
        $this->file_upload_provider = GetFileUploadDataStub::withField($file);

        $art1                     = $this->getArtifact(1, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT);
        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($art1);

        $file->method('getRESTValue')
            ->willReturnCallback(fn(PFUser $user, Tracker_Artifact_Changeset $changeset) => match ($changeset) {
                $art1->getLastChangeset() => $this->getFileValue($file, $art1),
            });

        $result = $this->getRepresentation([
            RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id'   => 101, 'rank' => 1, 'level' => 1]),
        ], RetrieveSemanticDescriptionFieldStub::withTextField($description_field));

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
        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->withUpdatePermission($this->user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description_field = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->withUpdatePermission($this->user, true)
            ->build();

        $file = $this->createMock(Tracker_FormElement_Field_File::class);
        $file->method('getId')->willReturn(600);
        $file->method('getLabel')->willReturn('Attachments');
        $this->file_upload_provider = GetFileUploadDataStub::withField($file);

        $art1                     = $this->getArtifact(1, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT);
        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($art1);

        $file->method('getRESTValue')
            ->willReturnCallback(fn(PFUser $user, Tracker_Artifact_Changeset $changeset) => match ($changeset) {
                $art1->getLastChangeset() => $this->getFileValue($file, $art1),
            });

        $result = $this->getRepresentation([
            RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id'   => 101, 'rank' => 1, 'level' => 1]),
        ], RetrieveSemanticDescriptionFieldStub::withTextField($description_field));

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
        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->withUpdatePermission($this->user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description_field = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->withUpdatePermission($this->user, true)
            ->build();

        $file = $this->createMock(Tracker_FormElement_Field_File::class);
        $file->method('getId')->willReturn(600);
        $file->method('getLabel')->willReturn('Attachments');
        $this->file_upload_provider = GetFileUploadDataStub::withField($file);

        $art1                     = $this->getArtifact(1, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($art1);

        $file->method('getRESTValue')
            ->willReturnCallback(fn(PFUser $user, Tracker_Artifact_Changeset $changeset) => match ($changeset) {
                $art1->getLastChangeset() => $this->getFileValue($file, $art1),
            });

        $result = $this->getRepresentation([
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
        ], RetrieveSemanticDescriptionFieldStub::withTextField($description_field));

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
        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->withUpdatePermission($this->user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description_field = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->withUpdatePermission($this->user, true)
            ->build();

        $file = $this->createMock(Tracker_FormElement_Field_File::class);
        $file->method('getId')->willReturn(600);
        $file->method('getLabel')->willReturn('Attachments');
        $this->file_upload_provider = GetFileUploadDataStub::withField($file);

        $art1                     = $this->getArtifact(1, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($art1);

        $file->method('getRESTValue')->willReturn(null);

        $result = $this->getRepresentation([
            RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
        ], RetrieveSemanticDescriptionFieldStub::withTextField($description_field));

        self::assertTrue(Result::isOk($result));
        self::assertSame(10, $result->value->total);
        self::assertCount(1, $result->value->sections);
        self::assertInstanceOf(ArtifactSectionRepresentation::class, $result->value->sections[0]);
        self::assertInstanceOf(ArtifactSectionAttachmentsRepresentation::class, $result->value->sections[0]->attachments);
    }

    public function testArtifactHasNoAttachmentField(): void
    {
        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->withUpdatePermission($this->user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description_field = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->withUpdatePermission($this->user, true)
            ->build();

        $art1                     = $this->getArtifact(1, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($art1);

        $editor_builder = $this->createMock(UploadDataAttributesForRichTextEditorBuilder::class);
        $editor_builder->method('getDataAttributes')->willReturn([]);

        $result = $this->getRepresentation([
            RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
        ], RetrieveSemanticDescriptionFieldStub::withTextField($description_field));

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
        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->withUpdatePermission($this->user, false)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description_field = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->withUpdatePermission($this->user, false)
            ->build();

        $art1 = $this->getArtifact(1, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art2 = $this->getArtifact(2, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art3 = $this->getArtifact(3, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art4 = $this->getArtifact(4, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);

        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($art1, $art2, $art3, $art4);

        $editor_builder = $this->createMock(UploadDataAttributesForRichTextEditorBuilder::class);
        $editor_builder->method('getDataAttributes')->willReturn([]);

        $result = $this->getRepresentation([
            RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 2, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 3, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 2, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 4, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 3, 'level' => 1]),
        ], RetrieveSemanticDescriptionFieldStub::withTextField($description_field));

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
        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn(null);

        $description_field = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->build();

        $art1 = $this->getArtifact(1, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art2 = $this->getArtifact(2, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art3 = $this->getArtifact(3, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art4 = $this->getArtifact(4, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);

        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($art1, $art2, $art3, $art4);

        $result = $this->getRepresentation([
            RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 2, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 3, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 2, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 4, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 3, 'level' => 1]),
        ], RetrieveSemanticDescriptionFieldStub::withTextField($description_field));

        self::assertTrue(Result::isErr($result));
    }

    public function testWhenTitleSemanticIsNotReadableByCurrentUser(): void
    {
        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, false)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description_field = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->build();

        $art1 = $this->getArtifact(1, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art2 = $this->getArtifact(2, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art3 = $this->getArtifact(3, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art4 = $this->getArtifact(4, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);

        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($art1, $art2, $art3, $art4);

        $result = $this->getRepresentation([
            RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 2, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 3, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 2, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 4, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 3, 'level' => 1]),
        ], RetrieveSemanticDescriptionFieldStub::withTextField($description_field));

        self::assertTrue(Result::isErr($result));
    }

    public function testWhenDescriptionSemanticIsNotReadableByCurrentUser(): void
    {
        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description_field = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, false)
            ->build();

        $art1 = $this->getArtifact(1, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art2 = $this->getArtifact(2, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art3 = $this->getArtifact(3, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art4 = $this->getArtifact(4, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);

        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($art1, $art2, $art3, $art4);

        $result = $this->getRepresentation([
            RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 2, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 3, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 2, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 4, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 3, 'level' => 1]),
        ], RetrieveSemanticDescriptionFieldStub::withTextField($description_field));

        self::assertTrue(Result::isErr($result));
    }

    public function testWhenDescriptionSemanticIsNotSet(): void
    {
        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description_field = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->build();

        $art1 = $this->getArtifact(1, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art2 = $this->getArtifact(2, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art3 = $this->getArtifact(3, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art4 = $this->getArtifact(4, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);

        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($art1, $art2, $art3, $art4);

        $result = $this->getRepresentation([
            RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 2, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 3, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 2, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 4, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 3, 'level' => 1]),
        ], RetrieveSemanticDescriptionFieldStub::withNoField());

        self::assertTrue(Result::isErr($result));
    }

    public function testFaultWhenThereIsAtLeastOneArtifactThatCurrentUserCannotRead(): void
    {
        $title = StringFieldBuilder::aStringField(1001)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->build();
        $this->semantic_title->method('getField')->willReturn($title);

        $description_field = TextFieldBuilder::aTextField(1002)
            ->inTracker($this->tracker)
            ->withReadPermission($this->user, true)
            ->build();

        $art1 = $this->getArtifact(1, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art2 = $this->getArtifact(2, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);
        $art3 = $this->getArtifactUserCannotView(3);
        $art4 = $this->getArtifact(4, $title, $description_field, \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT);

        $this->artifact_retriever = RetrieveArtifactStub::withArtifacts($art1, $art2, $art3, $art4);

        $result = $this->getRepresentation([
            RetrievedSection::fromArtifact(['artifact_id' => 1, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 0, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 2, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 1, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 3, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 2, 'level' => 1]),
            RetrievedSection::fromArtifact(['artifact_id' => 4, 'id' => SectionIdentifierStub::create(), 'item_id' => 101, 'rank' => 3, 'level' => 1]),
        ], RetrieveSemanticDescriptionFieldStub::withTextField($description_field));

        self::assertTrue(Result::isErr($result));
    }

    public function testWhenThereIsNotAnyArtifacts(): void
    {
        $this->artifact_retriever = RetrieveArtifactStub::withNoArtifact();

        $result = $this->getRepresentation([], RetrieveSemanticDescriptionFieldStub::withNoField());

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
