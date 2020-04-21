<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Docman\Upload\Document;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PermissionsManager;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\Permissions\PermissionItemUpdater;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSet;
use Tuleap\Docman\Upload\UploadCreationConflictException;
use Tuleap\Docman\Upload\UploadCreationFileMismatchException;
use Tuleap\Docman\Upload\UploadMaxSizeExceededException;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;

final class DocumentToUploadCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $dao;
    /**
     * @var PermissionsManager|Mockery\MockInterface
     */
    private $permissions_manager;
    /**
     * @var Mockery\MockInterface|PermissionItemUpdater
     */
    private $permissions_item_updater;

    public function setUp(): void
    {
        \ForgeConfig::store();

        $this->dao                      = Mockery::mock(DocumentOngoingUploadDAO::class);
        $this->permissions_manager      = Mockery::mock(PermissionsManager::class);
        $this->permissions_item_updater = Mockery::mock(PermissionItemUpdater::class);
    }

    public function tearDown(): void
    {
        \ForgeConfig::restore();
    }

    /**
     * @dataProvider permissionsForGroupsDataProvider
     */
    public function testCreation(?DocmanItemPermissionsForGroupsSet $permissions_for_groups): void
    {
        $metadata_creator = Mockery::mock(DocumentMetadataCreator::class);
        $creator          = new DocumentToUploadCreator(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $metadata_creator,
            $this->permissions_manager,
            $this->permissions_item_updater
        );

        \ForgeConfig::set(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $parent_item = Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $parent_item->shouldReceive('getGroupId')->andReturns(741);
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $current_time = new \DateTimeImmutable();

        $this->dao->shouldReceive('searchDocumentOngoingUploadByParentIDTitleAndExpirationDate')->andReturns([]);
        $this->dao->shouldReceive('saveDocumentOngoingUpload')->once()->andReturns(12);

        $obsolescence_date = \DateTimeImmutable::createFromFormat('Y-m-d', '2100-05-19');

        $metadata_creator->shouldReceive('storeItemCustomMetadata')->never();

        if ($permissions_for_groups === null) {
            $this->permissions_manager->shouldReceive('clonePermissions')->once();
        } else {
            $this->permissions_item_updater->shouldReceive('initPermissionsOnNewlyCreatedItem')->once();
        }

        $document_to_upload = $creator->create(
            $parent_item,
            $user,
            $current_time,
            'title',
            'description',
            'filename',
            123456,
            PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            $obsolescence_date->getTimestamp(),
            null,
            $permissions_for_groups
        );

        $this->assertSame(12, $document_to_upload->getItemId());
    }

    public function permissionsForGroupsDataProvider(): array
    {
        return [
            [new DocmanItemPermissionsForGroupsSet([])],
            [null]
        ];
    }

    public function testCreationWithMetadata(): void
    {
        $metadata_creator = Mockery::mock(DocumentMetadataCreator::class);
        $creator          = new DocumentToUploadCreator(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $metadata_creator,
            $this->permissions_manager,
            $this->permissions_item_updater
        );

        \ForgeConfig::set(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $parent_item = Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $parent_item->shouldReceive('getGroupId')->andReturns(741);
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $current_time = new \DateTimeImmutable();

        $this->dao->shouldReceive('searchDocumentOngoingUploadByParentIDTitleAndExpirationDate')->andReturns([]);
        $this->dao->shouldReceive('saveDocumentOngoingUpload')->once()->andReturns(12);

        $obsolescence_date = \DateTimeImmutable::createFromFormat('Y-m-d', '2100-05-19');

        $metadata_creator->shouldReceive('storeItemCustomMetadata')->once();
        $this->permissions_item_updater->shouldReceive('initPermissionsOnNewlyCreatedItem')->once();

        $document_to_upload = $creator->create(
            $parent_item,
            $user,
            $current_time,
            'title',
            'description',
            'filename',
            123456,
            PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            $obsolescence_date->getTimestamp(),
            ['id' => 1, 'value' => 'abcde'],
            new DocmanItemPermissionsForGroupsSet([])
        );

        $this->assertSame(12, $document_to_upload->getItemId());
    }

    public function testANewItemIsNotCreatedIfAnUploadIsOngoingWithTheSameFile()
    {
        $creator = new DocumentToUploadCreator(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            Mockery::mock(DocumentMetadataCreator::class),
            $this->permissions_manager,
            $this->permissions_item_updater
        );

        \ForgeConfig::set(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $parent_item = Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $current_time = new \DateTimeImmutable();

        $this->dao->shouldReceive('searchDocumentOngoingUploadByParentIDTitleAndExpirationDate')->andReturns([
            ['item_id' => 12, 'user_id' => 102, 'filename' => 'filename', 'filesize' => 123456]
        ]);

        $obsolescence_date = \DateTimeImmutable::createFromFormat('Y-m-d', '2100-05-19');

        $document_to_upload = $creator->create(
            $parent_item,
            $user,
            $current_time,
            'title',
            'description',
            'filename',
            123456,
            PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            $obsolescence_date->getTimestamp(),
            null,
            new DocmanItemPermissionsForGroupsSet([])
        );

        $this->assertSame(12, $document_to_upload->getItemId());
    }

    public function testCreationIsRejectedWhenAnotherUserIsCreatingTheDocument()
    {
        $creator = new DocumentToUploadCreator(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            Mockery::mock(DocumentMetadataCreator::class),
            $this->permissions_manager,
            $this->permissions_item_updater
        );

        \ForgeConfig::set(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $parent_item = Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $current_time = new \DateTimeImmutable();

        $this->dao->shouldReceive('searchDocumentOngoingUploadByParentIDTitleAndExpirationDate')->andReturns([
            ['user_id' => 103]
        ]);

        $this->expectException(UploadCreationConflictException::class);

        $obsolescence_date = \DateTimeImmutable::createFromFormat('Y-m-d', '2100-05-19');

        $creator->create(
            $parent_item,
            $user,
            $current_time,
            'title',
            'description',
            'filename',
            123456,
            PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            $obsolescence_date->getTimestamp(),
            null,
            new DocmanItemPermissionsForGroupsSet([])
        );
    }

    public function testCreationIsRejectedWhenTheUserIsAlreadyCreatingTheDocumentWithAnotherFile()
    {
        $creator = new DocumentToUploadCreator(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            Mockery::mock(DocumentMetadataCreator::class),
            $this->permissions_manager,
            $this->permissions_item_updater
        );

        \ForgeConfig::set(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $parent_item = Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $current_time = new \DateTimeImmutable();

        $this->dao->shouldReceive('searchDocumentOngoingUploadByParentIDTitleAndExpirationDate')->andReturns([
            ['user_id' => 102, 'filename' => 'filename1', 'filesize' => 123456]
        ]);

        $this->expectException(UploadCreationFileMismatchException::class);

        $obsolescence_date = \DateTimeImmutable::createFromFormat('Y-m-d', '2100-05-19');

        $creator->create(
            $parent_item,
            $user,
            $current_time,
            'title',
            'description',
            'filename2',
            789,
            PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            $obsolescence_date->getTimestamp(),
            null,
            new DocmanItemPermissionsForGroupsSet([])
        );
    }

    public function testCreationIsRejectedIfTheFileIsBiggerThanTheConfigurationLimit()
    {
        $creator = new DocumentToUploadCreator(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            Mockery::mock(DocumentMetadataCreator::class),
            $this->permissions_manager,
            $this->permissions_item_updater
        );

        \ForgeConfig::set(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '1');
        $parent_item = Mockery::mock(\Docman_Item::class);
        $user = Mockery::mock(\PFUser::class);
        $current_time = new \DateTimeImmutable();

        $this->expectException(UploadMaxSizeExceededException::class);

        $obsolescence_date = \DateTimeImmutable::createFromFormat('Y-m-d', '2100-05-19');

        $creator->create(
            $parent_item,
            $user,
            $current_time,
            'title',
            'description',
            'filename',
            2,
            PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            $obsolescence_date->getTimestamp(),
            null,
            new DocmanItemPermissionsForGroupsSet([])
        );
    }
}
