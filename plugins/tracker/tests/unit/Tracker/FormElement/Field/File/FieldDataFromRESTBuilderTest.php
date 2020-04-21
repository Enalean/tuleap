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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException;
use Tracker_Artifact_Attachment_FileNotFoundException;
use Tracker_Artifact_Attachment_TemporaryFile;
use Tracker_Artifact_Attachment_TemporaryFileManager;
use Tracker_FileInfoFactory;
use Tracker_FormElementFactory;
use UserManager;

class FieldDataFromRESTBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var Mockery\MockInterface|Tracker_FileInfoFactory
     */
    private $file_info_factory;
    /**
     * @var Mockery\MockInterface|Tracker_Artifact_Attachment_TemporaryFileManager
     */
    private $temporary_file_manager;
    /**
     * @var FieldDataFromRESTBuilder
     */
    private $builder;
    /**
     * @var Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact;
    /**
     * @var Mockery\MockInterface|\Tracker_FormElement_Field_File
     */
    private $field;
    /**
     * @var Mockery\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var Mockery\MockInterface|FileInfoForTusUploadedFileReadyToBeAttachedProvider
     */
    private $tus_uploaded_file_provider;

    protected function setUp(): void
    {
        $this->user_manager               = Mockery::mock(UserManager::class);
        $this->form_element_factory       = Mockery::mock(Tracker_FormElementFactory::class);
        $this->file_info_factory          = Mockery::mock(Tracker_FileInfoFactory::class);
        $this->temporary_file_manager     = Mockery::mock(Tracker_Artifact_Attachment_TemporaryFileManager::class);
        $this->tus_uploaded_file_provider = Mockery::mock(FileInfoForTusUploadedFileReadyToBeAttachedProvider::class);

        $this->builder = new FieldDataFromRESTBuilder(
            $this->user_manager,
            $this->form_element_factory,
            $this->file_info_factory,
            $this->temporary_file_manager,
            $this->tus_uploaded_file_provider
        );

        $this->tracker  = Mockery::mock(Tracker::class);
        $this->artifact = Mockery::mock(Tracker_Artifact::class);
        $this->artifact->shouldReceive('getTracker')->andReturn($this->tracker);
        $this->artifact->shouldReceive('getId')->andReturn(42);

        $this->field = Mockery::mock(\Tracker_FormElement_Field_File::class);
    }

    public function testEmptyValue(): void
    {
        $this->assertEquals(
            [],
            $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([]), $this->field, null)
        );
    }

    public function testExistingIdsRemoval(): void
    {
        $file1 = Mockery::mock(\Tracker_FileInfo::class);
        $file1->shouldReceive('getId')->andReturn(123);

        $file2 = Mockery::mock(\Tracker_FileInfo::class);
        $file2->shouldReceive('getId')->andReturn(456);

        $value = Mockery::mock(\Tracker_Artifact_ChangesetValue_File::class);
        $value->shouldReceive('getFiles')->andReturn([$file1, $file2]);

        $this->field
            ->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturn($value)
            ->once();

        $this->form_element_factory
            ->shouldReceive('getUsedFormElementsByType')
            ->with($this->tracker, 'file')
            ->andReturn([$this->field])
            ->once();

        $this->assertEquals(
            [
                'delete' => [123, 456]
            ],
            $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([]), $this->field, $this->artifact)
        );
    }

    public function testItDoesNotRemoveOldAttachment(): void
    {
        $file1 = Mockery::mock(\Tracker_FileInfo::class);
        $file1->shouldReceive('getId')->andReturn(123);

        $file2 = Mockery::mock(\Tracker_FileInfo::class);
        $file2->shouldReceive('getId')->andReturn(456);

        $value = Mockery::mock(\Tracker_Artifact_ChangesetValue_File::class);
        $value->shouldReceive('getFiles')->andReturn([$file1, $file2]);

        $this->field
            ->shouldReceive('getLastChangesetValue')
            ->with($this->artifact)
            ->andReturn($value)
            ->once();

        $this->form_element_factory
            ->shouldReceive('getUsedFormElementsByType')
            ->with($this->tracker, 'file')
            ->andReturn([$this->field])
            ->once();

        $this->file_info_factory
            ->shouldReceive('getArtifactByFileInfoIdInLastChangeset')
            ->with(123)
            ->andReturn($this->artifact)
            ->once();
        $this->file_info_factory
            ->shouldReceive('getArtifactByFileInfoIdInLastChangeset')
            ->with(456)
            ->andReturn($this->artifact)
            ->once();

        $this->assertEquals(
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
        $another_artifact = Mockery::mock(Tracker_Artifact::class);
        $another_artifact->shouldReceive('getId')->andReturn(666);

        $this->file_info_factory
            ->shouldReceive('getArtifactByFileInfoIdInLastChangeset')
            ->with(123)
            ->andReturn($another_artifact)
            ->once();

        $this->expectException(Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException::class);
        $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([123]), $this->field, null);
    }

    public function testExceptionWhenArtifactAndFileIsLinkedToAnotherOne(): void
    {
        $another_artifact = Mockery::mock(Tracker_Artifact::class);
        $another_artifact->shouldReceive('getId')->andReturn(666);

        $this->file_info_factory
            ->shouldReceive('getArtifactByFileInfoIdInLastChangeset')
            ->with(123)
            ->andReturn($another_artifact)
            ->once();

        $this->expectException(Tracker_Artifact_Attachment_AlreadyLinkedToAnotherArtifactException::class);
        $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([123]), $this->field, $this->artifact);
    }

    public function testFileIsNotLinkedAndNotTemporaryButTusUploaded(): void
    {
        $this->file_info_factory
            ->shouldReceive('getArtifactByFileInfoIdInLastChangeset')
            ->with(123)
            ->andReturn(null)
            ->once();

        $this->temporary_file_manager
            ->shouldReceive('isFileIdTemporary')
            ->with(123)
            ->andReturn(false)
            ->once();

        $current_user = Mockery::mock(PFUser::class);
        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->andReturn($current_user)
            ->once();

        $this->tus_uploaded_file_provider
            ->shouldReceive('getFileInfo')
            ->andReturn(Mockery::mock(\Tracker_FileInfo::class))
            ->once();

        $this->assertEquals(
            [['tus-uploaded-id' => 123]],
            $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([123]), $this->field, null)
        );
    }

    public function testFileIsNotLinkedAndNotTemporaryAndNotTusUploaded(): void
    {
        $this->file_info_factory
            ->shouldReceive('getArtifactByFileInfoIdInLastChangeset')
            ->with(123)
            ->andReturn(null)
            ->once();

        $this->temporary_file_manager
            ->shouldReceive('isFileIdTemporary')
            ->with(123)
            ->andReturn(false)
            ->once();

        $current_user = Mockery::mock(PFUser::class);
        $this->user_manager
            ->shouldReceive('getCurrentUser')
            ->andReturn($current_user)
            ->once();

        $this->tus_uploaded_file_provider
            ->shouldReceive('getFileInfo')
            ->andReturn(null)
            ->once();

        $this->expectException(Tracker_Artifact_Attachment_FileNotFoundException::class);
        $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([123]), $this->field, null);
    }

    public function testFileIsTemporaryButDoesNotExist(): void
    {
        $this->file_info_factory
            ->shouldReceive('getArtifactByFileInfoIdInLastChangeset')
            ->with(123)
            ->andReturn(null)
            ->once();

        $user = Mockery::mock(PFUser::class);

        $temporary_file = Mockery::mock(Tracker_Artifact_Attachment_TemporaryFile::class);
        $temporary_file->shouldReceive('getCreatorId')->andReturn(101)->once();
        $temporary_file->shouldReceive('getTemporaryName')->andReturn('file.txt')->once();

        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(101)
            ->andReturn($user)
            ->once();

        $this->temporary_file_manager
            ->shouldReceive('isFileIdTemporary')
            ->with(123)
            ->andReturn(true)
            ->once();
        $this->temporary_file_manager
            ->shouldReceive('getFile')
            ->with(123)
            ->andReturn($temporary_file)
            ->once();
        $this->temporary_file_manager
            ->shouldReceive('exists')
            ->with($user, 'file.txt')
            ->andReturn(false)
            ->once();

        $this->expectException(Tracker_Artifact_Attachment_FileNotFoundException::class);
        $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([123]), $this->field, null);
    }

    public function testFileIsTemporaryButItsCreatorDoesNotExist(): void
    {
        $this->file_info_factory
            ->shouldReceive('getArtifactByFileInfoIdInLastChangeset')
            ->with(123)
            ->andReturn(null)
            ->once();

        $temporary_file = Mockery::mock(Tracker_Artifact_Attachment_TemporaryFile::class);
        $temporary_file->shouldReceive('getCreatorId')->andReturn(101)->once();

        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(101)
            ->andReturn(null)
            ->once();

        $this->temporary_file_manager
            ->shouldReceive('isFileIdTemporary')
            ->with(123)
            ->andReturn(true)
            ->once();
        $this->temporary_file_manager
            ->shouldReceive('getFile')
            ->with(123)
            ->andReturn($temporary_file)
            ->once();
        $this->temporary_file_manager
            ->shouldReceive('exists')
            ->never();

        $this->expectException(Tracker_Artifact_Attachment_FileNotFoundException::class);
        $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([123]), $this->field, null);
    }

    public function testTemporaryFileIsGivenInRESTData(): void
    {
        $this->file_info_factory
            ->shouldReceive('getArtifactByFileInfoIdInLastChangeset')
            ->with(123)
            ->andReturn(null)
            ->once();

        $user = Mockery::mock(PFUser::class);

        $temporary_file = Mockery::mock(Tracker_Artifact_Attachment_TemporaryFile::class);
        $temporary_file->shouldReceive('getCreatorId')->andReturn(101)->once();
        $temporary_file->shouldReceive('getTemporaryName')->andReturn('file.txt')->twice();

        $this->user_manager
            ->shouldReceive('getUserById')
            ->with(101)
            ->andReturn($user)
            ->once();

        $this->temporary_file_manager
            ->shouldReceive('isFileIdTemporary')
            ->with(123)
            ->andReturn(true)
            ->once();
        $this->temporary_file_manager
            ->shouldReceive('getFile')
            ->with(123)
            ->andReturn($temporary_file)
            ->once();
        $this->temporary_file_manager
            ->shouldReceive('exists')
            ->with($user, 'file.txt')
            ->andReturn(true)
            ->once();
        $this->temporary_file_manager
            ->shouldReceive('getPath')
            ->with($user, 'file.txt')
            ->andReturn('/path/to/file.txt')
            ->once();
        $this->file_info_factory
            ->shouldReceive('buildFileInfoData')
            ->with($temporary_file, '/path/to/file.txt')
            ->andReturn(['id' => 123])
            ->once();

        $this->assertEquals(
            [
                ['id' => 123]
            ],
            $this->builder->buildFieldDataFromREST($this->buildRESTRepresentation([123]), $this->field, null)
        );
    }

    /**
     * @param array<int> $submitted_ids
     *
     * @return mixed
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
