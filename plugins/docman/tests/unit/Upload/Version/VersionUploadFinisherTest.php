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

namespace Tuleap\Docman\Upload\Version;

use Docman_File;
use Docman_ItemFactory;
use Docman_VersionFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdater;
use Tuleap\Docman\PostUpdate\PostUpdateFileHandler;
use Tuleap\Docman\REST\v1\DocmanItemsEventAdder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Upload\FileAlreadyUploadedInformation;
use Tuleap\Upload\FileBeingUploadedInformation;
use Tuleap\Upload\UploadPathAllocator;

final class VersionUploadFinisherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private $project_manager;
    private $adder;
    private $logger;
    private $item_factory;
    private $version_factory;
    private $event_manager;
    private $on_going_upload_dao;
    private $file_storage;
    private $user_manager;
    private $approval_table_updater;
    private $approval_table_retriever;
    private $approval_table_update_checker;
    private $lock_factory;

    protected function setUp(): void
    {
        $this->logger                        = Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->item_factory                  = Mockery::mock(Docman_ItemFactory::class);
        $this->version_factory               = Mockery::mock(Docman_VersionFactory::class);
        $this->event_manager                 = Mockery::mock(\EventManager::class);
        $this->on_going_upload_dao           = Mockery::mock(DocumentOnGoingVersionToUploadDAO::class);
        $this->file_storage                  = Mockery::mock(\Docman_FileStorage::class);
        $this->user_manager                  = Mockery::mock(\UserManager::class);
        $this->adder                         = Mockery::mock(DocmanItemsEventAdder::class);
        $this->project_manager               = Mockery::mock(\ProjectManager::class);
        $this->approval_table_updater        = Mockery::mock(ApprovalTableUpdater::class);
        $this->approval_table_retriever      = Mockery::mock(ApprovalTableRetriever::class);
        $this->approval_table_update_checker = Mockery::mock(ApprovalTableUpdateActionChecker::class);
        $this->lock_factory                  = Mockery::mock(\Docman_LockFactory::class);
    }

    public function testDocumentIsAddedToTheDocumentManagerWhenTheUploadIsComplete(): void
    {
        $root = vfsStream::setup();

        $path_allocator = new UploadPathAllocator($root->url() . '/version');

        $upload_finisher = new VersionUploadFinisher(
            $this->logger,
            $path_allocator,
            $this->item_factory,
            $this->version_factory,
            $this->on_going_upload_dao,
            new DBTransactionExecutorPassthrough(),
            $this->file_storage,
            new \Docman_MIMETypeDetector(),
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
        $this->on_going_upload_dao->shouldReceive('wrapAtomicOperations')->with(
            \Mockery::on(
                function (callable $operations) {
                    $operations($this->on_going_upload_dao);
                    return true;
                }
            )
        );
        $this->on_going_upload_dao->shouldReceive('searchDocumentVersionOngoingUploadByUploadID')->andReturns(
            [
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
            ]
        );
        $item = Mockery::mock(Docman_File::class);
        $item->shouldReceive('getTitle')->andReturn('title');
        $item->shouldReceive('getGroupId')->andReturn(101);
        $item->shouldReceive('getId')->andReturn(20);
        $item->shouldReceive('getParentId')->andReturn(3);
        $item->shouldReceive('setCurrentVersion')->once();
        $item->shouldReceive('accept')->andReturn(true);

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn($item);
        $this->version_factory->shouldReceive('getNextVersionNumber')->andReturn(2);

        $created_docman_version = $root->url() . '/created_version';
        touch($created_docman_version);
        $this->file_storage->shouldReceive('copy')->once()->andReturns($created_docman_version);

        $this->version_factory->shouldReceive('create')->once()->andReturns(true);

        $user = \Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getUserByID')->andReturns($user);

        $this->event_manager->shouldReceive('processEvent');
        $this->logger->shouldReceive('debug');
        $this->version_factory->shouldReceive('getCurrentVersionForItem');

        $this->project_manager->shouldReceive('getProjectById')->andReturn(Mockery::mock(\Project::class));

        $this->adder->shouldReceive('addNotificationEvents');
        $this->adder->shouldReceive('addLogEvents');

        $this->on_going_upload_dao->shouldReceive('deleteByVersionID')->once();
        $this->item_factory->shouldReceive('update')->once()->andReturn(true);

        $this->approval_table_retriever->shouldReceive('hasApprovalTable')->andReturn(true);

        $file_information = new FileAlreadyUploadedInformation($item_id_being_created, 'Filename', 123);
        $this->lock_factory->shouldReceive('unlock');

        $this->approval_table_update_checker
            ->shouldReceive('checkAvailableUpdateAction')
            ->with('copy')
            ->andReturn(true);

        $this->approval_table_updater->shouldReceive('updateApprovalTable')->withArgs([$item, $user, 'copy'])->once();

        $upload_finisher->finishUpload(new NullServerRequest(), $file_information);

        $this->assertFileDoesNotExist($path_item_being_uploaded);
    }

    public function testDocumentWithoutApprovalTableIsAddedToTheDocumentManagerWhenTheUploadIsComplete(): void
    {
        $root = vfsStream::setup();

        $path_allocator = new UploadPathAllocator($root->url() . '/version');

        $upload_finisher = new VersionUploadFinisher(
            $this->logger,
            $path_allocator,
            $this->item_factory,
            $this->version_factory,
            $this->on_going_upload_dao,
            new DBTransactionExecutorPassthrough(),
            $this->file_storage,
            new \Docman_MIMETypeDetector(),
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
        $this->on_going_upload_dao->shouldReceive('wrapAtomicOperations')->with(
            \Mockery::on(
                function (callable $operations) {
                    $operations($this->on_going_upload_dao);
                    return true;
                }
            )
        );
        $this->on_going_upload_dao->shouldReceive('searchDocumentVersionOngoingUploadByUploadID')->andReturns(
            [
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
            ]
        );
        $item = Mockery::mock(Docman_File::class);
        $item->shouldReceive('getTitle')->andReturn('title');
        $item->shouldReceive('getGroupId')->andReturn(101);
        $item->shouldReceive('getId')->andReturn(20);
        $item->shouldReceive('getParentId')->andReturn(3);
        $item->shouldReceive('setCurrentVersion')->never();
        $item->shouldReceive('accept')->andReturn(true);

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn($item);
        $this->version_factory->shouldReceive('getNextVersionNumber')->andReturn(2);

        $created_docman_version = $root->url() . '/created_version';
        touch($created_docman_version);
        $this->file_storage->shouldReceive('copy')->once()->andReturns($created_docman_version);

        $this->version_factory->shouldReceive('create')->once()->andReturns(true);

        $user = \Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getUserByID')->andReturns($user);

        $this->event_manager->shouldReceive('processEvent');
        $this->logger->shouldReceive('debug');
        $this->version_factory->shouldReceive('getCurrentVersionForItem');

        $this->project_manager->shouldReceive('getProjectById')->andReturn(Mockery::mock(\Project::class));

        $this->adder->shouldReceive('addNotificationEvents');
        $this->adder->shouldReceive('addLogEvents');

        $this->on_going_upload_dao->shouldReceive('deleteByVersionID')->once();
        $this->item_factory->shouldReceive('update')->once()->andReturn(true);

        $this->approval_table_retriever->shouldReceive('hasApprovalTable')->andReturn(false);

        $file_information = new FileAlreadyUploadedInformation($item_id_being_created, 'Filename', 123);

        $this->approval_table_update_checker
            ->shouldReceive('checkAvailableUpdateAction')
            ->with('copy')
            ->andReturn(true);

        $this->approval_table_updater->shouldReceive('updateApprovalTable')->never();
        $this->lock_factory->shouldReceive('unlock');

        $upload_finisher->finishUpload(new NullServerRequest(), $file_information);

        $this->assertFileDoesNotExist($path_item_being_uploaded);
    }

    public function testDocumentWithApprovalTableAndBadActionApprovalIsAddedToTheDocumentManagerWhenTheUploadIsComplete(): void
    {
        $root = vfsStream::setup();

        $path_allocator = new UploadPathAllocator($root->url() . '/version');

        $upload_finisher = new VersionUploadFinisher(
            $this->logger,
            $path_allocator,
            $this->item_factory,
            $this->version_factory,
            $this->on_going_upload_dao,
            new DBTransactionExecutorPassthrough(),
            $this->file_storage,
            new \Docman_MIMETypeDetector(),
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
        $this->on_going_upload_dao->shouldReceive('wrapAtomicOperations')->with(
            \Mockery::on(
                function (callable $operations) {
                    $operations($this->on_going_upload_dao);
                    return true;
                }
            )
        );
        $this->on_going_upload_dao->shouldReceive('searchDocumentVersionOngoingUploadByUploadID')->andReturns(
            [
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
            ]
        );
        $item = Mockery::mock(Docman_File::class);
        $item->shouldReceive('getTitle')->andReturn('title');
        $item->shouldReceive('getGroupId')->andReturn(101);
        $item->shouldReceive('getId')->andReturn(20);
        $item->shouldReceive('getParentId')->andReturn(3);
        $item->shouldReceive('setCurrentVersion')->never();
        $item->shouldReceive('accept')->andReturn(true);

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn($item);
        $this->version_factory->shouldReceive('getNextVersionNumber')->andReturn(2);

        $created_docman_version = $root->url() . '/created_version';
        touch($created_docman_version);
        $this->file_storage->shouldReceive('copy')->once()->andReturns($created_docman_version);

        $this->version_factory->shouldReceive('create')->once()->andReturns(true);

        $user = \Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getUserByID')->andReturns($user);

        $this->event_manager->shouldReceive('processEvent');
        $this->logger->shouldReceive('debug');
        $this->version_factory->shouldReceive('getCurrentVersionForItem');

        $this->project_manager->shouldReceive('getProjectById')->andReturn(Mockery::mock(\Project::class));

        $this->adder->shouldReceive('addNotificationEvents');
        $this->adder->shouldReceive('addLogEvents');

        $this->on_going_upload_dao->shouldReceive('deleteByVersionID')->once();
        $this->item_factory->shouldReceive('update')->once()->andReturn(true);

        $this->approval_table_retriever->shouldReceive('hasApprovalTable')->andReturn(true);

        $file_information = new FileAlreadyUploadedInformation($item_id_being_created, 'Filename', 123);
        $this->lock_factory->shouldReceive('unlock');

        $this->approval_table_update_checker
            ->shouldReceive('checkAvailableUpdateAction')
            ->with('blablabla')
            ->andReturn(false);

        $this->approval_table_updater->shouldReceive('updateApprovalTable')->never();

        $upload_finisher->finishUpload(new NullServerRequest(), $file_information);

        $this->assertFileDoesNotExist($path_item_being_uploaded);
    }
}
