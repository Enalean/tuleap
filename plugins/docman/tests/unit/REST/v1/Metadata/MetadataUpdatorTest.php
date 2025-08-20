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

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Metadata;

use DateTimeImmutable;
use Docman_Folder;
use Docman_Item;
use Docman_ItemFactory;
use Docman_Metadata;
use Docman_SettingsBo;
use LogicException;
use Luracast\Restler\RestException;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Tuleap\Docman\Metadata\MetadataEventProcessor;
use Tuleap\Docman\Metadata\MetadataRecursiveUpdator;
use Tuleap\Docman\Metadata\MetadataValueUpdator;
use Tuleap\Docman\Metadata\Owner\OwnerRetriever;
use Tuleap\Docman\REST\v1\ItemRepresentation;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\REST\I18NRestException;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MetadataUpdatorTest extends TestCase
{
    private MetadataValueUpdator&MockObject $metadata_value_updator;
    private CustomMetadataRepresentationRetriever&MockObject $representation_retriever;
    private DocumentOngoingUploadRetriever&MockObject $document_on_going_retriever;
    private MetadataRecursiveUpdator&MockObject $recursive_updator;
    private MetadataEventProcessor&MockObject $event_processor;
    private OwnerRetriever&MockObject $owner_retriever;
    private Docman_ItemFactory&MockObject $item_factory;
    private ItemStatusMapper&MockObject $status_mapper;
    private HardcodedMetadataObsolescenceDateRetriever&MockObject $obsolescence_date_retriever;
    private MetadataUpdator $updator;
    private UserManager&MockObject $user_manager;
    private Docman_SettingsBo&MockObject $docman_settings_bo;

    protected function setUp(): void
    {
        $this->item_factory                = $this->createMock(Docman_ItemFactory::class);
        $this->status_mapper               = $this->createMock(ItemStatusMapper::class);
        $this->obsolescence_date_retriever = $this->createMock(HardcodedMetadataObsolescenceDateRetriever::class);
        $this->user_manager                = $this->createMock(UserManager::class);
        $this->owner_retriever             = $this->createMock(OwnerRetriever::class);
        $this->event_processor             = $this->createMock(MetadataEventProcessor::class);
        $this->recursive_updator           = $this->createMock(MetadataRecursiveUpdator::class);
        $this->document_on_going_retriever = $this->createMock(DocumentOngoingUploadRetriever::class);
        $this->representation_retriever    = $this->createMock(CustomMetadataRepresentationRetriever::class);
        $this->metadata_value_updator      = $this->createMock(MetadataValueUpdator::class);
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
        $date = new DateTimeImmutable();
        $this->status_mapper->method('getItemStatusIdFromItemStatusString')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $this->obsolescence_date_retriever->method('getTimeStampOfDate')->willReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = new Docman_Item([
            'user_id'   => $old_user_id,
            'item_id'   => 10,
            'status'    => PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            'title'     => 'my title',
            'parent_id' => 9,
        ]);

        $old_user = UserTestBuilder::aUser()->withId($old_user_id)->withUserName('old user name')->build();
        $this->user_manager->method('getUserById')->willReturn($old_user);

        $new_user = UserTestBuilder::aUser()->withUserName('new user name')->build();
        $this->owner_retriever->method('getUserFromRepresentationId')->willReturn($new_user);

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = 'title';
        $representation->description       = '';
        $representation->owner_id          = 102;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_APPROVED;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $current_user = UserTestBuilder::buildWithDefaults();

        $this->event_processor->expects($this->once())->method('raiseUpdateEvent')
            ->with($item, $current_user, 'old user name', 'new user name', 'owner');

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->item_factory->expects($this->once())->method('update');

        $this->representation_retriever->expects($this->once())->method('checkAndBuildMetadataToUpdate')->willReturn([]);

        $this->item_factory->method('getItemFromDb')->willReturn(new Docman_Folder());
        $this->document_on_going_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->mockStatusAndObsolescenceDateMetadataAreUsed();

        $this->updator->updateDocumentMetadata($representation, $item, $current_user);
    }

    public function testDocumentCustomMetadataCanBeChangedForRootFolder(): void
    {
        $date = new DateTimeImmutable();
        $this->status_mapper->method('getItemStatusIdFromItemStatusString')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $this->obsolescence_date_retriever->method('getTimeStampOfDate')->willReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = new Docman_Item([
            'user_id'   => $old_user_id,
            'item_id'   => 10,
            'status'    => PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            'title'     => 'my title',
            'parent_id' => 0,
        ]);

        $old_user = UserTestBuilder::aUser()->withId($old_user_id)->withUserName('user name')->build();
        $this->owner_retriever->method('getUserFromRepresentationId')->willReturn($old_user);
        $this->user_manager->method('getUserById')->willReturn($old_user);

        $representation                             = new PUTMetadataRepresentation();
        $representation->title                      = 'title';
        $representation->description                = '';
        $representation->owner_id                   = $old_user_id;
        $representation->status                     = ItemStatusMapper::ITEM_STATUS_APPROVED;
        $representation->obsolescence_date          = ItemRepresentation::OBSOLESCENCE_DATE_NONE;
        $custom_metadata_representation             = new POSTCustomMetadataRepresentation();
        $custom_metadata_representation->short_name = 'field_1';
        $custom_metadata_representation->value      = 'value';
        $representation->metadata                   = [$custom_metadata_representation];

        $current_user = UserTestBuilder::buildWithDefaults();

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->item_factory->expects($this->once())->method('update');

        $project_configured_metadata = new Docman_Metadata();

        $metadata_to_update_representation = MetadataToUpdate::buildMetadataRepresentation(
            $project_configured_metadata,
            $custom_metadata_representation->value,
            PUTRecursiveStatusRepresentation::RECURSION_ALL_ITEMS
        );
        $this->representation_retriever->expects($this->once())->method('checkAndBuildMetadataToUpdate')
            ->willReturn([$metadata_to_update_representation]);

        $this->item_factory->expects($this->never())->method('getItemFromDb');
        $this->document_on_going_retriever->expects($this->never())->method('isThereAlreadyAnUploadOngoing');
        $this->metadata_value_updator->expects($this->once())->method('updateMetadata')
            ->with($metadata_to_update_representation->getMetadata(), 10, $metadata_to_update_representation->getValue());

        $this->mockStatusAndObsolescenceDateMetadataAreUsed();

        $this->updator->updateDocumentMetadata($representation, $item, $current_user);
    }

    public function testDocumentCustomMetadataCanBeChanged(): void
    {
        $date = new DateTimeImmutable();
        $this->status_mapper->method('getItemStatusIdFromItemStatusString')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $this->obsolescence_date_retriever->method('getTimeStampOfDate')->willReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = new Docman_Item([
            'user_id'   => $old_user_id,
            'item_id'   => 10,
            'status'    => PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            'title'     => 'my title',
            'parent_id' => 9,
        ]);

        $old_user = UserTestBuilder::aUser()->withId($old_user_id)->withUserName('user name')->build();
        $this->owner_retriever->method('getUserFromRepresentationId')->willReturn($old_user);
        $this->user_manager->method('getUserById')->willReturn($old_user);

        $representation                             = new PUTMetadataRepresentation();
        $representation->title                      = 'title';
        $representation->description                = '';
        $representation->owner_id                   = $old_user_id;
        $representation->status                     = ItemStatusMapper::ITEM_STATUS_APPROVED;
        $representation->obsolescence_date          = ItemRepresentation::OBSOLESCENCE_DATE_NONE;
        $custom_metadata_representation             = new POSTCustomMetadataRepresentation();
        $custom_metadata_representation->short_name = 'field_1';
        $custom_metadata_representation->value      = 'value';
        $representation->metadata                   = [$custom_metadata_representation];

        $current_user = UserTestBuilder::buildWithDefaults();

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->item_factory->expects($this->once())->method('update');

        $project_configured_metadata = new Docman_Metadata();

        $metadata_to_update_representation = MetadataToUpdate::buildMetadataRepresentation(
            $project_configured_metadata,
            $custom_metadata_representation->value,
            PUTRecursiveStatusRepresentation::RECURSION_ALL_ITEMS
        );
        $this->representation_retriever->expects($this->once())->method('checkAndBuildMetadataToUpdate')
            ->willReturn([$metadata_to_update_representation]);

        $this->item_factory->method('getItemFromDb')->willReturn(new Docman_Folder());
        $this->document_on_going_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->metadata_value_updator->expects($this->once())->method('updateMetadata')
            ->with($metadata_to_update_representation->getMetadata(), 10, $metadata_to_update_representation->getValue());

        $this->mockStatusAndObsolescenceDateMetadataAreUsed();

        $this->updator->updateDocumentMetadata($representation, $item, $current_user);
    }

    public function testDocumentUpdateIsRejectedIfNewOwnerCanNotBeFound(): void
    {
        $date = new DateTimeImmutable();
        $this->status_mapper->method('getItemStatusIdFromItemStatusString')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $this->obsolescence_date_retriever->method('getTimeStampOfDate')->willReturn($date->getTimestamp());

        $item = new Docman_Item([
            'user_id'   => 101,
            'title'     => 'my title',
            'parent_id' => 9,
        ]);

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = 'title';
        $representation->description       = '';
        $representation->owner_id          = 0;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_APPROVED;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->owner_retriever->method('getUserFromRepresentationId')->willReturn(null);

        $this->representation_retriever->expects($this->never())->method('checkAndBuildMetadataToUpdate');

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);

        $this->item_factory->method('getItemFromDb')->willReturn(new Docman_Folder());
        $this->document_on_going_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->updator->updateDocumentMetadata($representation, $item, UserTestBuilder::buildWithDefaults());
    }

    public function testDocumentUpdateIsInterruptedWhenThePreviousOwnerCanNotBeFoundAsTheWholeDocumentIsLikelyToBeBroken(): void
    {
        $date = new DateTimeImmutable();
        $this->status_mapper->method('getItemStatusIdFromItemStatusString')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $this->obsolescence_date_retriever->method('getTimeStampOfDate')->willReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = new Docman_Item([
            'user_id'   => $old_user_id,
            'item_id'   => 10,
            'status'    => PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            'title'     => 'my title',
            'parent_id' => 9,
        ]);

        $this->user_manager->method('getUserById')->willReturn(null);

        $new_user = UserTestBuilder::aUser()->withUserName('new user name')->build();
        $this->owner_retriever->method('getUserFromRepresentationId')->willReturn($new_user);

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = 'title';
        $representation->description       = '';
        $representation->owner_id          = 102;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_APPROVED;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $this->representation_retriever->expects($this->once())->method('checkAndBuildMetadataToUpdate')->willReturn([]);

        $this->expectException(RuntimeException::class);

        $this->item_factory->method('getItemFromDb')->willReturn(new Docman_Folder());
        $this->document_on_going_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->updator->updateDocumentMetadata($representation, $item, UserTestBuilder::buildWithDefaults());
    }

    public function testDocumentStatusIsUpdated(): void
    {
        $date = new DateTimeImmutable();
        $this->status_mapper->method('getItemStatusIdFromItemStatusString')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_DRAFT);
        $this->obsolescence_date_retriever->method('getTimeStampOfDate')->willReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = new Docman_Item([
            'user_id'   => $old_user_id,
            'item_id'   => 10,
            'status'    => PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            'title'     => 'my title',
            'parent_id' => 9,
        ]);

        $old_user = UserTestBuilder::aUser()->withId($old_user_id)->withUserName('user name')->build();
        $this->owner_retriever->method('getUserFromRepresentationId')->willReturn($old_user);
        $this->user_manager->method('getUserById')->willReturn($old_user);

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = 'title';
        $representation->description       = '';
        $representation->owner_id          = $old_user_id;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_DRAFT;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $current_user = UserTestBuilder::buildWithDefaults();

        $this->event_processor->expects($this->once())->method('raiseUpdateEvent')
            ->with($item, $current_user, PLUGIN_DOCMAN_ITEM_STATUS_APPROVED, PLUGIN_DOCMAN_ITEM_STATUS_DRAFT, 'status');

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->item_factory->expects($this->once())->method('update');

        $this->representation_retriever->expects($this->once())->method('checkAndBuildMetadataToUpdate')->willReturn([]);

        $this->item_factory->method('getItemFromDb')->willReturn(new Docman_Folder());
        $this->document_on_going_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->mockStatusAndObsolescenceDateMetadataAreUsed();

        $this->updator->updateDocumentMetadata($representation, $item, $current_user);
    }

    public function testDocumentStatusIsNotUpdatedIfStatusMetadataNotUsed(): void
    {
        $date = new DateTimeImmutable();
        $this->status_mapper->method('getItemStatusIdFromItemStatusString')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);
        $this->obsolescence_date_retriever->method('getTimeStampOfDate')->willReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = new Docman_Item([
            'user_id'   => $old_user_id,
            'item_id'   => 10,
            'status'    => PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            'title'     => 'my title',
            'parent_id' => 9,
        ]);

        $old_user = UserTestBuilder::aUser()->withId($old_user_id)->withUserName('user name')->build();
        $this->owner_retriever->method('getUserFromRepresentationId')->willReturn($old_user);
        $this->user_manager->method('getUserById')->willReturn($old_user);

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = 'title';
        $representation->description       = '';
        $representation->owner_id          = $old_user_id;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_NONE;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $current_user = UserTestBuilder::buildWithDefaults();

        $this->event_processor->expects($this->never())->method('raiseUpdateEvent')
            ->with($item, $current_user, self::anything(), self::anything(), 'status');

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->item_factory->expects($this->once())->method('update');

        $this->representation_retriever->expects($this->once())->method('checkAndBuildMetadataToUpdate')->willReturn([]);

        $this->item_factory->method('getItemFromDb')->willReturn(new Docman_Folder());
        $this->document_on_going_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->mockStatusAndObsolescenceDateMetadataAreUnused();

        $this->updator->updateDocumentMetadata($representation, $item, $current_user);
    }

    public function testDocumentObsolescenceDateIsNotUpdatedIfStatusMetadataNotUsed(): void
    {
        $date = new DateTimeImmutable();
        $this->status_mapper->method('getItemStatusIdFromItemStatusString')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_NONE);
        $this->obsolescence_date_retriever->method('getTimeStampOfDate')->willReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = new Docman_Item([
            'user_id'   => $old_user_id,
            'item_id'   => 10,
            'status'    => PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            'title'     => 'my title',
            'parent_id' => 9,
        ]);

        $old_user = UserTestBuilder::aUser()->withId($old_user_id)->withUserName('user name')->build();
        $this->owner_retriever->method('getUserFromRepresentationId')->willReturn($old_user);
        $this->user_manager->method('getUserById')->willReturn($old_user);

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = 'title';
        $representation->description       = '';
        $representation->owner_id          = $old_user_id;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_NONE;
        $representation->obsolescence_date = $date->format('Y-m-d');

        $current_user = UserTestBuilder::buildWithDefaults();

        $this->event_processor->expects($this->never())->method('raiseUpdateEvent')
            ->with($item, $current_user, self::anything(), self::anything(), 'status');

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->item_factory->expects($this->once())->method('update')
            ->with([
                'id'          => 10,
                'title'       => 'title',
                'description' => '',
                'user_id'     => $old_user_id,
            ]);

        $this->representation_retriever->expects($this->once())->method('checkAndBuildMetadataToUpdate')->willReturn([]);

        $this->item_factory->method('getItemFromDb')->willReturn(new Docman_Folder());
        $this->document_on_going_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->mockStatusAndObsolescenceDateMetadataAreUnused();

        $this->updator->updateDocumentMetadata($representation, $item, $current_user);
    }

    public function testDocumentUpdateIsInterruptedWhenAnOtherItemHasTheSameTitle(): void
    {
        $date = new DateTimeImmutable();
        $this->status_mapper->method('getItemStatusIdFromItemStatusString')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $this->obsolescence_date_retriever->method('getTimeStampOfDate')->willReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = new Docman_Item([
            'user_id'   => $old_user_id,
            'item_id'   => 10,
            'status'    => PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            'title'     => 'my title',
            'parent_id' => 9,
        ]);

        $user = UserTestBuilder::aUser()->withId($old_user_id)->withUserName('new user name')->build();
        $this->user_manager->method('getUserById')->willReturn($user);
        $this->owner_retriever->method('getUserFromRepresentationId')->willReturn($user);

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = 'title';
        $representation->description       = '';
        $representation->owner_id          = 102;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_APPROVED;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $this->representation_retriever->expects($this->never())->method('checkAndBuildMetadataToUpdate');

        $this->expectException(RestException::class);

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(true);
        $this->updator->updateDocumentMetadata($representation, $item, UserTestBuilder::buildWithDefaults());
    }

    public function testDocumentUpdateIsInterruptedWhenThereIsAnOnGoingUploadWIthTheSameTitle(): void
    {
        $date = new DateTimeImmutable();
        $this->status_mapper->method('getItemStatusIdFromItemStatusString')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $this->obsolescence_date_retriever->method('getTimeStampOfDate')->willReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = new Docman_Item([
            'user_id'   => $old_user_id,
            'item_id'   => 10,
            'status'    => PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            'title'     => 'my title',
            'parent_id' => 9,
        ]);

        $user = UserTestBuilder::aUser()->withId($old_user_id)->withUserName('new user name')->build();
        $this->user_manager->method('getUserById')->willReturn($user);
        $this->owner_retriever->method('getUserFromRepresentationId')->willReturn($user);

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = 'title';
        $representation->description       = '';
        $representation->owner_id          = 102;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_APPROVED;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $this->representation_retriever->expects($this->never())->method('checkAndBuildMetadataToUpdate');

        $this->expectException(I18NRestException::class);

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->item_factory->method('getItemFromDb')->willReturn(new Docman_Folder());
        $this->document_on_going_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(true);

        $this->updator->updateDocumentMetadata($representation, $item, UserTestBuilder::buildWithDefaults());
    }

    public function testDocumentUpdateIsInterruptedWhenParentItemIsNotFound(): void
    {
        $date = new DateTimeImmutable();
        $this->status_mapper->method('getItemStatusIdFromItemStatusString')->willReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $this->obsolescence_date_retriever->method('getTimeStampOfDate')->willReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = new Docman_Item([
            'user_id'   => $old_user_id,
            'item_id'   => 10,
            'status'    => PLUGIN_DOCMAN_ITEM_STATUS_APPROVED,
            'title'     => 'my title',
            'parent_id' => 9,
        ]);

        $user = UserTestBuilder::aUser()->withId($old_user_id)->withUserName('new user name')->build();
        $this->user_manager->method('getUserById')->willReturn($user);
        $this->owner_retriever->method('getUserFromRepresentationId')->willReturn($user);

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = 'title';
        $representation->description       = '';
        $representation->owner_id          = 102;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_APPROVED;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $this->representation_retriever->expects($this->never())->method('checkAndBuildMetadataToUpdate');

        $this->expectException(LogicException::class);

        $this->item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $this->item_factory->method('getItemFromDb')->willReturn(null);

        $this->updator->updateDocumentMetadata($representation, $item, UserTestBuilder::buildWithDefaults());
    }

    public function testFolderUpdateIsRejectedIfAnOtherItemHasTheSameTitle(): void
    {
        $item = new Docman_Item([
            'title'     => 'my title',
            'parent_id' => 9,
        ]);

        $representation                    = new PUTMetadataFolderRepresentation();
        $representation->title             = 'title';
        $representation->description       = '';
        $representation->status            = new PUTRecursiveStatusRepresentation();
        $representation->status->value     = 'draft';
        $representation->status->recursion = null;

        $this->expectException(RestException::class);

        $this->item_factory->method('doesTitleCorrespondToExistingFolder')->willReturn(true);
        $this->updator->updateFolderMetadata($representation, $item, ProjectTestBuilder::aProject()->build(), UserTestBuilder::buildWithDefaults());
    }

    public function testFolderStatusUpdateIsDoneForEveryFolderAndItems(): void
    {
        $item = new Docman_Item([
            'item_id'   => 100,
            'status'    => 100,
            'title'     => 'my title',
            'parent_id' => 9,
        ]);

        $user = UserTestBuilder::buildWithDefaults();

        $representation                    = new PUTMetadataFolderRepresentation();
        $representation->title             = 'a new title';
        $representation->description       = '';
        $representation->status            = new PUTRecursiveStatusRepresentation();
        $representation->status->value     = 'draft';
        $representation->status->recursion = 'all_items';

        $this->status_mapper->method('getItemStatusIdFromItemStatusString')->willReturn(102);

        $this->item_factory->method('doesTitleCorrespondToExistingFolder')->willReturn(false);
        $this->item_factory->method('update');
        $project = ProjectTestBuilder::aProject()->withId(109)->build();

        $this->event_processor->expects($this->once())->method('raiseUpdateEvent');

        $this->recursive_updator->method('updateRecursiveMetadataOnFolderAndItems');
        $this->recursive_updator->expects($this->never())->method('updateRecursiveMetadataOnFolder');

        $this->item_factory->method('getItemFromDb')->willReturn(new Docman_Folder());
        $this->document_on_going_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->representation_retriever->expects($this->once())->method('checkAndBuildFolderMetadataToUpdate')->willReturn([]);

        $this->mockStatusAndObsolescenceDateMetadataAreUsed();

        $this->updator->updateFolderMetadata($representation, $item, $project, $user);
    }

    public function testFolderStatusUpdateIsNotDoneForEveryFolderAndItemsIfStatusMetadataIsUnused(): void
    {
        $item = new Docman_Item([
            'item_id'   => 100,
            'status'    => 100,
            'title'     => 'my title',
            'parent_id' => 9,
        ]);

        $user = UserTestBuilder::buildWithDefaults();

        $representation                    = new PUTMetadataFolderRepresentation();
        $representation->title             = 'a new title';
        $representation->description       = '';
        $representation->status            = new PUTRecursiveStatusRepresentation();
        $representation->status->value     = 'none';
        $representation->status->recursion = 'none';

        $this->status_mapper->method('getItemStatusIdFromItemStatusString')->willReturn(102);

        $this->item_factory->method('doesTitleCorrespondToExistingFolder')->willReturn(false);
        $this->item_factory->method('update');
        $project = ProjectTestBuilder::aProject()->withId(109)->build();

        $this->event_processor->expects($this->never())->method('raiseUpdateEvent');

        $this->recursive_updator->method('updateRecursiveMetadataOnFolderAndItems');
        $this->recursive_updator->expects($this->never())->method('updateRecursiveMetadataOnFolder');

        $this->item_factory->method('getItemFromDb')->willReturn(new Docman_Folder());
        $this->document_on_going_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->representation_retriever->expects($this->once())->method('checkAndBuildFolderMetadataToUpdate')->willReturn([]);

        $this->mockStatusAndObsolescenceDateMetadataAreUnused();

        $this->updator->updateFolderMetadata($representation, $item, $project, $user);
    }

    public function testFolderUpdateIsDoneForOtherFolder(): void
    {
        $item = new Docman_Item([
            'item_id'   => 100,
            'status'    => 100,
            'title'     => 'my title',
            'parent_id' => 9,
        ]);

        $user = UserTestBuilder::buildWithDefaults();

        $representation                    = new PUTMetadataFolderRepresentation();
        $representation->title             = 'a new title';
        $representation->description       = '';
        $representation->status            = new PUTRecursiveStatusRepresentation();
        $representation->status->value     = 'draft';
        $representation->status->recursion = 'folders';

        $this->status_mapper->method('getItemStatusIdFromItemStatusString')->willReturn(102);

        $this->item_factory->method('doesTitleCorrespondToExistingFolder')->willReturn(false);
        $this->item_factory->method('update');
        $project = ProjectTestBuilder::aProject()->withId(109)->build();

        $this->event_processor->expects($this->once())->method('raiseUpdateEvent');

        $this->recursive_updator->expects($this->never())->method('updateRecursiveMetadataOnFolderAndItems');
        $this->recursive_updator->method('updateRecursiveMetadataOnFolder');

        $this->item_factory->method('getItemFromDb')->willReturn(new Docman_Folder());
        $this->document_on_going_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->representation_retriever->expects($this->once())->method('checkAndBuildFolderMetadataToUpdate')->willReturn([]);

        $this->mockStatusAndObsolescenceDateMetadataAreUsed();

        $this->updator->updateFolderMetadata($representation, $item, $project, $user);
    }

    public function testFolderUpdateCanBeDoneWithoutRecusrivity(): void
    {
        $item = new Docman_Item([
            'item_id'   => 100,
            'status'    => 100,
            'title'     => 'my title',
            'parent_id' => 9,
        ]);

        $user = UserTestBuilder::buildWithDefaults();

        $representation                    = new PUTMetadataFolderRepresentation();
        $representation->title             = 'a new title';
        $representation->description       = '';
        $representation->status            = new PUTRecursiveStatusRepresentation();
        $representation->status->value     = 'draft';
        $representation->status->recursion = null;

        $this->status_mapper->method('getItemStatusIdFromItemStatusString')->willReturn(102);

        $this->item_factory->method('doesTitleCorrespondToExistingFolder')->willReturn(false);
        $this->item_factory->method('update');
        $project = ProjectTestBuilder::aProject()->build();

        $this->event_processor->expects($this->once())->method('raiseUpdateEvent');

        $this->recursive_updator->expects($this->never())->method('updateRecursiveMetadataOnFolderAndItems');
        $this->recursive_updator->expects($this->never())->method('updateRecursiveMetadataOnFolder');

        $this->item_factory->method('getItemFromDb')->willReturn(new Docman_Folder());
        $this->document_on_going_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $this->representation_retriever->expects($this->once())->method('checkAndBuildFolderMetadataToUpdate')->willReturn([]);

        $this->mockStatusAndObsolescenceDateMetadataAreUsed();

        $this->updator->updateFolderMetadata($representation, $item, $project, $user);
    }

    private function mockStatusAndObsolescenceDateMetadataAreUsed(): void
    {
        $this->docman_settings_bo->method('getMetadataUsage')->willReturnMap([
            ['status', '1'],
            ['obsolescence_date', '1'],
        ]);
    }

    private function mockStatusAndObsolescenceDateMetadataAreUnused(): void
    {
        $this->docman_settings_bo->method('getMetadataUsage')->willReturnMap([
            ['status', false],
            ['obsolescence_date', false],
        ]);
    }
}
