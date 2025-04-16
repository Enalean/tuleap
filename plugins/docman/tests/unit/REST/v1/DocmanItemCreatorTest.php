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

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1;

use DateTimeImmutable;
use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_Folder;
use Docman_Item;
use Docman_ItemFactory;
use Docman_Link;
use Docman_LinkVersion;
use Docman_MetadataValueDao;
use Docman_Wiki;
use Luracast\Restler\RestException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\EventDispatcher\EventDispatcherInterface;
use Service;
use Tuleap\Docman\Item\OtherDocument;
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
use Tuleap\Docman\REST\v1\Others\DocmanOtherTypePOSTRepresentation;
use Tuleap\Docman\REST\v1\Others\VerifyOtherTypeIsSupported;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSet;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSetFactory;
use Tuleap\Docman\REST\v1\Permissions\DocmanItemPermissionsForGroupsSetRepresentation;
use Tuleap\Docman\REST\v1\Wiki\DocmanWikiPOSTRepresentation;
use Tuleap\Docman\REST\v1\Wiki\WikiPropertiesPOSTPATCHRepresentation;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\Docman\Upload\Document\DocumentToUpload;
use Tuleap\Docman\Upload\Document\DocumentToUploadCreator;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanItemCreatorTest extends TestCase
{
    private Docman_MetadataValueDao&MockObject $metadata_value_dao;
    private CustomMetadataRepresentationRetriever&MockObject $custom_metadata_checker;
    private DocmanLinksValidityChecker&MockObject $link_validity_checker;
    private AfterItemCreationVisitor&MockObject $creator_visitor;
    private Docman_ItemFactory&MockObject $item_factory;
    private DocumentOngoingUploadRetriever&MockObject $document_ongoing_upload_retriever;
    private DocumentToUploadCreator&MockObject $document_to_upload_creator;
    private EmptyFileToUploadFinisher&MockObject $empty_file_to_upload_finisher;
    private ItemStatusMapper&MockObject $item_status_mapper;
    private HardcodedMetadataObsolescenceDateRetriever&MockObject $metadata_obsolesence_date_retriever;
    private DocmanItemPermissionsForGroupsSetFactory&MockObject $permissions_for_groups_set_factory;

    protected function setUp(): void
    {
        $this->creator_visitor                     = $this->createMock(AfterItemCreationVisitor::class);
        $this->item_factory                        = $this->createMock(Docman_ItemFactory::class);
        $this->document_ongoing_upload_retriever   = $this->createMock(DocumentOngoingUploadRetriever::class);
        $this->document_to_upload_creator          = $this->createMock(DocumentToUploadCreator::class);
        $this->empty_file_to_upload_finisher       = $this->createMock(EmptyFileToUploadFinisher::class);
        $this->link_validity_checker               = $this->createMock(DocmanLinksValidityChecker::class);
        $this->item_status_mapper                  = $this->createMock(ItemStatusMapper::class);
        $this->metadata_obsolesence_date_retriever = $this->createMock(HardcodedMetadataObsolescenceDateRetriever::class);
        $this->custom_metadata_checker             = $this->createMock(CustomMetadataRepresentationRetriever::class);
        $this->metadata_value_dao                  = $this->createMock(Docman_MetadataValueDao::class);
        $this->permissions_for_groups_set_factory  = $this->createMock(DocmanItemPermissionsForGroupsSetFactory::class);
    }

    private function getItemCreator(EventDispatcherInterface $dispatcher): DocmanItemCreator
    {
        return new DocmanItemCreator(
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
            $dispatcher,
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('permissionsForGroupsSetRepresentationDataProvider')]
    public function testEmptyDocumentCanBeCreated(?DocmanItemPermissionsForGroupsSetRepresentation $permissions_for_groups_set): void
    {
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(222);
        $project      = ProjectTestBuilder::aProject()->withId(102)->build();
        $current_time = new DateTimeImmutable();

        $post_representation                         = new DocmanEmptyPOSTRepresentation();
        $post_representation->title                  = 'Title';
        $post_representation->permissions_for_groups = $permissions_for_groups_set;

        $this->document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->item_status_mapper->method('getItemStatusWithParentInheritance')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);

        $this->metadata_obsolesence_date_retriever
            ->method('getTimeStampOfDateWithoutPeriodValidity')
            ->with(ItemRepresentation::OBSOLESCENCE_DATE_NONE, $current_time)
            ->willReturn((int) ItemRepresentation::OBSOLESCENCE_DATE_NONE);

        $created_item = new Docman_Empty(['item_id' => 12, 'parent_id' => 11]);

        $this->item_factory->expects($this->once())->method('createWithoutOrdering')
            ->with('Title', '', 11, 100, 0, 222, PLUGIN_DOCMAN_ITEM_TYPE_EMPTY, '', self::anything(), self::anything(), null, null)
            ->willReturn($created_item);

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);

        if ($permissions_for_groups_set !== null) {
            $this->permissions_for_groups_set_factory->expects($this->once())->method('fromRepresentation')
                ->willReturn(new DocmanItemPermissionsForGroupsSet([]));
        }

        $this->creator_visitor->expects($this->once())->method('visitEmpty')
            ->with(self::isInstanceOf(Docman_Empty::class), self::callback(function (array $params) use ($permissions_for_groups_set): bool {
                if ($permissions_for_groups_set === null) {
                    self::assertNull($params['permissions_for_groups']);
                } else {
                    self::assertNotNull($params['permissions_for_groups']);
                }
                return true;
            }));

        $this->custom_metadata_checker->method('checkAndRetrieveFormattedRepresentation')->willReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this
            ->getItemCreator(EventDispatcherStub::withIdentityCallback())
            ->createEmpty(
                $parent_item,
                $user,
                $post_representation,
                $current_time,
                $project
            );

        self::assertSame(12, $created_item_representation->id);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('permissionsForGroupsSetRepresentationDataProvider')]
    public function testOtherTypeDocumentCanBeCreated(?DocmanItemPermissionsForGroupsSetRepresentation $permissions_for_groups_set): void
    {
        $parent_item  = new Docman_Folder(['item_id' => 11]);
        $user         = UserTestBuilder::aUser()->withId(222)->build();
        $project      = ProjectTestBuilder::aProject()->withId(102)->build();
        $current_time = new DateTimeImmutable();

        $post_representation                         = new DocmanOtherTypePOSTRepresentation();
        $post_representation->title                  = 'Title';
        $post_representation->permissions_for_groups = $permissions_for_groups_set;
        $post_representation->type                   = 'whatever';

        $this->document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->item_status_mapper->method('getItemStatusWithParentInheritance')
            ->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);

        $this->metadata_obsolesence_date_retriever
            ->method('getTimeStampOfDateWithoutPeriodValidity')
            ->with(ItemRepresentation::OBSOLESCENCE_DATE_NONE, $current_time)
            ->willReturn((int) ItemRepresentation::OBSOLESCENCE_DATE_NONE);

        $created_item = $this->createPartialMock(OtherDocument::class, ['getId', 'getParentId']);
        $created_item->method('getId')->willReturn(12);
        $created_item->method('getParentId')->willReturn(11);

        $this->item_factory->expects($this->once())->method('createWithoutOrdering')
            ->with('Title', '', 11, 100, 0, 222, Docman_Item::TYPE_OTHER, 'whatever', self::anything(), self::anything(), null, null)
            ->willReturn($created_item);

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);

        if ($permissions_for_groups_set !== null) {
            $this->permissions_for_groups_set_factory->expects($this->once())->method('fromRepresentation')
                ->willReturn(new DocmanItemPermissionsForGroupsSet([]));
        }

        $this->creator_visitor->expects($this->once())->method('visitOtherDocument')
            ->with(self::isInstanceOf(OtherDocument::class), self::callback(function (array $params) use ($permissions_for_groups_set): bool {
                if ($permissions_for_groups_set === null) {
                    self::assertNull($params['permissions_for_groups']);
                } else {
                    self::assertNotNull($params['permissions_for_groups']);
                }
                return true;
            }));

        $this->custom_metadata_checker->method('checkAndRetrieveFormattedRepresentation')->willReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this
            ->getItemCreator(EventDispatcherStub::withCallback(function (object $event): object {
                if ($event instanceof VerifyOtherTypeIsSupported) {
                    $event->flagAsSupported();
                }

                return $event;
            }))
            ->createOtherType(
                $parent_item,
                $user,
                $post_representation,
                $current_time,
                $project
            );

        self::assertSame(12, $created_item_representation->id);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('permissionsForGroupsSetRepresentationDataProvider')]
    public function testOtherTypeDocumentCannotBeCreatedIfWeDontKnowTheGivenType(?DocmanItemPermissionsForGroupsSetRepresentation $permissions_for_groups_set): void
    {
        $parent_item  = new Docman_Folder(['item_id' => 11]);
        $user         = UserTestBuilder::aUser()->withId(222)->build();
        $project      = ProjectTestBuilder::aProject()->withId(102)->build();
        $current_time = new DateTimeImmutable();

        $post_representation                         = new DocmanOtherTypePOSTRepresentation();
        $post_representation->title                  = 'Title';
        $post_representation->permissions_for_groups = $permissions_for_groups_set;
        $post_representation->type                   = 'whatever';

        $this->document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->item_status_mapper->method('getItemStatusWithParentInheritance')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);

        $this->metadata_obsolesence_date_retriever->method('getTimeStampOfDateWithoutPeriodValidity')
            ->with(ItemRepresentation::OBSOLESCENCE_DATE_NONE, $current_time)
            ->willReturn((int) ItemRepresentation::OBSOLESCENCE_DATE_NONE);

        $created_item = $this->createPartialMock(OtherDocument::class, ['getId', 'getParentId']);
        $created_item->method('getId')->willReturn(12);
        $created_item->method('getParentId')->willReturn(11);

        $this->item_factory->expects($this->never())->method('createWithoutOrdering');
        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->creator_visitor->expects($this->never())->method('visitOtherDocument');
        $this->custom_metadata_checker->expects($this->never())->method('checkAndRetrieveFormattedRepresentation');

        self::expectException(RestException::class);
        self::expectExceptionCode(400);

        $this->getItemCreator(EventDispatcherStub::withCallback(static fn(object $event) => $event))
            ->createOtherType(
                $parent_item,
                $user,
                $post_representation,
                $current_time,
                $project
            );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('permissionsForGroupsSetRepresentationDataProvider')]
    public function testWikiDocumentCanBeCreated(?DocmanItemPermissionsForGroupsSetRepresentation $permissions_for_groups_set): void
    {
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(222);
        $project      = ProjectTestBuilder::aProject()->withId(102)->withUsedService(Service::WIKI)->build();
        $current_time = new DateTimeImmutable();

        $post_representation                             = new DocmanWikiPOSTRepresentation();
        $post_representation->title                      = 'Title';
        $post_representation->wiki_properties            = new WikiPropertiesPOSTPATCHRepresentation();
        $post_representation->wiki_properties->page_name = 'Monchichi';
        $post_representation->permissions_for_groups     = $permissions_for_groups_set;

        $this->document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->item_status_mapper->method('getItemStatusWithParentInheritance')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);

        $this->metadata_obsolesence_date_retriever->method('getTimeStampOfDateWithoutPeriodValidity')->willReturn((int) ItemRepresentation::OBSOLESCENCE_DATE_NONE);

        $created_item = new Docman_Wiki(['item_id' => 12, 'parent_id' => 11, 'wiki_page' => '']);

        if ($permissions_for_groups_set !== null) {
            $this->permissions_for_groups_set_factory->expects($this->once())->method('fromRepresentation')
                ->willReturn(new DocmanItemPermissionsForGroupsSet([]));
        }

        $this->creator_visitor->expects($this->once())->method('visitWiki')
            ->with(self::isInstanceOf(Docman_Wiki::class), self::callback(function (array $params) use ($permissions_for_groups_set): bool {
                if ($permissions_for_groups_set === null) {
                    self::assertNull($params['permissions_for_groups']);
                } else {
                    self::assertNotNull($params['permissions_for_groups']);
                }
                return true;
            }));

        $this->item_factory->expects($this->once())->method('createWithoutOrdering')
            ->with('Title', '', 11, 100, (int) ItemRepresentation::OBSOLESCENCE_DATE_NONE, 222, PLUGIN_DOCMAN_ITEM_TYPE_WIKI, '', self::anything(), self::anything(), 'Monchichi', null)
            ->willReturn($created_item);

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);

        $this->custom_metadata_checker->method('checkAndRetrieveFormattedRepresentation')->willReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this
            ->getItemCreator(EventDispatcherStub::withIdentityCallback())
            ->createWiki(
                $parent_item,
                $user,
                $post_representation,
                $current_time,
                $project
            );

        self::assertSame(12, $created_item_representation->id);
    }

    public function testWikiDocumentCannotBeCreatedIfServiceWikiIsNotAvailable(): void
    {
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(222);
        $project      = ProjectTestBuilder::aProject()->withId(102)->withoutServices()->build();
        $current_time = new DateTimeImmutable();

        $post_representation                             = new DocmanWikiPOSTRepresentation();
        $post_representation->title                      = 'Title';
        $post_representation->wiki_properties            = new WikiPropertiesPOSTPATCHRepresentation();
        $post_representation->wiki_properties->page_name = 'Monchichi';

        $this->document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->item_factory->expects($this->never())->method('createWithoutOrdering');

        $this->custom_metadata_checker->method('checkAndRetrieveFormattedRepresentation')->willReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);

        self::expectException(RestException::class);
        self::expectExceptionCode(400);

        $this
            ->getItemCreator(EventDispatcherStub::withIdentityCallback())
            ->createWiki(
                $parent_item,
                $user,
                $post_representation,
                $current_time,
                $project
            );
    }

    public function testFileDocumentCanBeCreated(): void
    {
        $parent_item  = new Docman_Item(['item_id' => 3, 'group_id' => 104]);
        $user         = UserTestBuilder::buildWithDefaults();
        $current_time = new DateTimeImmutable();

        $post_representation                            = new DocmanPOSTFilesRepresentation();
        $post_representation->title                     = 'Title';
        $post_representation->description               = '';
        $file_properties_post_representation            = new FilePropertiesPOSTPATCHRepresentation();
        $file_properties_post_representation->file_size = 123456;
        $file_properties_post_representation->file_name = 'myfile';
        $post_representation->file_properties           = $file_properties_post_representation;

        $this->document_to_upload_creator->expects($this->once())->method('create')->willReturn(new DocumentToUpload(12));

        $this->item_status_mapper->method('getItemStatusWithParentInheritance')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);

        $this->metadata_obsolesence_date_retriever->method('getTimeStampOfDateWithoutPeriodValidity')->willReturn((int) ItemRepresentation::OBSOLESCENCE_DATE_NONE);

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);

        $metadata_to_create = MetadataToCreate::buildMetadataRepresentation([], false);

        $created_item_representation = $this
            ->getItemCreator(EventDispatcherStub::withIdentityCallback())
            ->createFileDocument(
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

        self::assertSame(12, $created_item_representation->id);
        self::assertNotNull($created_item_representation->file_properties);
    }

    public function testItThrowsAnExceptionWhenDocumentHasSameNameThanCreatedFile(): void
    {
        $parent_item  = new Docman_Item(['item_id' => 3]);
        $user         = UserTestBuilder::buildWithDefaults();
        $current_time = new DateTimeImmutable();

        $post_representation                            = new DocmanPOSTFilesRepresentation();
        $post_representation->title                     = 'Title';
        $post_representation->description               = '';
        $file_properties_post_representation            = new FilePropertiesPOSTPATCHRepresentation();
        $file_properties_post_representation->file_size = 123456;
        $file_properties_post_representation->file_name = 'myfile';
        $post_representation->file_properties           = $file_properties_post_representation;

        $this->document_to_upload_creator->expects($this->never())->method('create');

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(true);

        $this->custom_metadata_checker->expects($this->never())->method('checkAndRetrieveFormattedRepresentation');

        $metadata_to_create = MetadataToCreate::buildMetadataRepresentation([], false);

        self::expectException(RestException::class);
        self::expectExceptionCode(400);

        $this->getItemCreator(EventDispatcherStub::withIdentityCallback())
            ->createFileDocument(
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
        $parent_item  = new Docman_Item(['item_id' => 1]);
        $user         = UserTestBuilder::buildWithDefaults();
        $project      = ProjectTestBuilder::aProject()->build();
        $current_time = new DateTimeImmutable();

        $post_representation        = new DocmanEmptyPOSTRepresentation();
        $post_representation->title = 'Title';

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);

        $this->document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(true);

        $this->custom_metadata_checker->method('checkAndRetrieveFormattedRepresentation')->willReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        self::expectException(RestException::class);
        self::expectExceptionCode(409);

        $this->getItemCreator(EventDispatcherStub::withIdentityCallback())
            ->createEmpty(
                $parent_item,
                $user,
                $post_representation,
                $current_time,
                $project
            );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('permissionsForGroupsSetRepresentationDataProvider')]
    public function testLinkDocumentCanBeCreated(?DocmanItemPermissionsForGroupsSetRepresentation $permissions_for_groups_set): void
    {
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(222);
        $project      = ProjectTestBuilder::aProject()->withId(102)->build();
        $current_time = new DateTimeImmutable();

        $post_representation        = new DocmanLinkPOSTRepresentation();
        $post_representation->title = 'Mie faboulouse linke';
        $docman_link                = $this->createMock(Docman_LinkVersion::class);
        $docman_link->method('getLink')->willReturn('https://my.example.test');
        $post_representation->link_properties        = LinkPropertiesRepresentation::build($docman_link);
        $post_representation->permissions_for_groups = $permissions_for_groups_set;

        $this->document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->item_status_mapper->method('getItemStatusWithParentInheritance')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);

        $this->metadata_obsolesence_date_retriever->method('getTimeStampOfDateWithoutPeriodValidity')->willReturn((int) ItemRepresentation::OBSOLESCENCE_DATE_NONE);

        if ($permissions_for_groups_set !== null) {
            $this->permissions_for_groups_set_factory->expects($this->once())->method('fromRepresentation')
                ->willReturn(new DocmanItemPermissionsForGroupsSet([]));
        }

        $this->creator_visitor->expects($this->once())->method('visitLink')
            ->with(self::isInstanceOf(Docman_Link::class), self::callback(function (array $params) use ($permissions_for_groups_set): bool {
                if ($permissions_for_groups_set === null) {
                    self::assertNull($params['permissions_for_groups']);
                } else {
                    self::assertNotNull($params['permissions_for_groups']);
                }
                return true;
            }));

        $created_item = new Docman_Link(['item_id' => 12, 'parent_id' => 11, 'link_url' => '']);

        $this->item_factory->expects($this->once())->method('createWithoutOrdering')
            ->with(
                'Mie faboulouse linke',
                '',
                11,
                100,
                0,
                222,
                PLUGIN_DOCMAN_ITEM_TYPE_LINK,
                '',
                self::anything(),
                self::anything(),
                null,
                'https://my.example.test'
            )
            ->willReturn($created_item);

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);

        $this->link_validity_checker->method('checkLinkValidity');

        $this->custom_metadata_checker->method('checkAndRetrieveFormattedRepresentation')->willReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this->getItemCreator(EventDispatcherStub::withIdentityCallback())
            ->createLink(
                $parent_item,
                $user,
                $post_representation,
                $current_time,
                $project
            );

        self::assertSame(12, $created_item_representation->id);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('permissionsForGroupsSetRepresentationDataProvider')]
    public function testFolderCanBeCreated(?DocmanItemPermissionsForGroupsSetRepresentation $permissions_for_groups_set): void
    {
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(222);
        $project      = ProjectTestBuilder::aProject()->withId(102)->build();
        $current_time = new DateTimeImmutable();

        $post_representation                         = new DocmanFolderPOSTRepresentation();
        $post_representation->title                  = 'Title';
        $post_representation->description            = '';
        $post_representation->permissions_for_groups = $permissions_for_groups_set;

        $this->document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->item_status_mapper->method('getItemStatusWithParentInheritance')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);

        $this->metadata_obsolesence_date_retriever->method('getTimeStampOfDateWithoutPeriodValidity')->willReturn((int) ItemRepresentation::OBSOLESCENCE_DATE_NONE);

        if ($permissions_for_groups_set !== null) {
            $this->permissions_for_groups_set_factory->expects($this->once())->method('fromRepresentation')
                ->willReturn(new DocmanItemPermissionsForGroupsSet([]));
        }

        $this->creator_visitor->expects($this->once())->method('visitFolder')
            ->with(self::isInstanceOf(Docman_Folder::class), self::callback(function (array $params) use ($permissions_for_groups_set): bool {
                if ($permissions_for_groups_set === null) {
                    self::assertNull($params['permissions_for_groups']);
                } else {
                    self::assertNotNull($params['permissions_for_groups']);
                }
                return true;
            }));

        $created_item = new Docman_Folder(['item_id' => 12, 'parent_id' => 11]);

        $this->item_factory->expects($this->once())->method('createWithoutOrdering')
            ->with('Title', '', 11, 100, 0, 222, PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, '', self::anything(), self::anything(), null, null)
            ->willReturn($created_item);

        $this->item_factory->method('doesTitleCorrespondToExistingFolder')->willReturn(false);

        $this->custom_metadata_checker->method('checkAndRetrieveFormattedRepresentation')->willReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this->getItemCreator(EventDispatcherStub::withIdentityCallback())
            ->createFolder(
                $parent_item,
                $user,
                $post_representation,
                $current_time,
                $project
            );

        self::assertSame(12, $created_item_representation->id);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('permissionsForGroupsSetRepresentationDataProvider')]
    public function testEmbeddedFileCanBeCreated(?DocmanItemPermissionsForGroupsSetRepresentation $permissions_for_groups_set): void
    {
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(222);
        $project      = ProjectTestBuilder::aProject()->withId(102)->build();
        $current_time = new DateTimeImmutable();

        $post_representation                               = new DocmanEmbeddedPOSTRepresentation();
        $post_representation->title                        = 'Embedded file';
        $post_representation->embedded_properties          = new EmbeddedPropertiesPOSTPATCHRepresentation();
        $post_representation->embedded_properties->content = 'My original content :)';
        $post_representation->permissions_for_groups       = $permissions_for_groups_set;

        $this->document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->item_status_mapper->method('getItemStatusWithParentInheritance')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);

        $this->metadata_obsolesence_date_retriever->method('getTimeStampOfDateWithoutPeriodValidity')->willReturn((int) ItemRepresentation::OBSOLESCENCE_DATE_NONE);

        if ($permissions_for_groups_set !== null) {
            $this->permissions_for_groups_set_factory->expects($this->once())->method('fromRepresentation')
                ->willReturn(new DocmanItemPermissionsForGroupsSet([]));
        }

        $this->creator_visitor->expects($this->once())->method('visitEmbeddedFile')
            ->with(self::isInstanceOf(Docman_EmbeddedFile::class), self::callback(function (array $params) use ($permissions_for_groups_set): bool {
                if ($permissions_for_groups_set === null) {
                    self::assertNull($params['permissions_for_groups']);
                } else {
                    self::assertNotNull($params['permissions_for_groups']);
                }
                return true;
            }));

        $created_item = new Docman_EmbeddedFile(['item_id' => 12, 'parent_id' => 11, 'group_id' => 102]);

        $this->item_factory->expects($this->once())->method('createWithoutOrdering')
            ->with('Embedded file', '', 11, 100, 0, 222, PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE, '', self::anything(), self::anything(), null, null)
            ->willReturn($created_item);

        $this->item_factory->method('doesTitleCorrespondToExistingFolder')->willReturn(false);
        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);

        $this->custom_metadata_checker->method('checkAndRetrieveFormattedRepresentation')->willReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this->getItemCreator(EventDispatcherStub::withIdentityCallback())
            ->createEmbedded(
                $parent_item,
                $user,
                $post_representation,
                $current_time,
                $project
            );

        self::assertSame(12, $created_item_representation->id);
    }

    public function testItemAreRejectedIfItemWIthSameNameAlreadyExists(): void
    {
        $parent_item  = new Docman_Item(['item_id' => 1]);
        $user         = UserTestBuilder::buildWithDefaults();
        $project      = ProjectTestBuilder::aProject()->build();
        $current_time = new DateTimeImmutable();

        $post_representation        = new DocmanEmptyPOSTRepresentation();
        $post_representation->title = 'Title';

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(true);

        $this->custom_metadata_checker->expects($this->never())->method('checkAndRetrieveFormattedRepresentation');

        self::expectException(RestException::class);
        self::expectExceptionCode(400);

        $this->getItemCreator(EventDispatcherStub::withIdentityCallback())
            ->createEmpty(
                $parent_item,
                $user,
                $post_representation,
                $current_time,
                $project
            );
    }

    public function testItThrowsExceptionIfTheStatusMetadataIsNotUsedButSetInTheRepresentation(): void
    {
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(222);
        $project      = ProjectTestBuilder::aProject()->withId(102)->build();
        $current_time = new DateTimeImmutable();

        $post_representation              = new DocmanFolderPOSTRepresentation();
        $post_representation->title       = 'Title';
        $post_representation->description = '';
        $post_representation->status      = 'approved';

        $this->document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->item_status_mapper->method('getItemStatusWithParentInheritance')->willThrowException(
            HardCodedMetadataException::itemStatusNotAvailable()
        );

        $this->metadata_obsolesence_date_retriever->expects($this->never())->method('getTimeStampOfDateWithoutPeriodValidity');

        $this->item_factory->expects($this->never())->method('createWithoutOrdering');

        $this->item_factory->method('doesTitleCorrespondToExistingFolder')->willReturn(false);

        $this->creator_visitor->expects($this->never())->method('visitFolder');
        $this->custom_metadata_checker->method('checkAndRetrieveFormattedRepresentation')->willReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        self::expectException(HardCodedMetadataException::class);
        self::expectExceptionMessage('Status is not enabled for project');

        $this->getItemCreator(EventDispatcherStub::withIdentityCallback())
            ->createFolder(
                $parent_item,
                $user,
                $post_representation,
                $current_time,
                $project
            );
    }

    public function testFolderCanBeCreatedWithStatus(): void
    {
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(222);
        $project      = ProjectTestBuilder::aProject()->withId(102)->build();
        $current_time = new DateTimeImmutable();

        $post_representation              = new DocmanFolderPOSTRepresentation();
        $post_representation->title       = 'Title';
        $post_representation->description = '';
        $post_representation->status      = 'approved';

        $this->document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->item_status_mapper->method('getItemStatusWithParentInheritance')->with($parent_item, $post_representation->status)
            ->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);

        $this->metadata_obsolesence_date_retriever->method('getTimeStampOfDateWithoutPeriodValidity')->willReturn((int) ItemRepresentation::OBSOLESCENCE_DATE_NONE);

        $created_item = new Docman_Folder(['item_id' => 12, 'parent_id' => 11]);

        $this->item_factory->expects($this->once())->method('createWithoutOrdering')
            ->with(
                'Title',
                '',
                11,
                PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
                0,
                222,
                PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
                '',
                self::anything(),
                self::anything(),
                null,
                null
            )
            ->willReturn($created_item);

        $this->item_factory->method('doesTitleCorrespondToExistingFolder')->willReturn(false);

        $this->creator_visitor->expects($this->once())->method('visitFolder');
        $this->custom_metadata_checker->method('checkAndRetrieveFormattedRepresentation')->willReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this->getItemCreator(EventDispatcherStub::withIdentityCallback())
            ->createFolder(
                $parent_item,
                $user,
                $post_representation,
                $current_time,
                $project
            );

        self::assertSame(12, $created_item_representation->id);
    }

    public function testCreateEmptyDocumentWithStatusAndObsolescenceDate(): void
    {
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(222);
        $project      = ProjectTestBuilder::aProject()->withId(102)->build();
        $current_time = new DateTimeImmutable();

        $post_representation                    = new DocmanEmptyPOSTRepresentation();
        $post_representation->title             = 'Title';
        $post_representation->status            = 'approved';
        $post_representation->obsolescence_date = '2019-10-11';

        $this->document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->item_status_mapper->method('getItemStatusWithParentInheritance')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);

        $obsolescence_date_time_stamp = 123456;
        $this->metadata_obsolesence_date_retriever->method('getTimeStampOfDateWithoutPeriodValidity')
            ->with($post_representation->obsolescence_date, $current_time)
            ->willReturn($obsolescence_date_time_stamp);

        $created_item = new Docman_Empty(['item_id' => 12, 'parent_id' => 11]);

        $this->item_factory->expects($this->once())->method('createWithoutOrdering')
            ->with('Title', '', 11, 100, $obsolescence_date_time_stamp, 222, PLUGIN_DOCMAN_ITEM_TYPE_EMPTY, '', self::anything(), self::anything(), null, null)
            ->willReturn($created_item);

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);

        $this->creator_visitor->expects($this->once())->method('visitEmpty');
        $this->custom_metadata_checker->method('checkAndRetrieveFormattedRepresentation')->willReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this->getItemCreator(EventDispatcherStub::withIdentityCallback())
            ->createEmpty(
                $parent_item,
                $user,
                $post_representation,
                $current_time,
                $project
            );

        self::assertSame(12, $created_item_representation->id);
    }

    public function testCreateEmbeddedDocumentWithStatusAndObsolescenceDate(): void
    {
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(222);
        $project      = ProjectTestBuilder::aProject()->withId(102)->build();
        $current_time = new DateTimeImmutable();

        $post_representation                               = new DocmanEmbeddedPOSTRepresentation();
        $post_representation->title                        = 'Embedded file with status and Obsolescence date';
        $post_representation->embedded_properties          = new EmbeddedPropertiesPOSTPATCHRepresentation();
        $post_representation->embedded_properties->content = 'My original content :)... Not So original :(';
        $post_representation->status                       = 'approved';
        $post_representation->obsolescence_date            = '2019-10-11';

        $this->document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->item_status_mapper->method('getItemStatusWithParentInheritance')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);

        $obsolescence_date_time_stamp = 123456;
        $this->metadata_obsolesence_date_retriever->method('getTimeStampOfDateWithoutPeriodValidity')
            ->with($post_representation->obsolescence_date, $current_time)
            ->willReturn($obsolescence_date_time_stamp);

        $created_item = new Docman_Empty(['item_id' => 12, 'parent_id' => 11]);

        $this->item_factory->expects($this->once())->method('createWithoutOrdering')
            ->with(
                'Embedded file with status and Obsolescence date',
                '',
                11,
                PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
                $obsolescence_date_time_stamp,
                222,
                PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE,
                '',
                self::anything(),
                self::anything(),
                null,
                null
            )
            ->willReturn($created_item);

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);

        $this->creator_visitor->expects($this->once())->method('visitEmpty');
        $this->custom_metadata_checker->method('checkAndRetrieveFormattedRepresentation')->willReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this->getItemCreator(EventDispatcherStub::withIdentityCallback())
            ->createEmbedded(
                $parent_item,
                $user,
                $post_representation,
                $current_time,
                $project
            );

        self::assertSame(12, $created_item_representation->id);
    }

    public function testCreateLinkDocumentWithStatusAndObsolescenceDate(): void
    {
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(222);
        $project      = ProjectTestBuilder::aProject()->withId(102)->build();
        $current_time = new DateTimeImmutable();

        $post_representation        = new DocmanLinkPOSTRepresentation();
        $post_representation->title = 'Link with status and Obsolescence date';
        $docman_link                = $this->createMock(Docman_LinkVersion::class);
        $docman_link->method('getLink')->willReturn('https://my.example.test');
        $post_representation->link_properties   = LinkPropertiesRepresentation::build($docman_link);
        $post_representation->status            = 'approved';
        $post_representation->obsolescence_date = '2019-10-11';

        $this->document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->item_status_mapper->method('getItemStatusWithParentInheritance')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_REJECTED);

        $obsolescence_date_time_stamp = 123456;
        $this->metadata_obsolesence_date_retriever->method('getTimeStampOfDateWithoutPeriodValidity')
            ->with($post_representation->obsolescence_date, $current_time)
            ->willReturn($obsolescence_date_time_stamp);

        $created_item = new Docman_Link(['item_id' => 12, 'parent_id' => 11, 'link_url' => '']);

        $this->item_factory->expects($this->once())->method('createWithoutOrdering')
            ->with(
                'Link with status and Obsolescence date',
                '',
                11,
                PLUGIN_DOCMAN_ITEM_STATUS_REJECTED,
                $obsolescence_date_time_stamp,
                222,
                PLUGIN_DOCMAN_ITEM_TYPE_LINK,
                '',
                self::anything(),
                self::anything(),
                null,
                'https://my.example.test'
            )
            ->willReturn($created_item);

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);

        $this->creator_visitor->expects($this->once())->method('visitLink');
        $this->custom_metadata_checker->method('checkAndRetrieveFormattedRepresentation')->willReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $this->link_validity_checker->expects($this->once())->method('checkLinkValidity');

        $created_item_representation = $this->getItemCreator(EventDispatcherStub::withIdentityCallback())
            ->createLink(
                $parent_item,
                $user,
                $post_representation,
                $current_time,
                $project
            );

        self::assertSame(12, $created_item_representation->id);
    }

    public function testWikiDocumentCanBeCreatedWithStatusAndObsolescenceDate(): void
    {
        $parent_item  = new Docman_Item(['item_id' => 11]);
        $user         = UserTestBuilder::buildWithId(222);
        $project      = ProjectTestBuilder::aProject()->withId(102)->withUsedService(Service::WIKI)->build();
        $current_time = new DateTimeImmutable();

        $post_representation                             = new DocmanWikiPOSTRepresentation();
        $post_representation->title                      = 'wiki';
        $post_representation->wiki_properties            = new WikiPropertiesPOSTPATCHRepresentation();
        $post_representation->wiki_properties->page_name = 'Monchocho';
        $post_representation->status                     = 'rejected';
        $post_representation->obsolescence_date          = '2019-10-11';

        $this->document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->item_status_mapper->method('getItemStatusWithParentInheritance')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_REJECTED);

        $obsolescence_date_time_stamp = 123456;
        $this->metadata_obsolesence_date_retriever->method('getTimeStampOfDateWithoutPeriodValidity')
            ->with($post_representation->obsolescence_date, $current_time)
            ->willReturn($obsolescence_date_time_stamp);

        $created_item = new Docman_Wiki(['item_id' => 12, 'parent_id' => 11, 'wiki_page' => '']);

        $this->item_factory->expects($this->once())->method('createWithoutOrdering')
            ->with(
                'wiki',
                '',
                11,
                PLUGIN_DOCMAN_ITEM_STATUS_REJECTED,
                $obsolescence_date_time_stamp,
                222,
                PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
                '',
                self::anything(),
                self::anything(),
                'Monchocho',
                null
            )
            ->willReturn($created_item);

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);

        $this->creator_visitor->expects($this->once())->method('visitWiki');
        $this->custom_metadata_checker->method('checkAndRetrieveFormattedRepresentation')->willReturn(
            MetadataToCreate::buildMetadataRepresentation([], false)
        );

        $created_item_representation = $this->getItemCreator(EventDispatcherStub::withIdentityCallback())
            ->createWiki(
                $parent_item,
                $user,
                $post_representation,
                $current_time,
                $project
            );

        self::assertSame(12, $created_item_representation->id);
    }

    public function testFileDocumentCanBeCreatedWithStatusAndObsolescenceDate(): void
    {
        $parent_item  = new Docman_Item(['item_id' => 3, 'group_id' => 101]);
        $user         = UserTestBuilder::buildWithDefaults();
        $current_time = new DateTimeImmutable();

        $post_representation                            = new DocmanPOSTFilesRepresentation();
        $post_representation->title                     = 'Title';
        $post_representation->description               = '';
        $post_representation->status                    = 'approved';
        $post_representation->obsolescence_date         = '2019-03-08';
        $file_properties_post_representation            = new FilePropertiesPOSTPATCHRepresentation();
        $file_properties_post_representation->file_size = 123456;
        $file_properties_post_representation->file_name = 'myfile';
        $post_representation->file_properties           = $file_properties_post_representation;

        $this->document_to_upload_creator->expects($this->once())->method('create')->willReturn(new DocumentToUpload(12));

        $this->item_status_mapper->method('getItemStatusWithParentInheritance')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);

        $obsolescence_date_time_stamp = 123456;
        $this->metadata_obsolesence_date_retriever->method('getTimeStampOfDateWithoutPeriodValidity')
            ->with($post_representation->obsolescence_date, $current_time)
            ->willReturn($obsolescence_date_time_stamp);

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->custom_metadata_checker->expects($this->never())->method('checkAndRetrieveFormattedRepresentation');

        $metadata_to_create          = MetadataToCreate::buildMetadataRepresentation([], false);
        $created_item_representation = $this->getItemCreator(EventDispatcherStub::withIdentityCallback())
            ->createFileDocument(
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

        self::assertSame(12, $created_item_representation->id);
        self::assertNotNull($created_item_representation->file_properties);
    }

    public static function permissionsForGroupsSetRepresentationDataProvider(): array
    {
        return [
            [null],
            [new DocmanItemPermissionsForGroupsSetRepresentation()],
        ];
    }
}
