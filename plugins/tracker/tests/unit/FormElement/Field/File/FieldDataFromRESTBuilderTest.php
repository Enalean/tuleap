<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\File;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException;
use Tracker_Artifact_Attachment_FileNotFoundException;
use Tracker_Artifact_Attachment_TemporaryFile;
use Tracker_Artifact_Attachment_TemporaryFileManager;
use Tracker_FileInfo;
use Tracker_FileInfoFactory;
use Tracker_FormElement_Field_File;
use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueFileTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FileFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;
use UserManager;

#[DisableReturnValueGenerationForTestDoubles]
final class FieldDataFromRESTBuilderTest extends TestCase
{
    private UserManager&MockObject $user_manager;
    private Tracker_FormElementFactory&MockObject $form_element_factory;
    private Tracker_FileInfoFactory&MockObject $file_info_factory;
    private Tracker_Artifact_Attachment_TemporaryFileManager&MockObject $temporary_file_manager;
    private FieldDataFromRESTBuilder $builder;
    private Artifact $artifact;
    private Tracker_FormElement_Field_File $field;
    private Tracker $tracker;
    private FileInfoForTusUploadedFileReadyToBeAttachedProvider&MockObject $tus_uploaded_file_provider;

    protected function setUp(): void
    {
        $this->user_manager               = $this->createMock(UserManager::class);
        $this->form_element_factory       = $this->createMock(Tracker_FormElementFactory::class);
        $this->file_info_factory          = $this->createMock(Tracker_FileInfoFactory::class);
        $this->temporary_file_manager     = $this->createMock(Tracker_Artifact_Attachment_TemporaryFileManager::class);
        $this->tus_uploaded_file_provider = $this->createMock(FileInfoForTusUploadedFileReadyToBeAttachedProvider::class);

        $this->builder = new FieldDataFromRESTBuilder(
            $this->user_manager,
            $this->form_element_factory,
            $this->file_info_factory,
            $this->temporary_file_manager,
            $this->tus_uploaded_file_provider
        );

        $this->tracker  = TrackerTestBuilder::aTracker()->build();
        $this->artifact = ArtifactTestBuilder::anArtifact(42)->inTracker($this->tracker)->build();
        $this->field    = FileFieldBuilder::aFileField(6541)->build();
    }

