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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\Actions\OwnerRetriever;
use Tuleap\Docman\Metadata\MetadataEventProcessor;
use Tuleap\Docman\REST\v1\Files\PUTMetadataRepresentation;
use Tuleap\Docman\REST\v1\ItemRepresentation;

class MetadataUpdatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;
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

    public function setUp(): void
    {
        parent::setUp();

        $this->item_factory                = \Mockery::mock(\Docman_ItemFactory::class);
        $this->status_mapper               = \Mockery::mock(ItemStatusMapper::class);
        $this->obsolescence_date_retriever = \Mockery::mock(HardcodedMetadataObsolescenceDateRetriever::class);
        $this->user_manager                = \Mockery::mock(\UserManager::class);
        $this->owner_retriever             = \Mockery::mock(OwnerRetriever::class);
        $this->event_processor             = \Mockery::mock(MetadataEventProcessor::class);

        $this->updator = new MetadataUpdator(
            $this->item_factory,
            $this->status_mapper,
            $this->obsolescence_date_retriever,
            $this->user_manager,
            $this->owner_retriever,
            $this->event_processor
        );
    }

    public function testOwnerCanBeChanged(): void
    {
        $date = new \DateTimeImmutable();
        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $this->obsolescence_date_retriever->shouldReceive('getTimeStampOfDate')->andReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getOwnerId')->andReturn($old_user_id);
        $item->shouldReceive('getId')->andReturn(10);
        $item->shouldReceive('getStatus')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);

        $old_user = \Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getUserById')->andReturn($old_user);
        $old_user->shouldReceive('getName')->andReturn('old user name');

        $new_user = \Mockery::mock(\PFUser::class);
        $new_user->shouldReceive('getUnixName')->andReturn('new user name');
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

        $this->item_factory->shouldReceive('update')->once();

        $this->updator->updateDocumentMetadata($representation, $item, new \DateTimeImmutable(), $current_user);
    }

    public function testOwnerIsChangedToMyselfIfPreviousUserNotFound(): void
    {
        $date = new \DateTimeImmutable();
        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);
        $this->obsolescence_date_retriever->shouldReceive('getTimeStampOfDate')->andReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getOwnerId')->andReturn($old_user_id);
        $item->shouldReceive('getId')->andReturn(10);
        $item->shouldReceive('getStatus')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);

        $this->user_manager->shouldReceive('getUserById')->andReturn(null);

        $new_user = \Mockery::mock(\PFUser::class);
        $new_user->shouldReceive('getUnixName')->andReturn('new user name');
        $this->owner_retriever->shouldReceive('getUserFromRepresentationId')->andThrow($new_user);

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = "title";
        $representation->description       = "";
        $representation->owner_id          = 102;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_APPROVED;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $current_user = \Mockery::mock(\PFUser::class);
        $current_user->shouldReceive('getName')->andReturn('current user name');

        $this->event_processor->shouldReceive('raiseUpdateEvent')
                              ->withArgs([$item, $current_user, 'current user name', 'new user name', 'owner'])
                              ->once();

        $this->item_factory->shouldReceive('update')->once();

        $this->updator->updateDocumentMetadata($representation, $item, new \DateTimeImmutable(), $current_user);
    }

    public function testStatusIsUpdated(): void
    {
        $date = new \DateTimeImmutable();
        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_DRAFT);
        $this->status_mapper->shouldReceive('getItemStatusStringFormId')->andReturn(ItemStatusMapper::ITEM_STATUS_APPROVED);
        $this->obsolescence_date_retriever->shouldReceive('getTimeStampOfDate')->andReturn($date->getTimestamp());

        $old_user_id = 101;
        $item        = \Mockery::mock(\Docman_Item::class);
        $item->shouldReceive('getOwnerId')->andReturn($old_user_id);
        $item->shouldReceive('getId')->andReturn(10);
        $item->shouldReceive('getStatus')->andReturn(PLUGIN_DOCMAN_ITEM_STATUS_APPROVED);

        $old_user = \Mockery::mock(\PFUser::class);
        $old_user->shouldReceive('getUnixName')->andReturn('owner name');
        $this->owner_retriever->shouldReceive('getUserFromRepresentationId')->andReturn($old_user);
        $this->user_manager->shouldReceive('getUserById')->andReturn($old_user);
        $old_user->shouldReceive('getName')->andReturn('user name');

        $representation                    = new PUTMetadataRepresentation();
        $representation->title             = "title";
        $representation->description       = "";
        $representation->owner_id          = $old_user_id;
        $representation->status            = ItemStatusMapper::ITEM_STATUS_DRAFT;
        $representation->obsolescence_date = ItemRepresentation::OBSOLESCENCE_DATE_NONE;

        $current_user = \Mockery::mock(\PFUser::class);

        $this->event_processor->shouldReceive('raiseUpdateEvent')
                              ->withArgs([$item, $current_user, ItemStatusMapper::ITEM_STATUS_APPROVED, ItemStatusMapper::ITEM_STATUS_DRAFT, 'status'])
                              ->once();

        $this->item_factory->shouldReceive('update')->once();

        $this->updator->updateDocumentMetadata($representation, $item, new \DateTimeImmutable(), $current_user);
    }
}
