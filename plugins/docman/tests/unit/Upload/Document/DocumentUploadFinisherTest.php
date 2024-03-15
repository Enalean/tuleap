<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use Docman_ItemFactory;
use Docman_VersionFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use Tuleap\Docman\PostUpdate\PostUpdateFileHandler;
use Tuleap\Docman\REST\v1\DocmanItemsEventAdder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Upload\FileAlreadyUploadedInformation;
use Tuleap\Upload\FileBeingUploadedInformation;
use Tuleap\Upload\UploadPathAllocator;

final class DocumentUploadFinisherTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|DocmanItemsEventAdder
     */
    private $event_adder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $project_manager;
    private $logger;
    private $item_factory;
    private $version_factory;
    private $event_manager;
    private $on_going_upload_dao;
    private $item_dao;
    private $file_storage;
    private $user_manager;

    protected function setUp(): void
    {
        $this->logger              = \Mockery::mock(\Psr\Log\LoggerInterface::class);
        $this->item_factory        = \Mockery::mock(Docman_ItemFactory::class);
        $this->version_factory     = \Mockery::mock(Docman_VersionFactory::class);
        $this->event_manager       = \Mockery::mock(\EventManager::class);
        $this->on_going_upload_dao = \Mockery::mock(DocumentOngoingUploadDAO::class);
        $this->item_dao            = \Mockery::mock(\Docman_ItemDao::class);
        $this->file_storage        = \Mockery::mock(\Docman_FileStorage::class);
        $this->user_manager        = \Mockery::mock(\UserManager::class);
        $this->event_adder         = \Mockery::mock(DocmanItemsEventAdder::class);
        $this->project_manager     = \Mockery::mock(\ProjectManager::instance());
    }

    public function testDocumentIsAddedToTheDocumentManagerWhenTheUploadIsComplete(): void
    {
        $root = vfsStream::setup();

        $path_allocator = new UploadPathAllocator($root->url() . '/document');

        $upload_finisher = new DocumentUploadFinisher(
            $this->logger,
            $path_allocator,
            $this->item_factory,
            $this->version_factory,
            $this->event_manager,
            $this->on_going_upload_dao,
            $this->item_dao,
            $this->file_storage,
            new \Docman_MIMETypeDetector(),
            $this->user_manager,
            new DBTransactionExecutorPassthrough(),
            new PostUpdateFileHandler($this->version_factory, $this->event_adder, $this->project_manager, $this->event_manager),
        );

        $item_id_being_created    = 12;
        $file_information         = new FileBeingUploadedInformation($item_id_being_created, 'Filename', 123, 0);
        $path_item_being_uploaded = $path_allocator->getPathForItemBeingUploaded($file_information);
        mkdir(dirname($path_item_being_uploaded), 0777, true);
        touch($path_item_being_uploaded);
        $item = $this->createStub(\Docman_File::class);
        $item->method('getOwnerId')->willReturn(333);
        $item->method('getGroupId')->willReturn(102);
        $item->method('getParentId')->willReturn(3);
        $item->method('accept')->willReturn(true);
        $this->item_factory->shouldReceive('getItemFromDB')->andReturns(null, $item);
        $this->on_going_upload_dao->shouldReceive('searchDocumentOngoingUploadByItemID')->andReturns(
            [
                'item_id'           => $item_id_being_created,
                'parent_id'         => 3,
                'group_id'          => 102,
                'user_id'           => 101,
                'title'             => 'Title',
                'description'       => 'Description',
                'filename'          => 'Filename',
                'filesize'          => 123,
                'filetype'          => 'Filetype',
                'status'            => 'approved',
                'obsolescence_date' => '2020-03-06',
            ]
        );
        $this->on_going_upload_dao->shouldReceive('deleteByItemID')->once();
        $this->item_factory->shouldReceive('create')->once()->andReturns(true);
        $created_docman_file = $root->url() . '/created_file';
        touch($created_docman_file);
        $this->file_storage->shouldReceive('copy')->once()->andReturns($created_docman_file);
        $this->version_factory->shouldReceive('create')->once()->andReturns(true);
        $this->version_factory->shouldReceive('getCurrentVersionForItem')->andReturns(\Mockery::mock(\Docman_Version::class));
        $this->event_manager->shouldReceive('processEvent');
        $this->user_manager->shouldReceive('getUserByID')->andReturns(\Mockery::mock(\PFUser::class));
        $this->logger->shouldReceive('debug');
        $project = \Mockery::mock(\Project::class);
        $this->project_manager->shouldReceive('getProjectById')->andReturn($project);
        $this->event_adder->shouldReceive('addNotificationEvents')->withArgs([$project])->once();
        $this->event_adder->shouldReceive('addLogEvents');

        $file_information = new FileAlreadyUploadedInformation($item_id_being_created, 'Filename', 123);

        $upload_finisher->finishUpload(new NullServerRequest(), $file_information);

        $this->assertFileDoesNotExist($path_item_being_uploaded);
    }
}
