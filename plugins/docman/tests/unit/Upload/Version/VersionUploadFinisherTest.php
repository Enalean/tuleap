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

namespace Tuleap\Docman\Upload\Version;

use Docman_File;
use Docman_FileStorage;
use Docman_ItemFactory;
use Docman_LockFactory;
use Docman_MIMETypeDetector;
use Docman_VersionFactory;
use EventManager;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use Psr\Log\NullLogger;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdater;
use Tuleap\Docman\PostUpdate\PostUpdateFileHandler;
use Tuleap\Docman\REST\v1\DocmanItemsEventAdder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Upload\FileAlreadyUploadedInformation;
use Tuleap\Upload\FileBeingUploadedInformation;
use Tuleap\Upload\UploadPathAllocator;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class VersionUploadFinisherTest extends TestCase
{
    private ProjectManager&MockObject $project_manager;
    private DocmanItemsEventAdder&MockObject $adder;
    private Docman_ItemFactory&MockObject $item_factory;
    private Docman_VersionFactory&MockObject $version_factory;
    private EventManager&MockObject $event_manager;
    private DocumentOnGoingVersionToUploadDAO&MockObject $on_going_upload_dao;
    private Docman_FileStorage&MockObject $file_storage;
    private UserManager&MockObject $user_manager;
    private ApprovalTableUpdater&MockObject $approval_table_updater;
    private ApprovalTableRetriever&MockObject $approval_table_retriever;
    private ApprovalTableUpdateActionChecker&MockObject $approval_table_update_checker;
    private Docman_LockFactory&MockObject $lock_factory;

    protected function setUp(): void
    {
        $this->item_factory                  = $this->createMock(Docman_ItemFactory::class);
        $this->version_factory               = $this->createMock(Docman_VersionFactory::class);
        $this->event_manager                 = $this->createMock(EventManager::class);
        $this->on_going_upload_dao           = $this->createMock(DocumentOnGoingVersionToUploadDAO::class);
        $this->file_storage                  = $this->createMock(Docman_FileStorage::class);
        $this->user_manager                  = $this->createMock(UserManager::class);
        $this->adder                         = $this->createMock(DocmanItemsEventAdder::class);
        $this->project_manager               = $this->createMock(ProjectManager::class);
        $this->approval_table_updater        = $this->createMock(ApprovalTableUpdater::class);
        $this->approval_table_retriever      = $this->createMock(ApprovalTableRetriever::class);
        $this->approval_table_update_checker = $this->createMock(ApprovalTableUpdateActionChecker::class);
        $this->lock_factory                  = $this->createMock(Docman_LockFactory::class);
    }

    public function testDocumentIsAddedToTheDocumentManagerWhenTheUploadIsComplete(): void
    {
        $root = vfsStream::setup();

        $path_allocator = new UploadPathAllocator($root->url() . '/version');

        $upload_finisher = new VersionUploadFinisher(
            new NullLogger(),
            $path_allocator,
            $this->item_factory,
            $this->version_factory,
            $this->on_going_upload_dao,
            new DBTransactionExecutorPassthrough(),
            $this->file_storage,
            new Docman_MIMETypeDetector(),
            $this->user_manager,
            $this->lock_factory,
            $this->approval_table_updater,
            $this->approval_table_retriever,
            $this->approval_table_update_checker,
            new PostUpdateFileHandler($this->version_factory, $this->adder, $this->project_manager, $this->event_manager),
        );

        $item_id_being_created    = 12;
        $file_information         = new FileBeingUploadedInformation($item_id_being_created, 'Filename', 123, 0);
        $path_item_being_uploaded = $path_allocator->getPathForItemBeingUploaded($file_information);
        mkdir(dirname($path_item_being_uploaded), 0777, true);
        touch($path_item_being_uploaded);
        $this->on_going_upload_dao->method('searchDocumentVersionOngoingUploadByUploadID')->willReturn([
            'id'                    => $item_id_being_created,
            'parent_id'             => 3,
            'item_id'               => 20,
            'user_id'               => 101,
            'version_title'         => 'Title',
            'changelog'             => 'Description',
            'filename'              => 'Filename',
            'filesize'              => 123,
            'filetype'              => 'Filetype',
            'is_file_locked'        => false,
            'approval_table_action' => 'copy',
            'title'                 => 'New title',
            'description'           => '',
            'obsolescence_date'     => 125861251,
            'status'                => 101,
        ]);
        $item = $this->createMock(Docman_File::class);
        $item->method('getTitle')->willReturn('title');
        $item->method('getGroupId')->willReturn(101);
        $item->method('getId')->willReturn(20);
        $item->method('getParentId')->willReturn(3);
        $item->expects($this->once())->method('setCurrentVersion');
        $item->method('accept')->willReturn(true);

        $this->item_factory->method('getItemFromDb')->willReturn($item);
        $this->version_factory->method('getNextVersionNumber')->willReturn(2);

        $created_docman_version = $root->url() . '/created_version';
        touch($created_docman_version);
        $this->file_storage->expects($this->once())->method('copy')->willReturn($created_docman_version);

        $this->version_factory->expects($this->once())->method('create')->willReturn(true);

        $user = UserTestBuilder::buildWithDefaults();
        $this->user_manager->method('getUserByID')->willReturn($user);

        $this->event_manager->method('processEvent');
        $this->version_factory->method('getCurrentVersionForItem');

        $this->project_manager->method('getProjectById')->willReturn(ProjectTestBuilder::aProject()->build());

        $this->adder->method('addNotificationEvents');
        $this->adder->method('addLogEvents');

        $this->on_going_upload_dao->expects($this->once())->method('deleteByVersionID');
        $this->item_factory->expects($this->once())->method('update')->willReturn(true);

        $this->approval_table_retriever->method('hasApprovalTable')->willReturn(true);

        $file_information = new FileAlreadyUploadedInformation($item_id_being_created, 'Filename', 123);
        $this->lock_factory->method('unlock');

        $this->approval_table_update_checker->method('checkAvailableUpdateAction')->with('copy')->willReturn(true);

        $this->approval_table_updater->expects($this->once())->method('updateApprovalTable')->with($item, $user, 'copy');

        $upload_finisher->finishUpload(new NullServerRequest(), $file_information);

        self::assertFileDoesNotExist($path_item_being_uploaded);
    }

    public function testDocumentWithoutApprovalTableIsAddedToTheDocumentManagerWhenTheUploadIsComplete(): void
    {
        $root = vfsStream::setup();

        $path_allocator = new UploadPathAllocator($root->url() . '/version');

        $upload_finisher = new VersionUploadFinisher(
            new NullLogger(),
            $path_allocator,
            $this->item_factory,
            $this->version_factory,
            $this->on_going_upload_dao,
            new DBTransactionExecutorPassthrough(),
            $this->file_storage,
            new Docman_MIMETypeDetector(),
            $this->user_manager,
            $this->lock_factory,
            $this->approval_table_updater,
            $this->approval_table_retriever,
            $this->approval_table_update_checker,
            new PostUpdateFileHandler($this->version_factory, $this->adder, $this->project_manager, $this->event_manager),
        );

        $item_id_being_created    = 12;
        $file_information         = new FileBeingUploadedInformation($item_id_being_created, 'Filename', 123, 0);
        $path_item_being_uploaded = $path_allocator->getPathForItemBeingUploaded($file_information);
        mkdir(dirname($path_item_being_uploaded), 0777, true);
        touch($path_item_being_uploaded);
        $this->on_going_upload_dao->method('searchDocumentVersionOngoingUploadByUploadID')->willReturn([
            'id'                    => $item_id_being_created,
            'parent_id'             => 3,
            'item_id'               => 20,
            'user_id'               => 101,
            'version_title'         => 'Title',
            'changelog'             => 'Description',
            'filename'              => 'Filename',
            'filesize'              => 123,
            'filetype'              => 'Filetype',
            'is_file_locked'        => false,
            'approval_table_action' => 'copy',
            'title'                 => 'New title',
            'description'           => '',
            'obsolescence_date'     => 125861251,
            'status'                => 101,
        ]);
        $item = $this->createMock(Docman_File::class);
        $item->method('getTitle')->willReturn('title');
        $item->method('getGroupId')->willReturn(101);
        $item->method('getId')->willReturn(20);
        $item->method('getParentId')->willReturn(3);
        $item->expects(self::never())->method('setCurrentVersion');
        $item->method('accept')->willReturn(true);

        $this->item_factory->method('getItemFromDb')->willReturn($item);
        $this->version_factory->method('getNextVersionNumber')->willReturn(2);

        $created_docman_version = $root->url() . '/created_version';
        touch($created_docman_version);
        $this->file_storage->expects($this->once())->method('copy')->willReturn($created_docman_version);

        $this->version_factory->expects($this->once())->method('create')->willReturn(true);

        $user = UserTestBuilder::buildWithDefaults();
        $this->user_manager->method('getUserByID')->willReturn($user);

        $this->event_manager->method('processEvent');
        $this->version_factory->method('getCurrentVersionForItem');

        $this->project_manager->method('getProjectById')->willReturn(ProjectTestBuilder::aProject()->build());

        $this->adder->method('addNotificationEvents');
        $this->adder->method('addLogEvents');

        $this->on_going_upload_dao->expects($this->once())->method('deleteByVersionID');
        $this->item_factory->expects($this->once())->method('update')->willReturn(true);

        $this->approval_table_retriever->method('hasApprovalTable')->willReturn(false);

        $file_information = new FileAlreadyUploadedInformation($item_id_being_created, 'Filename', 123);

        $this->approval_table_update_checker->method('checkAvailableUpdateAction')->with('copy')->willReturn(true);

        $this->approval_table_updater->expects(self::never())->method('updateApprovalTable');
        $this->lock_factory->method('unlock');

        $upload_finisher->finishUpload(new NullServerRequest(), $file_information);

        self::assertFileDoesNotExist($path_item_being_uploaded);
    }

    public function testDocumentWithApprovalTableAndBadActionApprovalIsAddedToTheDocumentManagerWhenTheUploadIsComplete(): void
    {
        $root = vfsStream::setup();

        $path_allocator = new UploadPathAllocator($root->url() . '/version');

        $upload_finisher = new VersionUploadFinisher(
            new NullLogger(),
            $path_allocator,
            $this->item_factory,
            $this->version_factory,
            $this->on_going_upload_dao,
            new DBTransactionExecutorPassthrough(),
            $this->file_storage,
            new Docman_MIMETypeDetector(),
            $this->user_manager,
            $this->lock_factory,
            $this->approval_table_updater,
            $this->approval_table_retriever,
            $this->approval_table_update_checker,
            new PostUpdateFileHandler($this->version_factory, $this->adder, $this->project_manager, $this->event_manager)
        );

        $item_id_being_created    = 12;
        $file_information         = new FileBeingUploadedInformation($item_id_being_created, 'Filename', 123, 0);
        $path_item_being_uploaded = $path_allocator->getPathForItemBeingUploaded($file_information);
        mkdir(dirname($path_item_being_uploaded), 0777, true);
        touch($path_item_being_uploaded);
        $this->on_going_upload_dao->method('searchDocumentVersionOngoingUploadByUploadID')->willReturn([
            'id'                    => $item_id_being_created,
            'parent_id'             => 3,
            'item_id'               => 20,
            'user_id'               => 101,
            'version_title'         => 'Title',
            'changelog'             => 'Description',
            'filename'              => 'Filename',
            'filesize'              => 123,
            'filetype'              => 'Filetype',
            'is_file_locked'        => false,
            'approval_table_action' => 'blablabla',
            'title'                 => 'New title',
            'description'           => '',
            'obsolescence_date'     => 125861251,
            'status'                => 101,
        ]);
        $item = $this->createMock(Docman_File::class);
        $item->method('getTitle')->willReturn('title');
        $item->method('getGroupId')->willReturn(101);
        $item->method('getId')->willReturn(20);
        $item->method('getParentId')->willReturn(3);
        $item->expects(self::never())->method('setCurrentVersion');
        $item->method('accept')->willReturn(true);

        $this->item_factory->method('getItemFromDb')->willReturn($item);
        $this->version_factory->method('getNextVersionNumber')->willReturn(2);

        $created_docman_version = $root->url() . '/created_version';
        touch($created_docman_version);
        $this->file_storage->expects($this->once())->method('copy')->willReturn($created_docman_version);

        $this->version_factory->expects($this->once())->method('create')->willReturn(true);

        $user = UserTestBuilder::buildWithDefaults();
        $this->user_manager->method('getUserByID')->willReturn($user);

        $this->event_manager->method('processEvent');
        $this->version_factory->method('getCurrentVersionForItem');

        $this->project_manager->method('getProjectById')->willReturn(ProjectTestBuilder::aProject()->build());

        $this->adder->method('addNotificationEvents');
        $this->adder->method('addLogEvents');

        $this->on_going_upload_dao->expects($this->once())->method('deleteByVersionID');
        $this->item_factory->expects($this->once())->method('update')->willReturn(true);

        $this->approval_table_retriever->method('hasApprovalTable')->willReturn(true);

        $file_information = new FileAlreadyUploadedInformation($item_id_being_created, 'Filename', 123);
        $this->lock_factory->method('unlock');

        $this->approval_table_update_checker->method('checkAvailableUpdateAction')->with('blablabla')->willReturn(false);

        $this->approval_table_updater->expects(self::never())->method('updateApprovalTable');

        $upload_finisher->finishUpload(new NullServerRequest(), $file_information);

        self::assertFileDoesNotExist($path_item_being_uploaded);
    }
}
