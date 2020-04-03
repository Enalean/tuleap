<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\File\Upload;

use DateTimeImmutable;
use LogicException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class FileToUploadCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var MockInterface|FileOngoingUploadDao
     */
    private $dao;
    /**
     * @var FileToUploadCreator
     */
    private $creator;
    /**
     * @var MockInterface|PFUser
     */
    private $user;
    /**
     * @var MockInterface|\Tracker_FormElement_Field_File
     */
    private $field;

    /**
     * @before
     */
    public function instantiateCreator(): void
    {
        $this->dao          = Mockery::mock(FileOngoingUploadDao::class);
        $this->creator      = new FileToUploadCreator(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            1000
        );
    }

    /**
     * @before
     */
    public function instantiateField(): void
    {
        $this->field = Mockery::mock(\Tracker_FormElement_Field_File::class);
        $this->field->shouldReceive('getId')->andReturn(42);
    }

    /**
     * @before
     */
    public function instantiateUser(): void
    {
        $this->user = Mockery::mock(PFUser::class);
        $this->user->shouldReceive('getId')->andReturn(102);
    }

    public function testCreation()
    {
        $current_time = new DateTimeImmutable();

        $this->dao->shouldReceive('searchFileOngoingUploadByFieldIdNameAndExpirationDate')->andReturn([]);
        $this->dao->shouldReceive('saveFileOngoingUpload')->once()->andReturn(12);

        $document_to_upload = $this->creator->create(
            $this->field,
            $this->user,
            $current_time,
            'filename.txt',
            123,
            'text/plain'
        );

        $this->assertSame('/uploads/tracker/file/12', $document_to_upload->getUploadHref());
    }

    public function testANewItemIsNotCreatedIfAnUploadIsOngoingWithTheSameFile()
    {
        $current_time = new DateTimeImmutable();

        $this->dao->shouldReceive('searchFileOngoingUploadByFieldIdNameAndExpirationDate')->andReturn(
            [
                ['id' => 12, 'submitted_by' => 102, 'filesize' => 123]
            ]
        );

        $document_to_upload = $this->creator->create(
            $this->field,
            $this->user,
            $current_time,
            'filename.txt',
            123,
            'text/plain'
        );

        $this->assertSame('/uploads/tracker/file/12', $document_to_upload->getUploadHref());
    }

    public function testANewItemIsNotCreatedIfManyUploadsAreOngoingWithTheSameFileWhichShouldNeverHappen()
    {
        $current_time = new DateTimeImmutable();

        $this->dao->shouldReceive('searchFileOngoingUploadByFieldIdNameAndExpirationDate')->andReturn(
            [
                ['id' => 12, 'submitted_by' => 102, 'filesize' => 123],
                ['id' => 13, 'submitted_by' => 102, 'filesize' => 123]
            ]
        );

        $this->dao->shouldReceive('saveFileOngoingUpload')->never();

        $this->expectException(LogicException::class);

        $this->creator->create(
            $this->field,
            $this->user,
            $current_time,
            'filename.txt',
            123,
            'text/plain'
        );
    }

    public function testCreationIsRejectedWhenAnotherUserIsCreatingTheDocument()
    {
        $current_time = new DateTimeImmutable();

        $this->dao->shouldReceive('searchFileOngoingUploadByFieldIdNameAndExpirationDate')->andReturn(
            [
                ['submitted_by' => 103]
            ]
        );

        $this->expectException(UploadCreationConflictException::class);

        $this->creator->create(
            $this->field,
            $this->user,
            $current_time,
            'filename.txt',
            123,
            'text/plain'
        );
    }

    public function testCreationIsRejectedWhenTheUserIsAlreadyCreatingTheDocumentWithAnotherFile()
    {
        $current_time = new DateTimeImmutable();

        $this->dao->shouldReceive('searchFileOngoingUploadByFieldIdNameAndExpirationDate')->andReturn(
            [
                ['submitted_by' => 102, 'filesize' => 123456]
            ]
        );

        $this->expectException(UploadCreationFileMismatchException::class);

        $this->creator->create(
            $this->field,
            $this->user,
            $current_time,
            'filename.txt',
            789,
            'text/plain'
        );
    }

    public function testCreationIsRejectedIfTheFileIsBiggerThanTheConfigurationLimit()
    {
        $current_time = new DateTimeImmutable();

        $this->expectException(UploadMaxSizeExceededException::class);

        $this->creator->create(
            $this->field,
            $this->user,
            $current_time,
            'filename.txt',
            2000,
            'text/plain'
        );
    }
}
