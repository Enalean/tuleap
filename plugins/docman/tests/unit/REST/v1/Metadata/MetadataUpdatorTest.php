<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

namespace Tuleap\Docman\REST\v1\Metadata;

use Docman_Metadata;
use Docman_SettingsBo;
use LogicException;
use Luracast\Restler\RestException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use RuntimeException;
use Tuleap\Docman\Metadata\MetadataEventProcessor;
use Tuleap\Docman\Metadata\MetadataRecursiveUpdator;
use Tuleap\Docman\Metadata\MetadataValueUpdator;
use Tuleap\Docman\Metadata\Owner\OwnerRetriever;
use Tuleap\Docman\REST\v1\ItemRepresentation;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\REST\I18NRestException;

final class MetadataUpdatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface|MetadataValueUpdator
     */
    private $metadata_value_updator;
    /**
     * @var \Mockery\MockInterface|CustomMetadataRepresentationRetriever
     */
    private $representation_retriever;
    /**
     * @var \Mockery\MockInterface|DocumentOngoingUploadRetriever
     */
    private $document_on_going_retriever;
    /**
     * @var \Mockery\MockInterface|MetadataRecursiveUpdator
     */
    private $recursive_updator;
    /**
     * @var \Mockery\MockInterface|MetadataEventProcessor
     */
    private $event_processor;
    /**
     * @var \Mockery\MockInterface|OwnerRetriever
     */
    private $owner_retriever;

    /**
     * @var \Docman_ItemFactory|\Mockery\MockInterface
     */
    private $item_factory;
    /**
     * @var \Mockery\MockInterface|ItemStatusMapper
     */
    private $status_mapper;
    /**
     * @var \Mockery\MockInterface|HardcodedMetadataObsolescenceDateRetriever
     */
    private $obsolescence_date_retriever;
    /**
     * @var MetadataUpdator
     */
    private $updator;
    /**
     * @var \Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var Docman_SettingsBo&\PHPUnit\Framework\MockObject\MockObject
     */
    private $docman_settings_bo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->item_factory                = \Mockery::mock(\Docman_ItemFactory::class);
        $this->status_mapper               = \Mockery::mock(ItemStatusMapper::class);
        $this->obsolescence_date_retriever = \Mockery::mock(HardcodedMetadataObsolescenceDateRetriever::class);
        $this->user_manager                = \Mockery::mock(\UserManager::class);
        $this->owner_retriever             = \Mockery::mock(OwnerRetriever::class);
        $this->event_processor             = \Mockery::mock(MetadataEventProcessor::class);
        $this->recursive_updator           = \Mockery::mock(MetadataRecursiveUpdator::class);
        $this->document_on_going_retriever = \Mockery::mock(DocumentOngoingUploadRetriever::class);
        $this->representation_retriever    = \Mockery::mock(CustomMetadataRepresentationRetriever::class);
        $this->metadata_value_updator      = \Mockery::mock(MetadataValueUpdator::class);
        $this->docman_settings_bo          = $this->createMock(Docman_SettingsBo::class);

        $this->updator = new MetadataUpdator(
            $this->item_factory,
            $this->status_mapper,
            $this->obsolescence_date_retriever,
            $this->user_manager,
            $this->owner_retriever,
            $this->event_processor,
            $this->recursive_updator,
            $this->document_on_going_retriever,
            $this->representation_retriever,
            $this->metadata_value_updator,
            $this->docman_settings_bo
        );
    }

    public function testDocumentOwnerCanBeChanged(): void
    {
        $date = new \DateTimeImmutable();
        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $this->obsolescence_date_retriever->shouldReceive('getTimeStampOfDate')->andReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getOwnerId')->andReturn($old_user_id);
        $item->shouldReceive('getId')->andReturn(10);
        $item->shouldReceive('getStatus')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $item->shouldReceive('getTitle')->andReturn("my title");
        $item->shouldReceive('getParentId')->andReturn(9);

        $old_user = \Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getUserById')->andReturn($old_user);
        $old_user->shouldReceive('getUserName')->andReturn('old user name');

        $new_user = \Mockery::mock(\PFUser::class);
        $new_user->shouldReceive('getUserName')->andReturn('new user name');
        $this->owner_retriever->shouldReceive('getUserFromRepresentationId')->andReturn($new_user);

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = "title";
        $representation->description       = "";
        $representation->owner_id          = 102;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_APPROVED;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $current_user = \Mockery::mock(\PFUser::class);

        $this->event_processor->shouldReceive('raiseUpdateEvent')
            ->withArgs([$item, $current_user, 'old user name', 'new user name', 'owner'])
            ->once();

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);
        $this->item_factory->shouldReceive('update')->once();

        $this->representation_retriever->shouldReceive('checkAndBuildMetadataToUpdate')->once()->andReturn([]);

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(\Mockery::mock(\Docman_Folder::class));
        $this->document_on_going_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturn(false);

        $this->mockStatusAndObsolescenceDateMetadataAreUsed();

        $this->updator->updateDocumentMetadata($representation, $item, $current_user);
    }

    public function testDocumentCustomMetadataCanBeChangedForRootFolder(): void
    {
        $date = new \DateTimeImmutable();
        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(
            PLUGIN_DOCMAN_ITEM_STATUS_APPROVED
        );
        $this->obsolescence_date_retriever->shouldReceive('getTimeStampOfDate')->andReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getOwnerId')->andReturn($old_user_id);
        $item->shouldReceive('getId')->andReturn(10);
        $item->shouldReceive('getStatus')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $item->shouldReceive('getTitle')->andReturn("my title");
        $item->shouldReceive('getParentId')->andReturn(0);

        $old_user = \Mockery::mock(\PFUser::class);
        $old_user->shouldReceive('getUserName')->andReturn('owner name');
        $this->owner_retriever->shouldReceive('getUserFromRepresentationId')->andReturn($old_user);
        $this->user_manager->shouldReceive('getUserById')->andReturn($old_user);
        $old_user->shouldReceive('getUserName')->andReturn('user name');

        $representation                             = new PUTMetadataRepresentation();
        $representation->title                      = "title";
        $representation->description                = "";
        $representation->owner_id                   = $old_user_id;
        $representation->status                     = ItemStatusMapper::ITEM_STATUS_APPROVED;
        $representation->obsolescence_date          = ItemRepresentation::OBSOLESCENCE_DATE_NONE;
        $custom_metadata_representation             = new POSTCustomMetadataRepresentation();
        $custom_metadata_representation->short_name = "field_1";
        $custom_metadata_representation->value      = "value";
        $representation->metadata                   = [$custom_metadata_representation];

        $current_user = \Mockery::mock(\PFUser::class);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);
        $this->item_factory->shouldReceive('update')->once();

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);

        $metadata_to_update_representation = MetadataToUpdate::buildMetadataRepresentation(
            $project_configured_metadata,
            $custom_metadata_representation->value,
            PUTRecursiveStatusRepresentation::RECURSION_ALL_ITEMS
        );
        $this->representation_retriever->shouldReceive('checkAndBuildMetadataToUpdate')->once()->andReturn(
            [$metadata_to_update_representation]
        );

        $this->item_factory->shouldReceive('getItemFromDb')->never();
        $this->document_on_going_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->never();
        $this->metadata_value_updator->shouldReceive('updateMetadata')
            ->withArgs([$metadata_to_update_representation->getMetadata(), 10, $metadata_to_update_representation->getValue()])->once();

        $this->mockStatusAndObsolescenceDateMetadataAreUsed();

        $this->updator->updateDocumentMetadata($representation, $item, $current_user);
    }

    public function testDocumentCustomMetadataCanBeChanged(): void
    {
        $date = new \DateTimeImmutable();
        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(
            PLUGIN_DOCMAN_ITEM_STATUS_APPROVED
        );
        $this->obsolescence_date_retriever->shouldReceive('getTimeStampOfDate')->andReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getOwnerId')->andReturn($old_user_id);
        $item->shouldReceive('getId')->andReturn(10);
        $item->shouldReceive('getStatus')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $item->shouldReceive('getTitle')->andReturn("my title");
        $item->shouldReceive('getParentId')->andReturn(9);

        $old_user = \Mockery::mock(\PFUser::class);
        $old_user->shouldReceive('getUserName')->andReturn('owner name');
        $this->owner_retriever->shouldReceive('getUserFromRepresentationId')->andReturn($old_user);
        $this->user_manager->shouldReceive('getUserById')->andReturn($old_user);
        $old_user->shouldReceive('getUserName')->andReturn('user name');

        $representation                             = new PUTMetadataRepresentation();
        $representation->title                      = "title";
        $representation->description                = "";
        $representation->owner_id                   = $old_user_id;
        $representation->status                     = ItemStatusMapper::ITEM_STATUS_APPROVED;
        $representation->obsolescence_date          = ItemRepresentation::OBSOLESCENCE_DATE_NONE;
        $custom_metadata_representation             = new POSTCustomMetadataRepresentation();
        $custom_metadata_representation->short_name = "field_1";
        $custom_metadata_representation->value      = "value";
        $representation->metadata                   = [$custom_metadata_representation];

        $current_user = \Mockery::mock(\PFUser::class);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);
        $this->item_factory->shouldReceive('update')->once();

        $project_configured_metadata = \Mockery::mock(Docman_Metadata::class);

        $metadata_to_update_representation = MetadataToUpdate::buildMetadataRepresentation(
            $project_configured_metadata,
            $custom_metadata_representation->value,
            PUTRecursiveStatusRepresentation::RECURSION_ALL_ITEMS
        );
        $this->representation_retriever->shouldReceive('checkAndBuildMetadataToUpdate')->once()->andReturn(
            [$metadata_to_update_representation]
        );

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(\Mockery::mock(\Docman_Folder::class));
        $this->document_on_going_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturn(false);

        $this->metadata_value_updator->shouldReceive('updateMetadata')
            ->withArgs([$metadata_to_update_representation->getMetadata(), 10, $metadata_to_update_representation->getValue()])->once();

        $this->mockStatusAndObsolescenceDateMetadataAreUsed();

        $this->updator->updateDocumentMetadata($representation, $item, $current_user);
    }

    public function testDocumentUpdateIsRejectedIfNewOwnerCanNotBeFound(): void
    {
        $date = new \DateTimeImmutable();
        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $this->obsolescence_date_retriever->shouldReceive('getTimeStampOfDate')->andReturn($date->getTimestamp());

        $item = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getOwnerId')->andReturn(101);
        $item->shouldReceive('getTitle')->andReturn("my title");
        $item->shouldReceive('getParentId')->andReturn(9);

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = 'title';
        $representation->description       = '';
        $representation->owner_id          = 0;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_APPROVED;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);
        $this->owner_retriever->shouldReceive('getUserFromRepresentationId')->andReturn(null);

        $this->representation_retriever->shouldReceive('checkAndBuildMetadataToUpdate')->never();

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(\Mockery::mock(\Docman_Folder::class));
        $this->document_on_going_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturn(false);

        $this->updator->updateDocumentMetadata(
            $representation,
            $item,
            \Mockery::mock(\PFUser::class)
        );
    }

    public function testDocumentUpdateIsInterruptedWhenThePreviousOwnerCanNotBeFoundAsTheWholeDocumentIsLikelyToBeBroken(): void
    {
        $date = new \DateTimeImmutable();
        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $this->obsolescence_date_retriever->shouldReceive('getTimeStampOfDate')->andReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getOwnerId')->andReturn($old_user_id);
        $item->shouldReceive('getId')->andReturn(10);
        $item->shouldReceive('getStatus')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $item->shouldReceive('getTitle')->andReturn("my title");
        $item->shouldReceive('getParentId')->andReturn(9);

        $this->user_manager->shouldReceive('getUserById')->andReturn(null);

        $new_user = \Mockery::mock(\PFUser::class);
        $new_user->shouldReceive('getUserName')->andReturn('new user name');
        $this->owner_retriever->shouldReceive('getUserFromRepresentationId')->andReturn($new_user);

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = "title";
        $representation->description       = "";
        $representation->owner_id          = 102;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_APPROVED;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $this->representation_retriever->shouldReceive('checkAndBuildMetadataToUpdate')->once(
        )->andReturn([]);

        $this->expectException(RuntimeException::class);

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(\Mockery::mock(\Docman_Folder::class));
        $this->document_on_going_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturn(false);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);
        $this->updator->updateDocumentMetadata($representation, $item, \Mockery::mock(\PFUser::class));
    }

    public function testDocumentStatusIsUpdated(): void
    {
        $date = new \DateTimeImmutable();
        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_DRAFT);
        $this->obsolescence_date_retriever->shouldReceive('getTimeStampOfDate')->andReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getOwnerId')->andReturn($old_user_id);
        $item->shouldReceive('getId')->andReturn(10);
        $item->shouldReceive('getStatus')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $item->shouldReceive('getTitle')->andReturn("my title");
        $item->shouldReceive('getParentId')->andReturn(9);

        $old_user = \Mockery::mock(\PFUser::class);
        $old_user->shouldReceive('getUserName')->andReturn('owner name');
        $this->owner_retriever->shouldReceive('getUserFromRepresentationId')->andReturn($old_user);
        $this->user_manager->shouldReceive('getUserById')->andReturn($old_user);
        $old_user->shouldReceive('getUserName')->andReturn('user name');

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = "title";
        $representation->description       = "";
        $representation->owner_id          = $old_user_id;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_DRAFT;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $current_user = \Mockery::mock(\PFUser::class);

        $this->event_processor->shouldReceive('raiseUpdateEvent')
            ->withArgs([$item, $current_user, PLUGIN_DOCMAN_ITEM_STATUS_APPROVED, PLUGIN_DOCMAN_ITEM_STATUS_DRAFT, 'status'])
            ->once();

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);
        $this->item_factory->shouldReceive('update')->once();

        $this->representation_retriever->shouldReceive('checkAndBuildMetadataToUpdate')->once(
        )->andReturn([]);

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(\Mockery::mock(\Docman_Folder::class));
        $this->document_on_going_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturn(false);

        $this->mockStatusAndObsolescenceDateMetadataAreUsed();

        $this->updator->updateDocumentMetadata($representation, $item, $current_user);
    }

    public function testDocumentStatusIsNotUpdatedIfStatusMetadataNotUsed(): void
    {
        $date = new \DateTimeImmutable();
        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);
        $this->obsolescence_date_retriever->shouldReceive('getTimeStampOfDate')->andReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getOwnerId')->andReturn($old_user_id);
        $item->shouldReceive('getId')->andReturn(10);
        $item->shouldReceive('getStatus')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $item->shouldReceive('getTitle')->andReturn("my title");
        $item->shouldReceive('getParentId')->andReturn(9);

        $old_user = \Mockery::mock(\PFUser::class);
        $old_user->shouldReceive('getUserName')->andReturn('owner name');
        $this->owner_retriever->shouldReceive('getUserFromRepresentationId')->andReturn($old_user);
        $this->user_manager->shouldReceive('getUserById')->andReturn($old_user);
        $old_user->shouldReceive('getUserName')->andReturn('user name');

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = "title";
        $representation->description       = "";
        $representation->owner_id          = $old_user_id;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_NONE;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $current_user = \Mockery::mock(\PFUser::class);

        $this->event_processor->shouldReceive('raiseUpdateEvent')
            ->withArgs([$item, $current_user, \Mockery::any(), \Mockery::any(), 'status'])
            ->never();

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);
        $this->item_factory->shouldReceive('update')->once();

        $this->representation_retriever->shouldReceive('checkAndBuildMetadataToUpdate')->once(
        )->andReturn([]);

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(\Mockery::mock(\Docman_Folder::class));
        $this->document_on_going_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturn(false);

        $this->mockStatusAndObsolescenceDateMetadataAreUnused();

        $this->updator->updateDocumentMetadata($representation, $item, $current_user);
    }

    public function testDocumentObsolescenceDateIsNotUpdatedIfStatusMetadataNotUsed(): void
    {
        $date = new \DateTimeImmutable();
        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);
        $this->obsolescence_date_retriever->shouldReceive('getTimeStampOfDate')->andReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getOwnerId')->andReturn($old_user_id);
        $item->shouldReceive('getId')->andReturn(10);
        $item->shouldReceive('getStatus')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $item->shouldReceive('getTitle')->andReturn("my title");
        $item->shouldReceive('getParentId')->andReturn(9);

        $old_user = \Mockery::mock(\PFUser::class);
        $old_user->shouldReceive('getUserName')->andReturn('owner name');
        $this->owner_retriever->shouldReceive('getUserFromRepresentationId')->andReturn($old_user);
        $this->user_manager->shouldReceive('getUserById')->andReturn($old_user);
        $old_user->shouldReceive('getUserName')->andReturn('user name');

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = "title";
        $representation->description       = "";
        $representation->owner_id          = $old_user_id;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_NONE;
        $representation->obsolescence_date = $date->format("Y-m-d");

        $current_user = \Mockery::mock(\PFUser::class);

        $this->event_processor->shouldReceive('raiseUpdateEvent')
            ->withArgs([$item, $current_user, \Mockery::any(), \Mockery::any(), 'status'])
            ->never();

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);
        $this->item_factory
            ->shouldReceive('update')
            ->with([
                'id'                => 10,
                'title'             => "title",
                'description'       => "",
                'user_id'           => $old_user_id,
            ])
            ->once();

        $this->representation_retriever->shouldReceive('checkAndBuildMetadataToUpdate')->once(
        )->andReturn([]);

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(\Mockery::mock(\Docman_Folder::class));
        $this->document_on_going_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturn(false);

        $this->mockStatusAndObsolescenceDateMetadataAreUnused();

        $this->updator->updateDocumentMetadata($representation, $item, $current_user);
    }

    public function testDocumentUpdateIsInterruptedWhenAnOtherItemHasTheSameTitle(): void
    {
        $date = new \DateTimeImmutable();
        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $this->obsolescence_date_retriever->shouldReceive('getTimeStampOfDate')->andReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getOwnerId')->andReturn($old_user_id);
        $item->shouldReceive('getId')->andReturn(10);
        $item->shouldReceive('getStatus')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $item->shouldReceive('getTitle')->andReturn("my title");
        $item->shouldReceive('getParentId')->andReturn(9);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getUserName')->andReturn('new user name');
        $this->user_manager->shouldReceive('getUserById')->andReturn($user);
        $this->owner_retriever->shouldReceive('getUserFromRepresentationId')->andReturn($user);

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = "title";
        $representation->description       = "";
        $representation->owner_id          = 102;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_APPROVED;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $this->representation_retriever->shouldReceive('checkAndBuildMetadataToUpdate')->never();

        $this->expectException(RestException::class);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(true);
        $this->updator->updateDocumentMetadata($representation, $item, \Mockery::mock(\PFUser::class));
    }

    public function testDocumentUpdateIsInterruptedWhenThereIsAnOnGoingUploadWIthTheSameTitle(): void
    {
        $date = new \DateTimeImmutable();
        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $this->obsolescence_date_retriever->shouldReceive('getTimeStampOfDate')->andReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getOwnerId')->andReturn($old_user_id);
        $item->shouldReceive('getId')->andReturn(10);
        $item->shouldReceive('getStatus')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $item->shouldReceive('getTitle')->andReturn("my title");
        $item->shouldReceive('getParentId')->andReturn(9);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getUserName')->andReturn('new user name');
        $this->user_manager->shouldReceive('getUserById')->andReturn($user);
        $this->owner_retriever->shouldReceive('getUserFromRepresentationId')->andReturn($user);

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = "title";
        $representation->description       = "";
        $representation->owner_id          = 102;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_APPROVED;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $this->representation_retriever->shouldReceive('checkAndBuildMetadataToUpdate')->never();

        $this->expectException(I18NRestException::class);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);
        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(\Mockery::mock(\Docman_Folder::class));
        $this->document_on_going_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturn(true);

        $this->updator->updateDocumentMetadata($representation, $item, \Mockery::mock(\PFUser::class));
    }

    public function testDocumentUpdateIsInterruptedWhenParentItemIsNotFound(): void
    {
        $date = new \DateTimeImmutable();
        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $this->obsolescence_date_retriever->shouldReceive('getTimeStampOfDate')->andReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getOwnerId')->andReturn($old_user_id);
        $item->shouldReceive('getId')->andReturn(10);
        $item->shouldReceive('getStatus')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $item->shouldReceive('getTitle')->andReturn("my title");
        $item->shouldReceive('getParentId')->andReturn(9);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getUserName')->andReturn('new user name');
        $this->user_manager->shouldReceive('getUserById')->andReturn($user);
        $this->owner_retriever->shouldReceive('getUserFromRepresentationId')->andReturn($user);

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = "title";
        $representation->description       = "";
        $representation->owner_id          = 102;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_APPROVED;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $this->representation_retriever->shouldReceive('checkAndBuildMetadataToUpdate')->never();

        $this->expectException(LogicException::class);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);
        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(null);

        $this->updator->updateDocumentMetadata($representation, $item, \Mockery::mock(\PFUser::class));
    }

    public function testFolderUpdateIsRejectedIfAnOtherItemHasTheSameTitle(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getTitle')->andReturn("my title");
        $item->shouldReceive('getParentId')->andReturn(9);

        $representation                    = new PUTMetadataFolderRepresentation();
        $representation->title             = 'title';
        $representation->description       = '';
        $representation->status            = new PUTRecursiveStatusRepresentation();
        $representation->status->value     = 'draft';
        $representation->status->recursion = null;

        $this->expectException(RestException::class);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(true);
        $this->updator->updateFolderMetadata($representation, $item, \Mockery::mock(\Project::class), \Mockery::mock(\PFUser::class));
    }

    public function testFolderStatusUpdateIsDoneForEveryFolderAndItems(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getTitle')->andReturn("my title");
        $item->shouldReceive('getParentId')->andReturn(9);
        $item->shouldReceive('getId')->andReturn(100);
        $item->shouldReceive('getStatus')->andReturn(100);

        $user = \Mockery::mock(\PFUser::class);

        $representation                    = new PUTMetadataFolderRepresentation();
        $representation->title             = 'a new title';
        $representation->description       = '';
        $representation->status            = new PUTRecursiveStatusRepresentation();
        $representation->status->value     = 'draft';
        $representation->status->recursion = "all_items";

        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(102);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(false);
        $this->item_factory->shouldReceive('update');
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(109);

        $this->event_processor->shouldReceive('raiseUpdateEvent')->once();

        $this->recursive_updator->shouldReceive('updateRecursiveMetadataOnFolderAndItems');
        $this->recursive_updator->shouldReceive('updateRecursiveMetadataOnFolder')->never();

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(\Mockery::mock(\Docman_Folder::class));
        $this->document_on_going_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturn(false);

        $this->representation_retriever->shouldReceive('checkAndBuildFolderMetadataToUpdate')->once()->andReturn([]);

        $this->mockStatusAndObsolescenceDateMetadataAreUsed();

        $this->updator->updateFolderMetadata($representation, $item, $project, $user);
    }

    public function testFolderStatusUpdateIsNotDoneForEveryFolderAndItemsIfStatusMetadataIsUnused(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getTitle')->andReturn("my title");
        $item->shouldReceive('getParentId')->andReturn(9);
        $item->shouldReceive('getId')->andReturn(100);
        $item->shouldReceive('getStatus')->andReturn(100);

        $user = \Mockery::mock(\PFUser::class);

        $representation                    = new PUTMetadataFolderRepresentation();
        $representation->title             = 'a new title';
        $representation->description       = '';
        $representation->status            = new PUTRecursiveStatusRepresentation();
        $representation->status->value     = 'none';
        $representation->status->recursion = "none";

        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(102);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(false);
        $this->item_factory->shouldReceive('update');
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(109);

        $this->event_processor->shouldReceive('raiseUpdateEvent')->never();

        $this->recursive_updator->shouldReceive('updateRecursiveMetadataOnFolderAndItems');
        $this->recursive_updator->shouldReceive('updateRecursiveMetadataOnFolder')->never();

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(\Mockery::mock(\Docman_Folder::class));
        $this->document_on_going_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturn(false);

        $this->representation_retriever->shouldReceive('checkAndBuildFolderMetadataToUpdate')->once()->andReturn([]);

        $this->mockStatusAndObsolescenceDateMetadataAreUnused();

        $this->updator->updateFolderMetadata($representation, $item, $project, $user);
    }

    public function testFolderUpdateIsDoneForOtherFolder(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getTitle')->andReturn("my title");
        $item->shouldReceive('getParentId')->andReturn(9);
        $item->shouldReceive('getId')->andReturn(100);
        $item->shouldReceive('getStatus')->andReturn(100);

        $user = \Mockery::mock(\PFUser::class);

        $representation                    = new PUTMetadataFolderRepresentation();
        $representation->title             = 'a new title';
        $representation->description       = '';
        $representation->status            = new PUTRecursiveStatusRepresentation();
        $representation->status->value     = 'draft';
        $representation->status->recursion = "folders";

        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(102);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(false);
        $this->item_factory->shouldReceive('update');
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(109);

        $this->event_processor->shouldReceive('raiseUpdateEvent')->once();

        $this->recursive_updator->shouldReceive('updateRecursiveMetadataOnFolderAndItems')->never();
        $this->recursive_updator->shouldReceive('updateRecursiveMetadataOnFolder');

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(\Mockery::mock(\Docman_Folder::class));
        $this->document_on_going_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturn(false);

        $this->representation_retriever->shouldReceive('checkAndBuildFolderMetadataToUpdate')->once()->andReturn([]);

        $this->mockStatusAndObsolescenceDateMetadataAreUsed();

        $this->updator->updateFolderMetadata($representation, $item, $project, $user);
    }

    public function testFolderUpdateCanBeDoneWithoutRecusrivity(): void
    {
        $item = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getTitle')->andReturn("my title");
        $item->shouldReceive('getParentId')->andReturn(9);
        $item->shouldReceive('getId')->andReturn(100);
        $item->shouldReceive('getStatus')->andReturn(100);

        $user = \Mockery::mock(\PFUser::class);

        $representation                    = new PUTMetadataFolderRepresentation();
        $representation->title             = 'a new title';
        $representation->description       = '';
        $representation->status            = new PUTRecursiveStatusRepresentation();
        $representation->status->value     = 'draft';
        $representation->status->recursion = null;

        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(102);

        $this->item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(false);
        $this->item_factory->shouldReceive('update');
        $project = \Mockery::mock(\Project::class);

        $this->event_processor->shouldReceive('raiseUpdateEvent')->once();

        $this->recursive_updator->shouldReceive('updateRecursiveMetadataOnFolderAndItems')->never();
        $this->recursive_updator->shouldReceive('updateRecursiveMetadataOnFolder')->never();

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(\Mockery::mock(\Docman_Folder::class));
        $this->document_on_going_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturn(false);

        $this->representation_retriever->shouldReceive('checkAndBuildFolderMetadataToUpdate')->once()->andReturn([]);

        $this->mockStatusAndObsolescenceDateMetadataAreUsed();

        $this->updator->updateFolderMetadata($representation, $item, $project, $user);
    }

    private function mockStatusAndObsolescenceDateMetadataAreUsed(): void
    {
        $this->docman_settings_bo->method('getMetadataUsage')->willReturnMap([
            ["status", "1"],
            ["obsolescence_date", "1"],
        ]);
    }

    private function mockStatusAndObsolescenceDateMetadataAreUnused(): void
    {
        $this->docman_settings_bo->method('getMetadataUsage')->willReturnMap([
            ["status", false],
            ["obsolescence_date", false],
        ]);
    }
}
