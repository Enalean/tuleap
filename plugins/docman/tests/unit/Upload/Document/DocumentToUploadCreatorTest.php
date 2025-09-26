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

declare(strict_types=1);

namespace Tuleap\Docman\Upload\Document;

use DateTimeImmutable;
use Docman_Item;
use Docman_SettingsBo;
use DocmanPlugin;
use ForgeConfig;
use PermissionsManager;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\FilenamePattern\FilenameBuilder;
use Tuleap\Docman\Permissions\PermissionItemUpdater;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSet;
use Tuleap\Docman\Tests\Stub\FilenamePatternRetrieverStub;
use Tuleap\Docman\Upload\UploadCreationConflictException;
use Tuleap\Docman\Upload\UploadCreationFileMismatchException;
use Tuleap\Docman\Upload\UploadMaxSizeExceededException;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocumentToUploadCreatorTest extends TestCase
{
    use ForgeConfigSandbox;

    private DocumentOngoingUploadDAO&MockObject $dao;
    private PermissionsManager&MockObject $permissions_manager;
    private PermissionItemUpdater&MockObject $permissions_item_updater;
    private FilenameBuilder $filename_builder;

    #[\Override]
    public function setUp(): void
    {
        $this->dao                      = $this->createMock(DocumentOngoingUploadDAO::class);
        $this->permissions_manager      = $this->createMock(PermissionsManager::class);
        $this->permissions_item_updater = $this->createMock(PermissionItemUpdater::class);
        $this->filename_builder         = new FilenameBuilder(
            FilenamePatternRetrieverStub::buildWithNoPattern(),
            new ItemStatusMapper($this->createMock(Docman_SettingsBo::class))
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('permissionsForGroupsDataProvider')]
    public function testCreation(?DocmanItemPermissionsForGroupsSet $permissions_for_groups): void
    {
        $metadata_creator = $this->createMock(DocumentMetadataCreator::class);
        $creator          = new DocumentToUploadCreator(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $metadata_creator,
            $this->permissions_manager,
            $this->permissions_item_updater,
            $this->filename_builder
        );

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $parent_item  = new Docman_Item(['item_id' => 11, 'group_id' => 741]);
        $user         = UserTestBuilder::buildWithId(102);
        $current_time = new DateTimeImmutable();

        $this->dao->method('searchDocumentOngoingUploadByParentIDTitleAndExpirationDate')->willReturn([]);
        $this->dao->expects($this->once())->method('updateDocumentFilenameOngoingUpload');
        $this->dao->expects($this->once())->method('saveDocumentOngoingUpload')->willReturn(12);

        $obsolescence_date = DateTimeImmutable::createFromFormat('Y-m-d', '2100-05-19');

        $metadata_creator->expects($this->never())->method('storeItemCustomMetadata');

        if ($permissions_for_groups === null) {
            $this->permissions_manager->expects($this->once())->method('clonePermissions');
        } else {
            $this->permissions_item_updater->expects($this->once())->method('initPermissionsOnNewlyCreatedItem');
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

        self::assertSame(12, $document_to_upload->getItemId());
    }

    public static function permissionsForGroupsDataProvider(): array
    {
        return [
            [new DocmanItemPermissionsForGroupsSet([])],
            [null],
        ];
    }

    public function testCreationWithMetadata(): void
    {
        $metadata_creator = $this->createMock(DocumentMetadataCreator::class);
        $creator          = new DocumentToUploadCreator(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $metadata_creator,
            $this->permissions_manager,
            $this->permissions_item_updater,
            $this->filename_builder
        );

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $parent_item  = new Docman_Item(['item_id' => 11, 'group_id' => 741]);
        $user         = UserTestBuilder::buildWithId(102);
        $current_time = new DateTimeImmutable();

        $this->dao->method('searchDocumentOngoingUploadByParentIDTitleAndExpirationDate')->willReturn([]);
        $this->dao->expects($this->once())->method('updateDocumentFilenameOngoingUpload');
        $this->dao->expects($this->once())->method('saveDocumentOngoingUpload')->willReturn(12);

        $obsolescence_date = DateTimeImmutable::createFromFormat('Y-m-d', '2100-05-19');

        $metadata_creator->expects($this->once())->method('storeItemCustomMetadata');
        $this->permissions_item_updater->expects($this->once())->method('initPermissionsOnNewlyCreatedItem');

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

        self::assertSame(12, $document_to_upload->getItemId());
    }

    public function testANewItemIsNotCreatedIfAnUploadIsOngoingWithTheSameFile(): void
    {
        $creator = new DocumentToUploadCreator(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->createMock(DocumentMetadataCreator::class),
            $this->permissions_manager,
            $this->permissions_item_updater,
            $this->filename_builder
        );

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(102);
        $current_time = new DateTimeImmutable();

        $this->dao->method('searchDocumentOngoingUploadByParentIDTitleAndExpirationDate')->willReturn([
            ['item_id' => 12, 'user_id' => 102, 'filename' => 'filename', 'filesize' => 123456],
        ]);

        $obsolescence_date = DateTimeImmutable::createFromFormat('Y-m-d', '2100-05-19');

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

        self::assertSame(12, $document_to_upload->getItemId());
    }

    public function testCreationIsRejectedWhenAnotherUserIsCreatingTheDocument(): void
    {
        $creator = new DocumentToUploadCreator(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->createMock(DocumentMetadataCreator::class),
            $this->permissions_manager,
            $this->permissions_item_updater,
            $this->filename_builder
        );

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(102);
        $current_time = new DateTimeImmutable();

        $this->dao->method('searchDocumentOngoingUploadByParentIDTitleAndExpirationDate')->willReturn([['user_id' => 103]]);

        $this->expectException(UploadCreationConflictException::class);

        $obsolescence_date = DateTimeImmutable::createFromFormat('Y-m-d', '2100-05-19');

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

    public function testCreationIsRejectedWhenTheUserIsAlreadyCreatingTheDocumentWithAnotherFile(): void
    {
        $creator = new DocumentToUploadCreator(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->createMock(DocumentMetadataCreator::class),
            $this->permissions_manager,
            $this->permissions_item_updater,
            $this->filename_builder
        );

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '999999');
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(102);
        $current_time = new DateTimeImmutable();

        $this->dao->method('searchDocumentOngoingUploadByParentIDTitleAndExpirationDate')->willReturn([
            ['user_id' => 102, 'filename' => 'filename1', 'filesize' => 123456],
        ]);

        $this->expectException(UploadCreationFileMismatchException::class);

        $obsolescence_date = DateTimeImmutable::createFromFormat('Y-m-d', '2100-05-19');

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

    public function testCreationIsRejectedIfTheFileIsBiggerThanTheConfigurationLimit(): void
    {
        $creator = new DocumentToUploadCreator(
            $this->dao,
            new DBTransactionExecutorPassthrough(),
            $this->createMock(DocumentMetadataCreator::class),
            $this->permissions_manager,
            $this->permissions_item_updater,
            $this->filename_builder
        );

        ForgeConfig::set(DocmanPlugin::PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING, '1');
        $parent_item  = new Docman_Item();
        $user         = UserTestBuilder::buildWithDefaults();
        $current_time = new DateTimeImmutable();

        $this->expectException(UploadMaxSizeExceededException::class);

        $obsolescence_date = DateTimeImmutable::createFromFormat('Y-m-d', '2100-05-19');

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
