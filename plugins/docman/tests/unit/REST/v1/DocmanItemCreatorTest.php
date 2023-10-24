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

namespace Tuleap\Docman\REST\v1;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_Folder;
use Docman_Link;
use Docman_LinkVersion;
use Docman_MetadataValueDao;
use Docman_Wiki;
use Luracast\Restler\RestException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Docman\REST\v1\EmbeddedFiles\DocmanEmbeddedPOSTRepresentation;
use Tuleap\Docman\REST\v1\EmbeddedFiles\EmbeddedPropertiesPOSTPATCHRepresentation;
use Tuleap\Docman\REST\v1\Empties\DocmanEmptyPOSTRepresentation;
use Tuleap\Docman\REST\v1\Files\DocmanPOSTFilesRepresentation;
use Tuleap\Docman\REST\v1\Files\EmptyFileToUploadFinisher;
use Tuleap\Docman\REST\v1\Files\FilePropertiesPOSTPATCHRepresentation;
use Tuleap\Docman\REST\v1\Folders\DocmanFolderPOSTRepresentation;
use Tuleap\Docman\REST\v1\Links\DocmanLinkPOSTRepresentation;
use Tuleap\Docman\REST\v1\Links\DocmanLinksValidityChecker;
use Tuleap\Docman\REST\v1\Links\LinkPropertiesRepresentation;
use Tuleap\Docman\REST\v1\Metadata\CustomMetadataRepresentationRetriever;
use Tuleap\Docman\REST\v1\Metadata\HardCodedMetadataException;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataObsolescenceDateRetriever;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\REST\v1\Metadata\MetadataToCreate;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSet;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSetFactory;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSetRepresentation;
use Tuleap\Docman\REST\v1\Wiki\DocmanWikiPOSTRepresentation;
use Tuleap\Docman\REST\v1\Wiki\WikiPropertiesPOSTPATCHRepresentation;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\Docman\Upload\Document\DocumentToUpload;
use Tuleap\Docman\Upload\Document\DocumentToUploadCreator;

