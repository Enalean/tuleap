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

use Luracast\Restler\RestException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\REST\v1\EmbeddedFiles\DocmanEmbeddedPOSTRepresentation;
use Tuleap\Docman\REST\v1\EmbeddedFiles\EmbeddedPropertiesPOSTPATCHRepresentation;
use Tuleap\Docman\REST\v1\Files\DocmanPOSTFilesRepresentation;
use Tuleap\Docman\REST\v1\Files\EmptyFileToUploadFinisher;
use Tuleap\Docman\REST\v1\Files\FilePropertiesPOSTPATCHRepresentation;
use Tuleap\Docman\REST\v1\Folders\DocmanEmptyPOSTRepresentation;
use Tuleap\Docman\REST\v1\Folders\DocmanFolderPOSTRepresentation;
use Tuleap\Docman\REST\v1\Wiki\DocmanWikiPOSTRepresentation;
use Tuleap\Docman\REST\v1\Wiki\WikiPropertiesPOSTPATCHRepresentation;
use Tuleap\Docman\REST\v1\Links\DocmanLinkPOSTRepresentation;
use Tuleap\Docman\REST\v1\Links\DocmanLinksValidityChecker;
use Tuleap\Docman\REST\v1\Links\LinkPropertiesRepresentation;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\Docman\Upload\Document\DocumentToUpload;
use Tuleap\Docman\Upload\Document\DocumentToUploadCreator;

class DocmanItemCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $link_validity_checker;
    private $type_retriever;
    private $creator_visitor;
    private $item_factory;
    private $document_ongoing_upload_retriever;
    private $document_to_upload_creator;
    /**
     * @var vfsStreamDirectory
     */
    private $docman_file_system;

    private $empty_file_to_upload_finisher;

    public function setUp() : void
    {
        $this->creator_visitor      = \Mockery::mock(AfterItemCreationVisitor::class);

        $this->item_factory                      = \Mockery::mock(\Docman_ItemFactory::class);
        $this->document_ongoing_upload_retriever = \Mockery::mock(DocumentOngoingUploadRetriever::class);
        $this->document_to_upload_creator        = \Mockery::mock(DocumentToUploadCreator::class);

        $this->empty_file_to_upload_finisher     = \Mockery::mock(EmptyFileToUploadFinisher::class);

        $this->link_validity_checker = \Mockery::mock(DocmanLinksValidityChecker::class);
    }

    public function testEmptyDocumentCanBeCreated()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher,
            $this->link_validity_checker
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation            = new DocmanEmptyPOSTRepresentation();
        $post_representation->type      = ItemRepresentation::TYPE_EMPTY;
        $post_representation->title     = 'Title';
        $post_representation->parent_id = 11;

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);

        $created_item = \Mockery::mock(\Docman_Empty::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with('Title', '', 11, 100, 222, PLUGIN_DOCMAN_ITEM_TYPE_EMPTY, null, null)
            ->once()
            ->andReturns($created_item);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $this->creator_visitor->shouldReceive('visitEmpty')->once();

        $created_item_representation = $item_creator->createEmpty(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );

        $this->assertSame(12, $created_item_representation->id);
    }

    public function testWikiDocumentCanBeCreated()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher,
            $this->link_validity_checker
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                             = new DocmanWikiPOSTRepresentation();
        $post_representation->title                      = 'Title';
        $post_representation->wiki_properties            = new WikiPropertiesPOSTPATCHRepresentation();
        $post_representation->wiki_properties->page_name = "Monchichi";

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);
        $project->shouldReceive('usesWiki')->andReturn(true);

        $created_item = \Mockery::mock(\Docman_Wiki::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with('Title', '', 11, 100, 222, PLUGIN_DOCMAN_ITEM_TYPE_WIKI, "Monchichi", null)
            ->once()
            ->andReturns($created_item);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $this->creator_visitor->shouldReceive('visitWiki')->once();

        $created_item_representation = $item_creator->createWiki(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );

        $this->assertSame(12, $created_item_representation->id);
    }

    public function testWikiDocumentCannotBeCreatedIfServiceWikiIsNotAvailable()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher,
            $this->link_validity_checker
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                             = new DocmanWikiPOSTRepresentation();
        $post_representation->title                      = 'Title';
        $post_representation->wiki_properties            = new WikiPropertiesPOSTPATCHRepresentation();
        $post_representation->wiki_properties->page_name = "Monchichi";

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);

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

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $item_creator->createWiki(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );
    }

    public function testFileDocumentCanBeCreated()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher,
            $this->link_validity_checker
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(3);
        $user         = \Mockery::mock(\PFUser::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                            = new DocmanPOSTFilesRepresentation();
        $post_representation->title                     = 'Title';
        $post_representation->description               = '';
        $file_properties_post_representation            = new FilePropertiesPOSTPATCHRepresentation();
        $file_properties_post_representation->file_size = 123456;
        $file_properties_post_representation->file_name = 'myfile';
        $post_representation->file_properties           = $file_properties_post_representation;

        $this->document_to_upload_creator->shouldReceive('create')->once()->andReturns(new DocumentToUpload(12));

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $created_item_representation = $item_creator->createFileDocument(
            $parent_item,
            $user,
            $post_representation->title,
            $post_representation->description,
            $current_time,
            $file_properties_post_representation
        );

        $this->assertSame(12, $created_item_representation->id);
        $this->assertNotNull($created_item_representation->file_properties);
    }

    public function testItThrowsAnExceptionWhenDocumentHasSameNameThanCreatedFile()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher,
            $this->link_validity_checker
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(3);
        $user         = \Mockery::mock(\PFUser::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                            = new DocmanPOSTFilesRepresentation();
        $post_representation->title                     = 'Title';
        $post_representation->description               = '';
        $file_properties_post_representation            = new FilePropertiesPOSTPATCHRepresentation();
        $file_properties_post_representation->file_size = 123456;
        $file_properties_post_representation->file_name = 'myfile';
        $post_representation->file_properties           = $file_properties_post_representation;

        $this->document_to_upload_creator->shouldReceive('create')->never();

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(true);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $item_creator->createFileDocument(
            $parent_item,
            $user,
            $post_representation->title,
            $post_representation->description,
            $current_time,
            $file_properties_post_representation
        );
    }

    public function testDocumentCreationWhenAFileIsBeingUploadedForItIsRejected()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher,
            $this->link_validity_checker
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturn(1);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation        = new DocmanEmptyPOSTRepresentation();
        $post_representation->title = 'Title';

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(true);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(409);

        $item_creator->createEmpty(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );
    }

    public function testLinkDocumentCanBeCreated()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher,
            $this->link_validity_checker
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                            = new DocmanLinkPOSTRepresentation();
        $post_representation->title                     = 'Mie faboulouse linke';
        $post_representation->link_properties           = new LinkPropertiesRepresentation();
        $post_representation->link_properties->link_url = 'https://my.example.test';

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);

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

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $this->creator_visitor->shouldReceive('visitLink')->once();

        $this->link_validity_checker->shouldReceive("checkLinkValidity");

        $created_item_representation = $item_creator->createLink(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );

        $this->assertSame(12, $created_item_representation->id);
    }

    public function testFolderCanBeCreated(): void
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher,
            $this->link_validity_checker
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation              = new DocmanFolderPOSTRepresentation();
        $post_representation->title       = 'Title';
        $post_representation->description = '';

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);

        $created_item = \Mockery::mock(\Docman_Folder::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with('Title', '', 11, 100, 222, PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, null, null)
            ->once()
            ->andReturns($created_item);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(false);

        $this->creator_visitor->shouldReceive('visitFolder')->once();

        $created_item_representation = $item_creator->createFolder(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );

        $this->assertSame(12, $created_item_representation->id);
    }

    public function testEmbeddedFileCanBeCreated(): void
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher,
            $this->link_validity_checker
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                               = new DocmanEmbeddedPOSTRepresentation();
        $post_representation->title                        = 'Embedded file';
        $post_representation->embedded_properties          = new EmbeddedPropertiesPOSTPATCHRepresentation();
        $post_representation->embedded_properties->content = 'My original content :)';

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);

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

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(false);
        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $this->creator_visitor->shouldReceive('visitEmbeddedFile')->once();

        $created_item_representation = $item_creator->createEmbedded(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );

        $this->assertSame(12, $created_item_representation->id);
    }

    public function testItemAreRejectedIfItemWIthSameNameAlreadyExists()
    {
        $item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher,
            $this->link_validity_checker
        );

        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturn(1);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation            = new DocmanEmptyPOSTRepresentation();
        $post_representation->title     = 'Title';

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(true);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $item_creator->createEmpty(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );
    }
}
