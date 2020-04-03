<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Docman\Upload\Version;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\Upload\UploadCreationConflictException;
use Tuleap\Docman\Upload\UploadCreationFileMismatchException;
use Tuleap\Docman\Upload\UploadMaxSizeExceededException;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

class VersionToUploadCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $dao;

    public function setUp(): void
    {
        \ForgeConfig::store();

        $this->dao = \Mockery::mock(DocumentOnGoingVersionToUploadDAO::class);
    }

    public function tearDown(): void
    {
        \ForgeConfig::restore();
    }

    public function testCreation()
    {
        $creator = new VersionToUploadCreator($this->dao, new DBTransactionExecutorPassthrough());

        \ForgeConfig::set(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $item = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getId')->andReturns(11);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $current_time = new \DateTimeImmutable();

        $this->dao->shouldReceive('searchDocumentVersionOngoingUploadByItemIdAndExpirationDate')->andReturns([]);
        $this->dao->shouldReceive('saveDocumentVersionOngoingUpload')->once()->andReturns(12);

        $is_file_locked = false;
        $document_to_upload = $creator->create(
            $item,
            $user,
            $current_time,
            'version title',
            'changelog',
            'filename',
            123456,
            $is_file_locked,
            101,
            155815815,
            'My new title',
            'new description',
            'copy'
        );

        $this->assertSame(12, $document_to_upload->getVersionId());
    }

    public function testANewItemIsNotCreatedIfAnUploadIsOngoingWithTheSameFile()
    {
        $creator = new VersionToUploadCreator($this->dao, new DBTransactionExecutorPassthrough());

        \ForgeConfig::set(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $parent_item = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $current_time = new \DateTimeImmutable();

        $this->dao->shouldReceive('searchDocumentVersionOngoingUploadByItemIdAndExpirationDate')->andReturns(
            [
                ['id' => 12, 'user_id' => 102, 'filename' => 'filename', 'filesize' => 123456]
            ]
        );

        $is_file_locked = false;
        $document_to_upload = $creator->create(
            $parent_item,
            $user,
            $current_time,
            'version title',
            'changelog',
            'filename',
            123456,
            $is_file_locked,
            101,
            155815815,
            'My new title',
            'new description',
            'copy'
        );

        $this->assertSame(12, $document_to_upload->getVersionId());
    }

    public function testCreationIsRejectedWhenAnotherUserIsCreatingTheDocument()
    {
        $creator = new VersionToUploadCreator($this->dao, new DBTransactionExecutorPassthrough());

        \ForgeConfig::set(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $parent_item = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $current_time = new \DateTimeImmutable();

        $this->dao->shouldReceive('searchDocumentVersionOngoingUploadByItemIdAndExpirationDate')->andReturns(
            [
                ['user_id' => 103]
            ]
        );

        $this->expectException(UploadCreationConflictException::class);

        $is_file_locked = false;
        $creator->create(
            $parent_item,
            $user,
            $current_time,
            'version title',
            'changelog',
            'filename',
            123456,
            $is_file_locked,
            101,
            155815815,
            'My new title',
            'new description',
            'empty'
        );
    }

    public function testCreationIsRejectedWhenTheUserIsAlreadyCreatingTheDocumentWithAnotherFile()
    {
        $creator = new VersionToUploadCreator($this->dao, new DBTransactionExecutorPassthrough());

        \ForgeConfig::set(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $parent_item = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $current_time = new \DateTimeImmutable();

        $this->dao->shouldReceive('searchDocumentVersionOngoingUploadByItemIdAndExpirationDate')->andReturns(
            [
                ['user_id' => 102, 'filename' => 'filename1', 'filesize' => 123456]
            ]
        );

        $this->expectException(UploadCreationFileMismatchException::class);

        $is_file_locked = false;
        $creator->create(
            $parent_item,
            $user,
            $current_time,
            'version title',
            'changelog',
            'filename2',
            789,
            $is_file_locked,
            101,
            155815815,
            'My new title',
            'new description',
            'reset'
        );
    }

    public function testCreationIsRejectedIfTheFileIsBiggerThanTheConfigurationLimit()
    {
        $creator = new VersionToUploadCreator($this->dao, new DBTransactionExecutorPassthrough());

        \ForgeConfig::set(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '1');
        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $current_time = new \DateTimeImmutable();

        $this->expectException(UploadMaxSizeExceededException::class);

        $is_file_locked = false;
        $creator->create(
            $parent_item,
            $user,
            $current_time,
            'version title',
            'changelog',
            'filename',
            2,
            $is_file_locked,
            101,
            155815815,
            'My new title',
            'new description',
            'reset'
        );
    }
}