final class DocmanItemCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Docman_MetadataValueDao|\Mockery\MockInterface
     */
    private $metadata_value_dao;

    /**
     * @var DocmanItemCreator
     */
    private $item_creator;
    /**
     * @var \Mockery\MockInterface|CustomMetadataRepresentationRetriever
     */
    private $custom_metadata_checker;
    /**
     * @var \Mockery\MockInterface|DocmanLinksValidityChecker
     */
    private $link_validity_checker;
    /**
     * @var \Mockery\MockInterface|AfterItemCreationVisitor
     */
    private $creator_visitor;
    /**
     * @var \Docman_ItemFactory|\Mockery\MockInterface
     */
    private $item_factory;
    /**
     * @var \Mockery\MockInterface|DocumentOngoingUploadRetriever
     */
    private $document_ongoing_upload_retriever;
    /**
     * @var \Mockery\MockInterface|DocumentToUploadCreator
     */
    private $document_to_upload_creator;
    /**
     * @var \Mockery\MockInterface|EmptyFileToUploadFinisher
     */
    private $empty_file_to_upload_finisher;
    /**
     * @var \Mockery\MockInterface|ItemStatusMapper
     */
    private $item_status_mapper;
    /**
     * @var \Mockery\MockInterface|HardcodedMetadataObsolescenceDateRetriever
     */
    private $metadata_obsolesence_date_retriever;
    /**
     * @var \Mockery\MockInterface|DocmanItemPermissionsForGroupsSetFactory
     */
    private $permissions_for_groups_set_factory;

    protected function setUp(): void
    {
        $this->creator_visitor = \Mockery::mock(AfterItemCreationVisitor::class);

        $this->item_factory                      = \Mockery::mock(\Docman_ItemFactory::class);
        $this->document_ongoing_upload_retriever = \Mockery::mock(DocumentOngoingUploadRetriever::class);
        $this->document_to_upload_creator        = \Mockery::mock(DocumentToUploadCreator::class);

        $this->empty_file_to_upload_finisher = \Mockery::mock(EmptyFileToUploadFinisher::class);

        $this->link_validity_checker = \Mockery::mock(DocmanLinksValidityChecker::class);

        $this->item_status_mapper = \Mockery::mock(ItemStatusMapper::class);

        $this->metadata_obsolesence_date_retriever = \Mockery::mock(HardcodedMetadataObsolescenceDateRetriever::class);

        $this->custom_metadata_checker = \Mockery::mock(CustomMetadataRepresentationRetriever::class);

        $this->metadata_value_dao = \Mockery::mock(Docman_MetadataValueDao::class);

        $this->permissions_for_groups_set_factory = \Mockery::mock(DocmanItemPermissionsForGroupsSetFactory::class);

        $this->item_creator = new DocmanItemCreator(
            $this->item_factory,
            $this->document_ongoing_upload_retriever,
            $this->document_to_upload_creator,
            $this->creator_visitor,
            $this->empty_file_to_upload_finisher,
            $this->link_validity_checker,
            $this->item_status_mapper,
            $this->metadata_obsolesence_date_retriever,
            $this->custom_metadata_checker,
            $this->metadata_value_dao,
            $this->permissions_for_groups_set_factory,
        );
    }

    /**
     * @dataProvider permissionsForGroupsSetRepresentationDataProvider
     */
    public function testEmptyDocumentCanBeCreated(?DocmanItemPermissionsForGroupsSetRepresentation $permissions_for_groups_set): void
    {
        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                         = new DocmanEmptyPOSTRepresentation();
        $post_representation->title                  = 'Title';
        $post_representation->permissions_for_groups = $permissions_for_groups_set;

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);

        $this->item_status_mapper->shouldReceive('getItemStatusWithParentInheritance')
            ->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);

        $this->metadata_obsolesence_date_retriever
            ->shouldReceive('getTimeStampOfDateWithoutPeriodValidity')
            ->withArgs([ItemRepresentation::OBSOLESCENCE_DATE_NONE, $current_time])
            ->andReturn((int) ItemRepresentation::OBSOLESCENCE_DATE_NONE);

        $created_item = \Mockery::mock(Docman_Empty::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with('Title', '', 11, 100, 0, 222, PLUGIN_DOCMAN_ITEM_TYPE_EMPTY, \Mockery::any(), \Mockery::any(), null, null)
            ->once()
            ->andReturns($created_item);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        if ($permissions_for_groups_set !== null) {
            $this->permissions_for_groups_set_factory->shouldReceive('fromRepresentation')
                ->once()
                ->andReturn(new DocmanItemPermissionsForGroupsSet([]));
        }

        $this->creator_visitor->shouldReceive('visitEmpty')
            ->withArgs(function (Docman_Empty $item, array $params) use ($permissions_for_groups_set): bool {
                if ($permissions_for_groups_set === null) {
                    $this->assertNull($params['permissions_for_groups']);
                } else {
                    $this->assertNotNull($params['permissions_for_groups']);
                }
                return true;
            })->once();

        $this->custom_metadata_checker->shouldReceive('checkAndRetrieveFormattedRepresentation')->andReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this->item_creator->createEmpty(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );

        $this->assertSame(12, $created_item_representation->id);
    }

    /**
     * @dataProvider permissionsForGroupsSetRepresentationDataProvider
     */
    public function testWikiDocumentCanBeCreated(?DocmanItemPermissionsForGroupsSetRepresentation $permissions_for_groups_set): void
    {
        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                             = new DocmanWikiPOSTRepresentation();
        $post_representation->title                      = 'Title';
        $post_representation->wiki_properties            = new WikiPropertiesPOSTPATCHRepresentation();
        $post_representation->wiki_properties->page_name = "Monchichi";
        $post_representation->permissions_for_groups     = $permissions_for_groups_set;

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);
        $project->shouldReceive('usesWiki')->andReturn(true);

        $this->item_status_mapper->shouldReceive('getItemStatusWithParentInheritance')->andReturn(
            PLUGIN_DOCMAN_ITEM_STATUS_NONE
        );

        $this->metadata_obsolesence_date_retriever->shouldReceive('getTimeStampOfDateWithoutPeriodValidity')->andReturn(
            (int) ItemRepresentation::OBSOLESCENCE_DATE_NONE
        );

        $created_item = \Mockery::mock(Docman_Wiki::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        if ($permissions_for_groups_set !== null) {
            $this->permissions_for_groups_set_factory->shouldReceive('fromRepresentation')
                ->once()
                ->andReturn(new DocmanItemPermissionsForGroupsSet([]));
        }

        $this->creator_visitor->shouldReceive('visitWiki')
            ->withArgs(function (Docman_Wiki $item, array $params) use ($permissions_for_groups_set): bool {
                if ($permissions_for_groups_set === null) {
                    $this->assertNull($params['permissions_for_groups']);
                } else {
                    $this->assertNotNull($params['permissions_for_groups']);
                }
                return true;
            })->once();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with('Title', '', 11, 100, (int) ItemRepresentation::OBSOLESCENCE_DATE_NONE, 222, PLUGIN_DOCMAN_ITEM_TYPE_WIKI, \Mockery::any(), \Mockery::any(), "Monchichi", null)
            ->once()
            ->andReturns($created_item);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $this->custom_metadata_checker->shouldReceive('checkAndRetrieveFormattedRepresentation')->andReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this->item_creator->createWiki(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );

        $this->assertSame(12, $created_item_representation->id);
    }

    public function testWikiDocumentCannotBeCreatedIfServiceWikiIsNotAvailable(): void
    {
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

        $created_item = \Mockery::mock(Docman_Empty::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->never();

        $project->shouldReceive('usesWiki')->andReturn(false);
        $project->shouldReceive('getUnixName')->once();

        $this->custom_metadata_checker->shouldReceive('checkAndRetrieveFormattedRepresentation')->andReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->item_creator->createWiki(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );
    }

    public function testFileDocumentCanBeCreated(): void
    {
        $parent_item = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(3);
        $parent_item->shouldReceive('getGroupId')->andReturns(104);
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

        $this->item_status_mapper->shouldReceive('getItemStatusWithParentInheritance')->andReturn(
            PLUGIN_DOCMAN_ITEM_STATUS_NONE
        );

        $this->metadata_obsolesence_date_retriever->shouldReceive('getTimeStampOfDateWithoutPeriodValidity')->andReturn(
            (int) ItemRepresentation::OBSOLESCENCE_DATE_NONE
        );

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $metadata_to_create = MetadataToCreate::buildMetadataRepresentation([], false);

        $created_item_representation = $this->item_creator->createFileDocument(
            $parent_item,
            $user,
            $post_representation->title,
            $post_representation->description,
            'approved',
            '2019-06-06',
            $current_time,
            $file_properties_post_representation,
            $metadata_to_create,
            null
        );

        $this->assertSame(12, $created_item_representation->id);
        $this->assertNotNull($created_item_representation->file_properties);
    }

    public function testItThrowsAnExceptionWhenDocumentHasSameNameThanCreatedFile(): void
    {
        $parent_item = \Mockery::mock(\Docman_Item::class);
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

        $this->custom_metadata_checker->shouldReceive('checkAndRetrieveFormattedRepresentation')->never();

        $metadata_to_create = MetadataToCreate::buildMetadataRepresentation([], false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->item_creator->createFileDocument(
            $parent_item,
            $user,
            $post_representation->title,
            $post_representation->description,
            'approved',
            '2019-06-06',
            $current_time,
            $file_properties_post_representation,
            $metadata_to_create,
            null
        );
    }

    public function testDocumentCreationWhenAFileIsBeingUploadedForItIsRejected(): void
    {
        $parent_item = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturn(1);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation        = new DocmanEmptyPOSTRepresentation();
        $post_representation->title = 'Title';

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(true);

        $this->custom_metadata_checker->shouldReceive('checkAndRetrieveFormattedRepresentation')->andReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(409);

        $this->item_creator->createEmpty(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );
    }

    /**
     * @dataProvider permissionsForGroupsSetRepresentationDataProvider
     */
    public function testLinkDocumentCanBeCreated(?DocmanItemPermissionsForGroupsSetRepresentation $permissions_for_groups_set): void
    {
        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation        = new DocmanLinkPOSTRepresentation();
        $post_representation->title = 'Mie faboulouse linke';
        $docman_link                = \Mockery::mock(Docman_LinkVersion::class);
        $docman_link->shouldReceive('getLink')->andReturn('https://my.example.test');
        $post_representation->link_properties        = LinkPropertiesRepresentation::build($docman_link);
        $post_representation->permissions_for_groups = $permissions_for_groups_set;

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);

        $this->item_status_mapper->shouldReceive('getItemStatusWithParentInheritance')
            ->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);

        $this->metadata_obsolesence_date_retriever->shouldReceive('getTimeStampOfDateWithoutPeriodValidity')->andReturn(
            (int) ItemRepresentation::OBSOLESCENCE_DATE_NONE
        );

        if ($permissions_for_groups_set !== null) {
            $this->permissions_for_groups_set_factory->shouldReceive('fromRepresentation')
                ->once()
                ->andReturn(new DocmanItemPermissionsForGroupsSet([]));
        }

        $this->creator_visitor->shouldReceive('visitLink')
            ->withArgs(function (Docman_Link $item, array $params) use ($permissions_for_groups_set): bool {
                if ($permissions_for_groups_set === null) {
                    $this->assertNull($params['permissions_for_groups']);
                } else {
                    $this->assertNotNull($params['permissions_for_groups']);
                }
                return true;
            })->once();

        $created_item = \Mockery::mock(Docman_Link::class);
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
                0,
                222,
                PLUGIN_DOCMAN_ITEM_TYPE_LINK,
                \Mockery::any(),
                \Mockery::any(),
                null,
                'https://my.example.test'
            )
            ->once()
            ->andReturns($created_item);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $this->link_validity_checker->shouldReceive("checkLinkValidity");

        $this->custom_metadata_checker->shouldReceive('checkAndRetrieveFormattedRepresentation')->andReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this->item_creator->createLink(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );

        $this->assertSame(12, $created_item_representation->id);
    }

    /**
     * @dataProvider permissionsForGroupsSetRepresentationDataProvider
     */
    public function testFolderCanBeCreated(?DocmanItemPermissionsForGroupsSetRepresentation $permissions_for_groups_set): void
    {
        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                         = new DocmanFolderPOSTRepresentation();
        $post_representation->title                  = 'Title';
        $post_representation->description            = '';
        $post_representation->permissions_for_groups = $permissions_for_groups_set;

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);

        $this->item_status_mapper->shouldReceive('getItemStatusWithParentInheritance')
            ->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);

        $this->metadata_obsolesence_date_retriever->shouldReceive('getTimeStampOfDateWithoutPeriodValidity')->andReturn(
            (int) ItemRepresentation::OBSOLESCENCE_DATE_NONE
        );

        if ($permissions_for_groups_set !== null) {
            $this->permissions_for_groups_set_factory->shouldReceive('fromRepresentation')
                ->once()
                ->andReturn(new DocmanItemPermissionsForGroupsSet([]));
        }

        $this->creator_visitor->shouldReceive('visitFolder')
            ->withArgs(function (Docman_Folder $item, array $params) use ($permissions_for_groups_set): bool {
                if ($permissions_for_groups_set === null) {
                    $this->assertNull($params['permissions_for_groups']);
                } else {
                    $this->assertNotNull($params['permissions_for_groups']);
                }
                return true;
            })->once();

        $created_item = \Mockery::mock(Docman_Folder::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with('Title', '', 11, 100, 0, 222, PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, \Mockery::any(), \Mockery::any(), null, null)
            ->once()
            ->andReturns($created_item);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(false);

        $this->custom_metadata_checker->shouldReceive('checkAndRetrieveFormattedRepresentation')->andReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this->item_creator->createFolder(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );

        $this->assertSame(12, $created_item_representation->id);
    }

    /**
     * @dataProvider permissionsForGroupsSetRepresentationDataProvider
     */
    public function testEmbeddedFileCanBeCreated(?DocmanItemPermissionsForGroupsSetRepresentation $permissions_for_groups_set): void
    {
        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                               = new DocmanEmbeddedPOSTRepresentation();
        $post_representation->title                        = 'Embedded file';
        $post_representation->embedded_properties          = new EmbeddedPropertiesPOSTPATCHRepresentation();
        $post_representation->embedded_properties->content = 'My original content :)';
        $post_representation->permissions_for_groups       = $permissions_for_groups_set;

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);

        $this->item_status_mapper->shouldReceive('getItemStatusWithParentInheritance')->andReturn(
            PLUGIN_DOCMAN_ITEM_STATUS_NONE
        );

        $this->metadata_obsolesence_date_retriever->shouldReceive('getTimeStampOfDateWithoutPeriodValidity')->andReturn(
            (int) ItemRepresentation::OBSOLESCENCE_DATE_NONE
        );

        if ($permissions_for_groups_set !== null) {
            $this->permissions_for_groups_set_factory->shouldReceive('fromRepresentation')
                ->once()
                ->andReturn(new DocmanItemPermissionsForGroupsSet([]));
        }

        $this->creator_visitor->shouldReceive('visitEmbeddedFile')
            ->withArgs(function (Docman_EmbeddedFile $item, array $params) use ($permissions_for_groups_set): bool {
                if ($permissions_for_groups_set === null) {
                    $this->assertNull($params['permissions_for_groups']);
                } else {
                    $this->assertNotNull($params['permissions_for_groups']);
                }
                return true;
            })->once();

        $created_item = \Mockery::mock(Docman_EmbeddedFile::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->shouldReceive('getGroupId')->andReturn(102);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with('Embedded file', '', 11, 100, 0, 222, PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE, \Mockery::any(), \Mockery::any(), null, null)
            ->once()
            ->andReturns($created_item);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(false);
        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $this->custom_metadata_checker->shouldReceive('checkAndRetrieveFormattedRepresentation')->andReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this->item_creator->createEmbedded(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );

        $this->assertSame(12, $created_item_representation->id);
    }

    public function testItemAreRejectedIfItemWIthSameNameAlreadyExists(): void
    {
        $parent_item = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturn(1);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation        = new DocmanEmptyPOSTRepresentation();
        $post_representation->title = 'Title';

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(true);

        $this->custom_metadata_checker->shouldReceive('checkAndRetrieveFormattedRepresentation')->never();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->item_creator->createEmpty(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );
    }

    public function testItThrowsExceptionIfTheStatusMetadataIsNotUsedButSetInTheRepresentation(): void
    {
        $parent_item = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturn(1);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation              = new DocmanFolderPOSTRepresentation();
        $post_representation->title       = 'Title';
        $post_representation->description = '';
        $post_representation->status      = 'approved';

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);

        $this->item_status_mapper->shouldReceive('getItemStatusWithParentInheritance')->andThrow(
            HardCodedMetadataException::itemStatusNotAvailable()
        );

        $this->metadata_obsolesence_date_retriever->shouldReceive('getTimeStampOfDateWithoutPeriodValidity')->never();

        $created_item = \Mockery::mock(Docman_Folder::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->never();

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(false);

        $this->creator_visitor->shouldReceive('visitFolder')->never();
        $this->custom_metadata_checker->shouldReceive('checkAndRetrieveFormattedRepresentation')->andReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $this->expectException(HardCodedMetadataException::class);
        $this->expectExceptionMessage('Status is not enabled for project');

        $this->item_creator->createFolder(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );
    }

    public function testFolderCanBeCreatedWithStatus(): void
    {
        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation              = new DocmanFolderPOSTRepresentation();
        $post_representation->title       = 'Title';
        $post_representation->description = '';
        $post_representation->status      = 'approved';

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);

        $this->item_status_mapper->shouldReceive('getItemStatusWithParentInheritance')->with(
            $parent_item,
            $post_representation->status
        )->andReturn(
            PLUGIN_DOCMAN_ITEM_STATUS_APPROVED
        );

        $this->metadata_obsolesence_date_retriever->shouldReceive('getTimeStampOfDateWithoutPeriodValidity')->andReturn(
            (int) ItemRepresentation::OBSOLESCENCE_DATE_NONE
        );

        $created_item = \Mockery::mock(Docman_Folder::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with(
                'Title',
                '',
                11,
                PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
                0,
                222,
                PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
                \Mockery::any(),
                \Mockery::any(),
                null,
                null
            )
            ->once()
            ->andReturns($created_item);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(false);

        $this->creator_visitor->shouldReceive('visitFolder')->once();
        $this->custom_metadata_checker->shouldReceive('checkAndRetrieveFormattedRepresentation')->andReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this->item_creator->createFolder(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );

        $this->assertSame(12, $created_item_representation->id);
    }

    public function testCreateEmptyDocumentWithStatusAndObsolescenceDate(): void
    {
        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                    = new DocmanEmptyPOSTRepresentation();
        $post_representation->title             = 'Title';
        $post_representation->status            = 'approved';
        $post_representation->obsolescence_date = '2019-10-11';

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);

        $this->item_status_mapper->shouldReceive('getItemStatusWithParentInheritance')
            ->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);

        $obsolescence_date_time_stamp = 123456;
        $this->metadata_obsolesence_date_retriever->shouldReceive('getTimeStampOfDateWithoutPeriodValidity')
            ->withArgs(
                [
                    $post_representation->obsolescence_date,
                    $current_time,
                ]
            )
            ->andReturn($obsolescence_date_time_stamp);

        $created_item = \Mockery::mock(Docman_Empty::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with('Title', '', 11, 100, $obsolescence_date_time_stamp, 222, PLUGIN_DOCMAN_ITEM_TYPE_EMPTY, \Mockery::any(), \Mockery::any(), null, null)
            ->once()
            ->andReturns($created_item);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $this->creator_visitor->shouldReceive('visitEmpty')->once();
        $this->custom_metadata_checker->shouldReceive('checkAndRetrieveFormattedRepresentation')->andReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this->item_creator->createEmpty(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );

        $this->assertSame(12, $created_item_representation->id);
    }

    public function testCreateEmbeddedDocumentWithStatusAndObsolescenceDate(): void
    {
        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                               = new DocmanEmbeddedPOSTRepresentation();
        $post_representation->title                        = 'Embedded file with status and Obsolescence date';
        $post_representation->embedded_properties          = new EmbeddedPropertiesPOSTPATCHRepresentation();
        $post_representation->embedded_properties->content = 'My original content :)... Not So original :(';
        $post_representation->status                       = 'approved';
        $post_representation->obsolescence_date            = '2019-10-11';

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);

        $this->item_status_mapper->shouldReceive('getItemStatusWithParentInheritance')
            ->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);

        $obsolescence_date_time_stamp = 123456;
        $this->metadata_obsolesence_date_retriever->shouldReceive('getTimeStampOfDateWithoutPeriodValidity')
            ->withArgs(
                [
                    $post_representation->obsolescence_date,
                    $current_time,
                ]
            )
            ->andReturn($obsolescence_date_time_stamp);

        $created_item = \Mockery::mock(Docman_Empty::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with(
                'Embedded file with status and Obsolescence date',
                '',
                11,
                PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
                $obsolescence_date_time_stamp,
                222,
                PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE,
                \Mockery::any(),
                \Mockery::any(),
                null,
                null
            )
            ->once()
            ->andReturns($created_item);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $this->creator_visitor->shouldReceive('visitEmpty')->once();
        $this->custom_metadata_checker->shouldReceive('checkAndRetrieveFormattedRepresentation')->andReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this->item_creator->createEmbedded(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );

        $this->assertSame(12, $created_item_representation->id);
    }

    public function testCreateLinkDocumentWithStatusAndObsolescenceDate(): void
    {
        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation        = new DocmanLinkPOSTRepresentation();
        $post_representation->title = 'Link with status and Obsolescence date';
        $docman_link                = \Mockery::mock(Docman_LinkVersion::class);
        $docman_link->shouldReceive('getLink')->andReturn('https://my.example.test');
        $post_representation->link_properties   = LinkPropertiesRepresentation::build($docman_link);
        $post_representation->status            = 'approved';
        $post_representation->obsolescence_date = '2019-10-11';

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);

        $this->item_status_mapper->shouldReceive('getItemStatusWithParentInheritance')
            ->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_REJECTED);

        $obsolescence_date_time_stamp = 123456;
        $this->metadata_obsolesence_date_retriever->shouldReceive('getTimeStampOfDateWithoutPeriodValidity')
            ->withArgs(
                [
                    $post_representation->obsolescence_date,
                    $current_time,
                ]
            )
            ->andReturn($obsolescence_date_time_stamp);

        $created_item = \Mockery::mock(Docman_Link::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with(
                'Link with status and Obsolescence date',
                '',
                11,
                PLUGIN_DOCMAN_ITEM_STATUS_REJECTED,
                $obsolescence_date_time_stamp,
                222,
                PLUGIN_DOCMAN_ITEM_TYPE_LINK,
                \Mockery::any(),
                \Mockery::any(),
                null,
                'https://my.example.test'
            )
            ->once()
            ->andReturns($created_item);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $this->creator_visitor->shouldReceive('visitLink')->once();
        $this->custom_metadata_checker->shouldReceive('checkAndRetrieveFormattedRepresentation')->andReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $this->link_validity_checker->shouldReceive("checkLinkValidity")->once();

        $created_item_representation = $this->item_creator->createLink(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );

        $this->assertSame(12, $created_item_representation->id);
    }

    public function testWikiDocumentCanBeCreatedWithStatusAndObsolescenceDate(): void
    {
        $parent_item  = \Mockery::mock(\Docman_Item::class);
        $user         = \Mockery::mock(\PFUser::class);
        $project      = \Mockery::mock(\Project::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                             = new DocmanWikiPOSTRepresentation();
        $post_representation->title                      = 'wiki';
        $post_representation->wiki_properties            = new WikiPropertiesPOSTPATCHRepresentation();
        $post_representation->wiki_properties->page_name = "Monchocho";
        $post_representation->status                     = 'rejected';
        $post_representation->obsolescence_date          = '2019-10-11';

        $this->document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturns(false);
        $parent_item->shouldReceive('getId')->andReturns(11);
        $user->shouldReceive('getId')->andReturns(222);
        $project->shouldReceive('getID')->andReturns(102);
        $project->shouldReceive('usesWiki')->andReturn(true);

        $this->item_status_mapper->shouldReceive('getItemStatusWithParentInheritance')->andReturn(
            PLUGIN_DOCMAN_ITEM_STATUS_REJECTED
        );

        $obsolescence_date_time_stamp = 123456;
        $this->metadata_obsolesence_date_retriever->shouldReceive('getTimeStampOfDateWithoutPeriodValidity')
            ->withArgs(
                [
                    $post_representation->obsolescence_date,
                    $current_time,
                ]
            )
            ->andReturn($obsolescence_date_time_stamp);

        $created_item = \Mockery::mock(Docman_Wiki::class);
        $created_item->shouldReceive('getId')->andReturns(12);
        $created_item->shouldReceive('getParentId')->andReturns(11);
        $created_item->makePartial();

        $this->item_factory
            ->shouldReceive('createWithoutOrdering')
            ->with(
                'wiki',
                '',
                11,
                PLUGIN_DOCMAN_ITEM_STATUS_REJECTED,
                $obsolescence_date_time_stamp,
                222,
                PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
                \Mockery::any(),
                \Mockery::any(),
                "Monchocho",
                null
            )
            ->once()
            ->andReturns($created_item);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);

        $this->creator_visitor->shouldReceive('visitWiki')->once();
        $this->custom_metadata_checker->shouldReceive('checkAndRetrieveFormattedRepresentation')->andReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this->item_creator->createWiki(
            $parent_item,
            $user,
            $post_representation,
            $current_time,
            $project
        );

        $this->assertSame(12, $created_item_representation->id);
    }

    public function testFileDocumentCanBeCreatedWithStatusAndObsolescenceDate(): void
    {
        $parent_item = \Mockery::mock(\Docman_Item::class);
        $parent_item->shouldReceive('getId')->andReturns(3);
        $parent_item->shouldReceive('getGroupId')->andReturns(101);
        $user         = \Mockery::mock(\PFUser::class);
        $current_time = new \DateTimeImmutable();

        $post_representation                            = new DocmanPOSTFilesRepresentation();
        $post_representation->title                     = 'Title';
        $post_representation->description               = '';
        $post_representation->status                    = 'approved';
        $post_representation->obsolescence_date         = '2019-03-08';
        $file_properties_post_representation            = new FilePropertiesPOSTPATCHRepresentation();
        $file_properties_post_representation->file_size = 123456;
        $file_properties_post_representation->file_name = 'myfile';
        $post_representation->file_properties           = $file_properties_post_representation;

        $this->document_to_upload_creator->shouldReceive('create')->once()->andReturns(new DocumentToUpload(12));

        $this->item_status_mapper->shouldReceive('getItemStatusWithParentInheritance')->andReturn(
            PLUGIN_DOCMAN_ITEM_STATUS_NONE
        );

        $obsolescence_date_time_stamp = 123456;
        $this->metadata_obsolesence_date_retriever->shouldReceive('getTimeStampOfDateWithoutPeriodValidity')
            ->withArgs(
                [
                    $post_representation->obsolescence_date,
                    $current_time,
                ]
            )
            ->andReturn($obsolescence_date_time_stamp);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);
        $this->custom_metadata_checker->shouldReceive('checkAndRetrieveFormattedRepresentation')->never();

        $metadata_to_create          = MetadataToCreate::buildMetadataRepresentation([], false);
        $created_item_representation = $this->item_creator->createFileDocument(
            $parent_item,
            $user,
            $post_representation->title,
            $post_representation->description,
            $post_representation->status,
            $post_representation->obsolescence_date,
            $current_time,
            $file_properties_post_representation,
            $metadata_to_create,
            null
        );

        $this->assertSame(12, $created_item_representation->id);
        $this->assertNotNull($created_item_representation->file_properties);
    }

    public static function permissionsForGroupsSetRepresentationDataProvider(): array
    {
        return [
            [null],
            [new DocmanItemPermissionsForGroupsSetRepresentation()],
        ];
    }
}
