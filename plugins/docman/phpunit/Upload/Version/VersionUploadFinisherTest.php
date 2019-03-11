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

namespace Tuleap\Docman\Upload\Version;

use Docman_File;
use Docman_ItemFactory;
use Docman_VersionFactory;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\REST\v1\DocmanItemsEventAdder;
use Tuleap\Docman\Upload\DocumentAlreadyUploadedInformation;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Upload\FileBeingUploadedInformation;

class VersionUploadFinisherTest extends TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox;

    private $project_manager;
    private $adder;
    private $logger;
    private $item_factory;
    private $version_factory;
    private $event_manager;
    private $on_going_upload_dao;
    private $file_storage;
    private $user_manager;

    protected function setUp() : void
    {
        $this->logger              = Mockery::mock(\Logger::class);
        $this->item_factory        = Mockery::mock(Docman_ItemFactory::class);
        $this->version_factory     = Mockery::mock(Docman_VersionFactory::class);
        $this->event_manager       = Mockery::mock(\EventManager::class);
        $this->on_going_upload_dao = Mockery::mock(DocumentOnGoingVersionToUploadDAO::class);
        $this->file_storage        = Mockery::mock(\Docman_FileStorage::class);
        $this->user_manager        = Mockery::mock(\UserManager::class);
        $this->adder               = Mockery::mock(DocmanItemsEventAdder::class);
        $this->project_manager     = Mockery::mock(\ProjectManager::class);
    }

    public function testDocumentIsAddedToTheDocumentManagerWhenTheUploadIsComplete() : void
    {
        $root = vfsStream::setup();
        \ForgeConfig::set('tmp_dir', $root->url());

        $path_allocator = new VersionUploadPathAllocator();

        $upload_finisher = new VersionUploadFinisher(
            $this->logger,
            $path_allocator,
            $this->item_factory,
            $this->version_factory,
            $this->event_manager,
            $this->on_going_upload_dao,
            new DBTransactionExecutorPassthrough(),
            $this->file_storage,
            new \Docman_MIMETypeDetector(),
            $this->user_manager,
            $this->adder,
            $this->project_manager
        );


        $item_id_being_created    = 12;
        $file_information = new FileBeingUploadedInformation($item_id_being_created, 123, 0);
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
                'id'            => $item_id_being_created,
                'parent_id'     => 3,
                'item_id'       => 20,
                'user_id'       => 101,
                'version_title' => 'Title',
                'changelog'     => 'Description',
                'filename'      => 'Filename',
                'filesize'      => 123,
                'filetype'      => 'Filetype',
            ]
        );
        $item = Mockery::mock(Docman_File::class);
        $item->shouldReceive('getTitle')->andReturn('title');
        $item->shouldReceive('getGroupId')->andReturn(101);
        $item->shouldReceive('getId')->andReturn(20);
        $item->shouldReceive('getParentId')->andReturn(3);
        $this->item_factory->shouldReceive('getItemFromDb')->andReturn($item);
        $this->version_factory->shouldReceive('getNextVersionNumber')->andReturn(2);

        $created_docman_version = $root->url() . '/created_version';
        touch($created_docman_version);
        $this->file_storage->shouldReceive('copy')->once()->andReturns($created_docman_version);

        $this->version_factory->shouldReceive('create')->once()->andReturns(true);
        $this->user_manager->shouldReceive('getUserByID')->andReturns(\Mockery::mock(\PFUser::class));

        $this->event_manager->shouldReceive('processEvent');
        $this->logger->shouldReceive('debug');
        $this->version_factory->shouldReceive('getCurrentVersionForItem');

        $this->project_manager->shouldReceive('getProject')->andReturn(Mockery::mock(\Project::class));

        $this->adder->shouldReceive('addNotificationEvents');
        $this->adder->shouldReceive('addLogEvents');

        $this->on_going_upload_dao->shouldReceive('deleteByVersionID')->once();
        $this->item_factory->shouldReceive('update')->once()->andReturn(true);

        $file_information = new DocumentAlreadyUploadedInformation($item_id_being_created, 123);

        $upload_finisher->finishUpload($file_information);

        $this->assertFileNotExists($path_item_being_uploaded);
    }
}
