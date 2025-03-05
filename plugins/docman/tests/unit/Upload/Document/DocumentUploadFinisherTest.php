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

declare(strict_types=1);

namespace Tuleap\Docman\Upload\Document;

use Docman_File;
use Docman_FileStorage;
use Docman_ItemDao;
use Docman_ItemFactory;
use Docman_MIMETypeDetector;
use Docman_Version;
use Docman_VersionFactory;
use EventManager;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use Psr\Log\NullLogger;
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
final class DocumentUploadFinisherTest extends TestCase
{
    private DocmanItemsEventAdder&MockObject $event_adder;
    private ProjectManager&MockObject $project_manager;
    private Docman_ItemFactory&MockObject $item_factory;
    private Docman_VersionFactory&MockObject $version_factory;
    private EventManager&MockObject $event_manager;
    private DocumentOngoingUploadDAO&MockObject $on_going_upload_dao;
    private Docman_ItemDao&MockObject $item_dao;
    private Docman_FileStorage&MockObject $file_storage;
    private UserManager&MockObject $user_manager;

    protected function setUp(): void
    {
        $this->item_factory        = $this->createMock(Docman_ItemFactory::class);
        $this->version_factory     = $this->createMock(Docman_VersionFactory::class);
        $this->event_manager       = $this->createMock(EventManager::class);
        $this->on_going_upload_dao = $this->createMock(DocumentOngoingUploadDAO::class);
        $this->item_dao            = $this->createMock(Docman_ItemDao::class);
        $this->file_storage        = $this->createMock(Docman_FileStorage::class);
        $this->user_manager        = $this->createMock(UserManager::class);
        $this->event_adder         = $this->createMock(DocmanItemsEventAdder::class);
        $this->project_manager     = $this->createMock(ProjectManager::class);
    }

    public function testDocumentIsAddedToTheDocumentManagerWhenTheUploadIsComplete(): void
    {
        $root = vfsStream::setup();

        $path_allocator = new UploadPathAllocator($root->url() . '/document');

        $upload_finisher = new DocumentUploadFinisher(
            new NullLogger(),
            $path_allocator,
            $this->item_factory,
            $this->version_factory,
            $this->event_manager,
            $this->on_going_upload_dao,
            $this->item_dao,
            $this->file_storage,
            new Docman_MIMETypeDetector(),
            $this->user_manager,
            new DBTransactionExecutorPassthrough(),
            new PostUpdateFileHandler($this->version_factory, $this->event_adder, $this->project_manager, $this->event_manager),
        );

        $item_id_being_created    = 12;
        $file_information         = new FileBeingUploadedInformation($item_id_being_created, 'Filename', 123, 0);
        $path_item_being_uploaded = $path_allocator->getPathForItemBeingUploaded($file_information);
        mkdir(dirname($path_item_being_uploaded), 0777, true);
        touch($path_item_being_uploaded);
        $item = $this->createStub(Docman_File::class);
        $item->method('getOwnerId')->willReturn(333);
        $item->method('getGroupId')->willReturn(102);
        $item->method('getParentId')->willReturn(3);
        $item->method('accept')->willReturn(true);
        $this->item_factory->method('getItemFromDB')->willReturn(null, $item, $item);
        $this->on_going_upload_dao->method('searchDocumentOngoingUploadByItemID')->willReturn([
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
        ]);
        $this->on_going_upload_dao->expects(self::once())->method('deleteByItemID');
        $this->item_factory->expects(self::once())->method('create')->willReturn(true);
        $created_docman_file = $root->url() . '/created_file';
        touch($created_docman_file);
        $this->file_storage->expects(self::once())->method('copy')->willReturn($created_docman_file);
        $this->version_factory->expects(self::once())->method('create')->willReturn(true);
        $this->version_factory->method('getCurrentVersionForItem')->willReturn(new Docman_Version());
        $this->event_manager->method('processEvent');
        $this->user_manager->method('getUserByID')->willReturn(UserTestBuilder::buildWithDefaults());
        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager->method('getProjectById')->willReturn($project);
        $this->event_adder->expects(self::once())->method('addNotificationEvents')->with($project);
        $this->event_adder->method('addLogEvents');

        $file_information = new FileAlreadyUploadedInformation($item_id_being_created, 'Filename', 123);

        $upload_finisher->finishUpload(new NullServerRequest(), $file_information);

        self::assertFileDoesNotExist($path_item_being_uploaded);
    }
}