    public function testEmptyValue(): void
    {
        self::assertEquals(
            [],
            $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([]), $this->field, null)
        );
    }

    public function testExistingIdsRemoval(): void
    {
        $file1 = new Tracker_FileInfo(123, $this->field, 0, '', '', '', 'image/png');
        $file2 = new Tracker_FileInfo(456, $this->field, 0, '', '', '', 'image/png');

        $changeset = ChangesetTestBuilder::aChangeset(574)->build();
        $changeset->setFieldValue(
            $this->field,
            ChangesetValueFileTestBuilder::aValue(654, $changeset, $this->field)->withFiles([$file1, $file2])->build()
        );
        $this->artifact->setLastChangeset($changeset);

        $this->form_element_factory->expects($this->once())->method('getUsedFormElementsByType')
            ->with($this->tracker, 'file')->willReturn([$this->field]);

        self::assertEquals(
            [
                'delete' => [123, 456],
            ],
            $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([]), $this->field, $this->artifact)
        );
    }

    public function testItDoesNotRemoveOldAttachment(): void
    {
        $file1 = new Tracker_FileInfo(123, $this->field, 0, '', '', '', 'image/png');
        $file2 = new Tracker_FileInfo(456, $this->field, 0, '', '', '', 'image/png');

        $changeset = ChangesetTestBuilder::aChangeset(574)->build();
        $changeset->setFieldValue(
            $this->field,
            ChangesetValueFileTestBuilder::aValue(654, $changeset, $this->field)->withFiles([$file1, $file2])->build()
        );
        $this->artifact->setLastChangeset($changeset);

        $this->form_element_factory->expects($this->once())->method('getUsedFormElementsByType')
            ->with($this->tracker, 'file')->willReturn([$this->field]);

        $this->file_info_factory->expects($this->exactly(2))->method('getArtifactByFileInfoIdInLastChangeset')
            ->with(self::callback(static fn(int $id) => in_array($id, [123, 456])))->willReturn($this->artifact);

        self::assertEquals(
            [],
            $this->builder->buildFieldDataFromREST(
                $this->buildRESTRepresentation([123, 456]),
                $this->field,
                $this->artifact
            )
        );
    }

    public function testExceptionWhenNoArtifactAndFileIsLinkedToAnotherOne(): void
    {
        $another_artifact = ArtifactTestBuilder::anArtifact(666)->build();

        $this->file_info_factory->expects($this->once())->method('getArtifactByFileInfoIdInLastChangeset')
            ->with(123)->willReturn($another_artifact);

        $this->expectException(Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException::class);
        $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([123]), $this->field, null);
    }

    public function testExceptionWhenArtifactAndFileIsLinkedToAnotherOne(): void
    {
        $another_artifact = ArtifactTestBuilder::anArtifact(666)->build();

        $this->file_info_factory->expects($this->once())->method('getArtifactByFileInfoIdInLastChangeset')
            ->with(123)->willReturn($another_artifact);

        $this->expectException(Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException::class);
        $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([123]), $this->field, $this->artifact);
    }

    public function testFileIsNotLinkedAndNotTemporaryButTusUploaded(): void
    {
        $this->file_info_factory->expects($this->once())->method('getArtifactByFileInfoIdInLastChangeset')
            ->with(123)->willReturn(null);

        $this->temporary_file_manager->expects($this->once())->method('isFileIdTemporary')
            ->with(123)->willReturn(false);

        $this->user_manager->expects($this->once())->method('getCurrentUser')->willReturn(UserTestBuilder::buildWithDefaults());

        $this->tus_uploaded_file_provider->expects($this->once())->method('getFileInfo')
            ->willReturn(new Tracker_FileInfo(123, $this->field, 0, '', '', '', 'image/png'));

        self::assertEquals(
            [['tus-uploaded-id' => 123]],
            $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([123]), $this->field, null)
        );
    }

    public function testFileIsNotLinkedAndNotTemporaryAndNotTusUploaded(): void
    {
        $this->file_info_factory->expects($this->once())->method('getArtifactByFileInfoIdInLastChangeset')
            ->with(123)->willReturn(null);

        $this->temporary_file_manager->expects($this->once())->method('isFileIdTemporary')
            ->with(123)->willReturn(false);

        $this->user_manager->expects($this->once())->method('getCurrentUser')->willReturn(UserTestBuilder::buildWithDefaults());

        $this->tus_uploaded_file_provider->expects($this->once())->method('getFileInfo')->willReturn(null);

        $this->expectException(Tracker_Artifact_Attachment_FileNotFoundException::class);
        $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([123]), $this->field, null);
    }

    public function testFileIsTemporaryButDoesNotExist(): void
    {
        $this->file_info_factory->expects($this->once())->method('getArtifactByFileInfoIdInLastChangeset')
            ->with(123)->willReturn(null);

        $user = UserTestBuilder::buildWithDefaults();

        $temporary_file = $this->createMock(Tracker_Artifact_Attachment_TemporaryFile::class);
        $temporary_file->expects($this->once())->method('getCreatorId')->willReturn(101);
        $temporary_file->expects($this->once())->method('getTemporaryName')->willReturn('file.txt');

        $this->user_manager->expects($this->once())->method('getUserById')->with(101)->willReturn($user);

        $this->temporary_file_manager->expects($this->once())->method('isFileIdTemporary')
            ->with(123)->willReturn(true);
        $this->temporary_file_manager->expects($this->once())->method('getFile')
            ->with(123)->willReturn($temporary_file);
        $this->temporary_file_manager->expects($this->once())->method('exists')
            ->with($user, 'file.txt')->willReturn(false);

        $this->expectException(Tracker_Artifact_Attachment_FileNotFoundException::class);
        $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([123]), $this->field, null);
    }

    public function testFileIsTemporaryButItsCreatorDoesNotExist(): void
    {
        $this->file_info_factory->expects($this->once())->method('getArtifactByFileInfoIdInLastChangeset')
            ->with(123)->willReturn(null);

        $temporary_file = $this->createMock(Tracker_Artifact_Attachment_TemporaryFile::class);
        $temporary_file->expects($this->once())->method('getCreatorId')->willReturn(101);

        $this->user_manager->expects($this->once())->method('getUserById')->with(101)->willReturn(null);

        $this->temporary_file_manager->expects($this->once())->method('isFileIdTemporary')
            ->with(123)->willReturn(true);
        $this->temporary_file_manager->expects($this->once())->method('getFile')
            ->with(123)->willReturn($temporary_file);
        $this->temporary_file_manager->expects($this->never())->method('exists');

        $this->expectException(Tracker_Artifact_Attachment_FileNotFoundException::class);
        $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([123]), $this->field, null);
    }

    public function testTemporaryFileIsGivenInRESTData(): void
    {
        $this->file_info_factory->expects($this->once())->method('getArtifactByFileInfoIdInLastChangeset')
            ->with(123)->willReturn(null);

        $user = UserTestBuilder::buildWithDefaults();

        $temporary_file = $this->createMock(Tracker_Artifact_Attachment_TemporaryFile::class);
        $temporary_file->expects($this->once())->method('getCreatorId')->willReturn(101);
        $temporary_file->expects($this->exactly(2))->method('getTemporaryName')->willReturn('file.txt');

        $this->user_manager->expects($this->once())->method('getUserById')->with(101)->willReturn($user);

        $this->temporary_file_manager->expects($this->once())->method('isFileIdTemporary')->with(123)->willReturn(true);
        $this->temporary_file_manager->expects($this->once())->method('getFile')->with(123)->willReturn($temporary_file);
        $this->temporary_file_manager->expects($this->once())->method('exists')->with($user, 'file.txt')->willReturn(true);
        $this->temporary_file_manager->expects($this->once())->method('getPath')->with($user, 'file.txt')->willReturn('/path/to/file.txt');
        $this->file_info_factory->expects($this->once())->method('buildFileInfoData')
            ->with($temporary_file, '/path/to/file.txt')->willReturn(['id' => 123]);

        self::assertEquals(
            [
                ['id' => 123],
            ],
            $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([123]), $this->field, null)
        );
    }

    /**
     * @param array<int> $submitted_ids
     */
    private function buildRESTRepresentation(array $submitted_ids): \stdClass
    {
        return json_decode(
            json_encode(
                ['value' => $submitted_ids]
            )
        );
    }
}
