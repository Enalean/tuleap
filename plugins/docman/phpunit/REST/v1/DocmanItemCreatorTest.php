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

namespace Tuleap\Docman\REST\v1;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\Upload\DocumentOngoingUploadRetriever;
use Tuleap\Docman\Upload\DocumentToUpload;
use Tuleap\Docman\Upload\DocumentToUploadCreator;

class DocmanItemCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $type_retriever;
    private $creator_visitor;
    private $permissions_manager;
    private $event_manager;
    private $item_factory;
    private $document_ongoing_upload_retriever;
    private $document_to_upload_creator;
    private $link_version_factory;
    private $file_storage;
    private $version_factory;
    /**
     * @var vfsStreamDirectory
     */
    private $docman_file_system;

    private $empty_file_to_upload_finisher;

    public function setUp()
    {
        $this->permissions_manager  = \Mockery::mock(\PermissionsManager::class);
        $this->event_manager        = \Mockery::mock(\EventManager::class);
        $this->link_version_factory = \Mockery::mock(\Docman_LinkVersionFactory::class);
        $this->file_storage         = \Mockery::mock(\Docman_FileStorage::class);
        $this->version_factory      = \Mockery::mock(\Docman_VersionFactory::class);
        $this->creator_visitor      = new AfterItemCreationVisitor(
            $this->permissions_manager,
            $this->event_manager,
            $this->link_version_factory,
            $this->file_storage,
            $this->version_factory
        );

        $this->item_factory                      = \Mockery::mock(\Docman_ItemFactory::class);
        $this->document_ongoing_upload_retriever = \Mockery::mock(DocumentOngoingUploadRetriever::class);
        $this->document_to_upload_creator        = \Mockery::mock(DocumentToUploadCreator::class);

        $this->directorySetup();
        $this->empty_file_to_upload_finisher     = \Mockery::mock(EmptyFileToUploadFinisher::class);
    }

    public function testEmptyDocumentCanBeCreated()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation            = new DocmanItemPOSTRepresentation();
        $post_representation->type      = ItemRepresentation::TYPE_EMPTY;
        $post_representation->title     = 'Title';
        $post_representation->parent_id = 11;

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);
        $this->event_manager->shouldReceive('processEvent');

        $created_item = \Mockery::mock(\Docman_Empty::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with('Title', '', 11, 100, 222, PLUGIN_DOCMAN_ITEM_TYPE_EMPTY, null, null)
            ->once()
            ->andReturns($created_item);
        $this->permissions_manager->shouldReceive('clonePermissions')->once();

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $created_item_representation = $item_creator->create(
            $parent_item,
            $user,
            $project,
            $post_representation,
            $current_time,
            true
        );

        $this->assertSame(12, $created_item_representation->id);
        $this->assertNull($created_item_representation->file_properties);
    }

    public function testWikiDocumentCanBeCreated()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                  = new DocmanItemPOSTRepresentation();
        $post_representation->type            = ItemRepresentation::TYPE_WIKI;
        $post_representation->title           = 'Title';
        $post_representation->parent_id       = 11;
        $post_representation->wiki_properties = json_decode(json_encode(["page_name" => "Monchichi"]));

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);
        $project->shouldReceive('usesWiki')->andReturn(true);
        $this->event_manager->shouldReceive('processEvent');

        $created_item = \Mockery::mock(\Docman_Empty::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with('Title', '', 11, 100, 222, PLUGIN_DOCMAN_ITEM_TYPE_WIKI, "Monchichi", null)
            ->once()
            ->andReturns($created_item);
        $this->permissions_manager->shouldReceive('clonePermissions')->once();

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $created_item_representation = $item_creator->create(
            $parent_item,
            $user,
            $project,
            $post_representation,
            $current_time,
            true
        );

        $this->assertSame(12, $created_item_representation->id);
        $this->assertNull($created_item_representation->file_properties);
    }

    /**
     * @expectedException \Luracast\Restler\RestException
     * @expectedExceptionCode 400
     */
    public function testWikiDocumentCannotBeCreatedIfServiceWikiIsNotAvailable()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                  = new DocmanItemPOSTRepresentation();
        $post_representation->type            = ItemRepresentation::TYPE_WIKI;
        $post_representation->title           = 'Title';
        $post_representation->parent_id       = 11;
        $post_representation->wiki_properties = json_decode(json_encode(["page_name" => "Monchichi"]));

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);
        $this->event_manager->shouldReceive('processEvent');

        $created_item = \Mockery::mock(\Docman_Empty::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->never();

        $project->shouldReceive('usesWiki')->andReturn(false);
        $project->shouldReceive('getUnixName')->once();

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $item_creator->create(
            $parent_item,
            $user,
            $project,
            $post_representation,
            $current_time,
            true
        );
    }

    public function testFileDocumentCanBeCreated()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                            = new DocmanItemPOSTRepresentation();
        $post_representation->type                      = ItemRepresentation::TYPE_FILE;
        $post_representation->title                     = 'Title';
        $post_representation->parent_id                 = 11;
        $file_properties_post_representation            = new FilePropertiesPOSTRepresentation();
        $file_properties_post_representation->file_size = 123456;
        $file_properties_post_representation->file_name = 'myfile';
        $post_representation->file_properties           = $file_properties_post_representation;

        $this->document_to_upload_creator->shouldReceive('create')->once()->andReturns(new DocumentToUpload(12));

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $created_item_representation = $item_creator->create(
            $parent_item,
            $user,
            $project,
            $post_representation,
            $current_time,
            true
        );

        $this->assertSame(12, $created_item_representation->id);
        $this->assertNotNull($created_item_representation->file_properties);
    }

    /**
     * @expectedException \Luracast\Restler\RestException
     * @expectedExceptionCode 409
     */
    public function testDocumentCreationWhenAFileIsBeingUploadedForItIsRejected()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation            = new DocmanItemPOSTRepresentation();
        $post_representation->type      = ItemRepresentation::TYPE_EMPTY;
        $post_representation->title     = 'Title';
        $post_representation->parent_id = 11;

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(true);

        $item_creator->create(
            $parent_item,
            $user,
            $project,
            $post_representation,
            $current_time,
            true
        );
    }

    public function testLinkDocumentCanBeCreated()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                  = new DocmanItemPOSTRepresentation();
        $post_representation->type            = ItemRepresentation::TYPE_LINK;
        $post_representation->title           = 'Mie faboulouse linke';
        $post_representation->parent_id       = 11;
        $post_representation->link_properties = json_decode(json_encode(['link_url' => 'https://my.example.test']));

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);

        $this->event_manager->shouldReceive('processEvent');

        $created_item = \Mockery::mock(\Docman_Link::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with(
                'Mie faboulouse linke',
                '',
                11,
                100,
                222,
                PLUGIN_DOCMAN_ITEM_TYPE_LINK,
                null,
                'https://my.example.test'
            )
            ->once()
            ->andReturns($created_item);

        $this->permissions_manager->shouldReceive('clonePermissions')->once();

        $this->link_version_factory
            ->shouldReceive('create')
            ->with($created_item, 'Initial version', 'Initial version', $current_time->getTimestamp())
            ->once()
            ->andReturn(true);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $created_item_representation = $item_creator->create(
            $parent_item,
            $user,
            $project,
            $post_representation,
            $current_time,
            true
        );

        $this->assertSame(12, $created_item_representation->id);
        $this->assertNull($created_item_representation->file_properties);
    }

    public function testFolderCanBeCreated(): void
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation            = new DocmanItemPOSTRepresentation();
        $post_representation->type      = ItemRepresentation::TYPE_FOLDER;
        $post_representation->title     = 'Title';
        $post_representation->parent_id = 11;

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);
        $this->event_manager->shouldReceive('processEvent');

        $created_item = \Mockery::mock(\Docman_Folder::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with('Title', '', 11, 100, 222, PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, null, null)
            ->once()
            ->andReturns($created_item);
        $this->permissions_manager->shouldReceive('clonePermissions')->once();

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(false);

        $created_item_representation = $item_creator->create(
            $parent_item,
            $user,
            $project,
            $post_representation,
            $current_time,
            true
        );

        $this->assertSame(12, $created_item_representation->id);
        $this->assertNull($created_item_representation->file_properties);
    }

    public function testEmbeddedFileCanBeCreated(): void
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                               = new DocmanItemPOSTRepresentation();
        $post_representation->type                         = ItemRepresentation::TYPE_EMBEDDED;
        $post_representation->title                        = 'Embedded file';
        $post_representation->parent_id                    = 11;
        $post_representation->embedded_properties          = new EmbeddedPropertiesPOSTRepresentation();
        $post_representation->embedded_properties->content = 'My original content :)';

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);
        $this->event_manager->shouldReceive('processEvent');

        $created_item = \Mockery::mock(\Docman_EmbeddedFile::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->shouldReceive('getGroupId')->andReturn(102);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with('Embedded file', '', 11, 100, 222, PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE, null, null)
            ->once()
            ->andReturns($created_item);
        $this->permissions_manager->shouldReceive('clonePermissions')->once();

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(false);
        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $created_file_path = $this->docman_file_system->url() . '/Embedded/file';
        $this->file_storage->shouldReceive('store')->with(
            $post_representation->embedded_properties->content,
            $created_item->getGroupId(),
            $created_item->getId(),
            1
        )->andReturns($created_file_path);
        $created_file_size = $this->docman_file_system->getChild('Embedded/file')->size();

        $new_embedded_version_row = [
            'item_id'   => $created_item->getId(),
            'number'    => 1,
            'user_id'   => $user->getId(),
            'label'     => '',
            'changelog' => dgettext('plugin_docman', 'Initial version'),
            'date'      => $current_time->getTimestamp(),
            'filename'  => basename($created_file_path),
            'filesize'  => $created_file_size,
            'filetype'  => 'text/html',
            'path'      => $created_file_path
        ];


        $version_id = 3;
        $this->version_factory->shouldReceive('create')->with($new_embedded_version_row)->andReturn($version_id);
        $this->version_factory->shouldReceive('getCurrentVersionForItem')->with($created_item)->andReturn(
            new \Docman_Version()
        );

        $is_embedded_allowed         = true;
        $created_item_representation = $item_creator->create(
            $parent_item,
            $user,
            $project,
            $post_representation,
            $current_time,
            $is_embedded_allowed
        );

        $this->assertSame(12, $created_item_representation->id);
        $this->assertNull($created_item_representation->file_properties);
    }

    /**
     * @expectedException \Luracast\Restler\RestException
     * @expectedExceptionCode 403
     */
    public function testEmbeddedFileCannotBeCreatedIfDocmanDoesNotAllowEmbedded(): void
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                               = new DocmanItemPOSTRepresentation();
        $post_representation->type                         = ItemRepresentation::TYPE_EMBEDDED;
        $post_representation->title                        = 'Embedded file';
        $post_representation->parent_id                    = 11;
        $post_representation->embedded_properties          = new EmbeddedPropertiesPOSTRepresentation();
        $post_representation->embedded_properties->content = 'My original content failed :(';

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $this->event_manager->shouldReceive('processEvent')->never();


        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->never();
        $this->permissions_manager->shouldReceive('clonePermissions')->never();
        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(false);
        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);
        $this->file_storage->shouldReceive('store')->never();

        $is_embedded_allowed = false;
        $item_creator->create(
            $parent_item,
            $user,
            $project,
            $post_representation,
            $current_time,
            $is_embedded_allowed
        );
    }

    /**
     * @expectedException \Luracast\Restler\RestException
     * @expectedExceptionCode 400
     */
    public function testFolderCannotBeCreatedIfTheTypeGivenIsNotFolder()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation            = new DocmanItemPOSTRepresentation();
        $post_representation->type      = ItemRepresentation::TYPE_FOLDER;
        $post_representation->title     = 'Title';
        $post_representation->parent_id = 11;
        $post_representation->link_properties = json_decode(json_encode(['link_url' => 'https://my.example.test']));

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);
        $this->event_manager->shouldReceive('processEvent');

        $created_item = \Mockery::mock(\Docman_Empty::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with('Title', '', 11, 100, 222, PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, null, null)
            ->never();

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(false);

        $item_creator->create(
            $parent_item,
            $user,
            $project,
            $post_representation,
            $current_time,
            true
        );
    }

    /**
     * @expectedException \Luracast\Restler\RestException
     * @expectedExceptionCode 400
     */
    public function testItemAreRejectedIfItemWIthSameNameAlreadyExists()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation            = new DocmanItemPOSTRepresentation();
        $post_representation->type      = ItemRepresentation::TYPE_EMPTY;
        $post_representation->title     = 'Title';
        $post_representation->parent_id = 11;

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(true);

        $item_creator->create(
            $parent_item,
            $user,
            $project,
            $post_representation,
            $current_time,
            true
        );
    }

    private function directorySetup()
    {
        $this->docman_file_system = vfsStream::setup(
            'docman_root',
            777,
            [
                'Embedded' => ['file' => 'Freak no more'],
                'File'     => []
            ]
        );
    }
}
