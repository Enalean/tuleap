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

    public function setUp()
    {
        $this->permissions_manager  = \Mockery::mock(\PermissionsManager::class);
        $this->event_manager        = \Mockery::mock(\EventManager::class);
        $this->link_version_factory = \Mockery::mock(\Docman_LinkVersionFactory::class);
        $this->creator_visitor      = new AfterItemCreationVisitor(
            $this->permissions_manager,
            $this->event_manager,
            $this->link_version_factory
        );

        $this->item_factory                      = \Mockery::mock(\Docman_ItemFactory::class);
        $this->document_ongoing_upload_retriever = \Mockery::mock(DocumentOngoingUploadRetriever::class);
        $this->document_to_upload_creator        = \Mockery::mock(DocumentToUploadCreator::class);
    }

    public function testEmptyDocumentCanBeCreated()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor
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
            $current_time
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
            $this->creator_visitor
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
            $current_time
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
            $this->creator_visitor
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
            $current_time
        );
    }

    public function testFileDocumentCanBeCreated()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor
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
            $current_time
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
            $this->creator_visitor
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
            $current_time
        );
    }

    public function testLinkDocumentCanBeCreated()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor
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
            ->with($created_item, 'Initial version', 'Initial version', time())
            ->once()
            ->andReturn(true);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $created_item_representation = $item_creator->create(
            $parent_item,
            $user,
            $project,
            $post_representation,
            $current_time
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
            $this->creator_visitor
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

        $created_item = \Mockery::mock(\Docman_Empty::class);
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
            $current_time
        );

        $this->assertSame(12, $created_item_representation->id);
        $this->assertNull($created_item_representation->file_properties);
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
            $this->creator_visitor
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
            $current_time
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
            $this->creator_visitor
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
            $current_time
        );
    }
}
