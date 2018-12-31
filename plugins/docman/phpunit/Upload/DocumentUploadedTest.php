<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Docman\Upload;

use Docman_ItemFactory;
use Docman_VersionFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class DocumentUploadedTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $logger;
    private $item_factory;
    private $version_factory;
    private $permission_manager;
    private $event_manager;
    private $on_going_upload_dao;
    private $file_storage;
    private $user_manager;

    protected function setUp()
    {
        \ForgeConfig::store();
        $this->logger              = \Mockery::mock(\Logger::class);
        $this->item_factory        = \Mockery::mock(Docman_ItemFactory::class);
        $this->version_factory     = \Mockery::mock(Docman_VersionFactory::class);
        $this->permission_manager  = \Mockery::mock(\PermissionsManager::class);
        $this->event_manager       = \Mockery::mock(\EventManager::class);
        $this->on_going_upload_dao = \Mockery::mock(DocumentOngoingUploadDAO::class);
        $this->file_storage        = \Mockery::mock(\Docman_FileStorage::class);
        $this->user_manager        = \Mockery::mock(\UserManager::class);
    }

    protected function tearDown()
    {
        \ForgeConfig::restore();
    }

    public function testDocumentIsAddedToTheDocumentManagerWhenTheUploadIsComplete()
    {
        $root = vfsStream::setup();
        \ForgeConfig::set('tmp_dir', $root->url());

        $path_allocator = new DocumentUploadPathAllocator();

        $document_uploaded_subscriber = new DocumentUploaded(
            $this->logger,
            $path_allocator,
            $this->item_factory,
            $this->version_factory,
            $this->permission_manager,
            $this->event_manager,
            $this->on_going_upload_dao,
            $this->file_storage,
            new \Docman_MIMETypeDetector(),
            $this->user_manager
        );


        $item_id_being_created    = 12;
        $path_item_being_uploaded = $path_allocator->getPathForItemBeingUploaded($item_id_being_created);
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
        $this->item_factory->shouldReceive('getItemFromDB')->andReturns(null, \Mockery::spy(\Docman_Item::class));
        $this->on_going_upload_dao->shouldReceive('searchDocumentOngoingUploadByItemID')->andReturns(
            [
                'item_id'     => $item_id_being_created,
                'parent_id'   => 3,
                'group_id'    => 102,
                'user_id'     => 101,
                'title'       => 'Title',
                'description' => 'Description',
                'filename' => 'Filename',
                'filesize' => 123,
                'filetype' => 'Filetype',
            ]
        );
        $this->on_going_upload_dao->shouldReceive('deleteByItemID')->once();
        $this->item_factory->shouldReceive('create')->once()->andReturns(true);
        $this->permission_manager->shouldReceive('clonePermissions')->once()->andReturns(true);
        $created_docman_file = $root->url() . '/created_file';
        touch($created_docman_file);
        $this->file_storage->shouldReceive('copy')->once()->andReturns($created_docman_file);
        $this->version_factory->shouldReceive('create')->once()->andReturns(true);
        $this->event_manager->shouldReceive('processEvent');
        $this->user_manager->shouldReceive('getUserByID')->andReturns(\Mockery::mock(\PFUser::class));
        $this->logger->shouldReceive('debug');

        $request = \Mockery::mock(\Psr\Http\Message\ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->with('item_id')->andReturns($item_id_being_created);

        $document_uploaded_subscriber->notify($request);

        $this->assertFileNotExists($path_item_being_uploaded);
    }

    /**
     * @expectedException \LogicException
     */
    public function testNotificationIsRejectedIsTheRequestDoesNotContainsTheExpectedInformation()
    {
        $document_uploaded_subscriber = new DocumentUploaded(
            $this->logger,
            new DocumentUploadPathAllocator(),
            $this->item_factory,
            $this->version_factory,
            $this->permission_manager,
            $this->event_manager,
            $this->on_going_upload_dao,
            $this->file_storage,
            new \Docman_MIMETypeDetector(),
            $this->user_manager
        );

        $request = \Mockery::mock(\Psr\Http\Message\ServerRequestInterface::class);
        $request->shouldReceive('getAttribute')->andReturns(null);

        $document_uploaded_subscriber->notify($request);
    }
}
