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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace Tuleap\Docman\REST\v1\Files;

use DateTimeZone;
use Docman_ItemFactory;
use Docman_Link;
use Docman_VersionFactory;
use EventManager;
use Luracast\Restler\RestException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\REST\v1\DocmanItemUpdator;
use Tuleap\Docman\REST\v1\ExceptionItemIsLockedByAnotherUser;
use Tuleap\Docman\REST\v1\Links\DocmanLinkPATCHRepresentation;
use Tuleap\Docman\REST\v1\Links\DocmanLinksValidityChecker;
use Tuleap\Docman\REST\v1\Links\DocmanLinkUpdator;
use Tuleap\Docman\REST\v1\Links\LinkPropertiesRepresentation;
use Tuleap\Docman\REST\v1\Metadata\HardcodedMetadataObsolescenceDateRetriever;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;

class DocmanLinkUpdatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;


    /**
     * @var Docman_VersionFactory|Mockery\MockInterface
     */
    private $version_factory;
    /**
     * @var Mockery\MockInterface|DocmanItemUpdator
     */
    private $updator;
    /**
     * @var Docman_ItemFactory|Mockery\MockInterface
     */
    private $item_factory;
    /**
     * @var EventManager|Mockery\MockInterface
     */
    private $event_manager;
    /**
     * @var DocmanLinksValidityChecker
     */
    private $links_validity_checker;
    /**
     * @var \Docman_LinkVersionFactory|Mockery\MockInterface
     */
    private $docman_link_version_factory;
    /**
     * @var Mockery\MockInterface|DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var Mockery\MockInterface|ItemStatusMapper
     */
    private $status_mapper;
    /**
     * @var Mockery\MockInterface|HardcodedMetadataObsolescenceDateRetriever
     */
    private $date_retriever;
    /**
     * @var \Docman_PermissionsManager|Mockery\MockInterface
     */
    private $docman_permissions_manager;
    /**
     * @var DocmanLinkUpdator
     */
    private $link_updator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->version_factory             = Mockery::mock(Docman_VersionFactory::class);
        $this->updator                     = Mockery::mock(DocmanItemUpdator::class);
        $this->item_factory                = Mockery::mock(Docman_ItemFactory::class);
        $this->event_manager               = Mockery::mock(EventManager::class);
        $this->links_validity_checker      = new DocmanLinksValidityChecker();
        $this->docman_link_version_factory = Mockery::mock(\Docman_LinkVersionFactory::class);
        $this->transaction_executor        = Mockery::mock(DBTransactionExecutor::class);
        $this->status_mapper               = Mockery::mock(ItemStatusMapper::class);
        $this->date_retriever              = Mockery::mock(HardcodedMetadataObsolescenceDateRetriever::class);
        $this->docman_permissions_manager  = Mockery::mock(\Docman_PermissionsManager::class);

        $this->link_updator = new DocmanLinkUpdator(
            $this->version_factory,
            $this->updator,
            $this->item_factory,
            $this->event_manager,
            $this->links_validity_checker,
            $this->docman_link_version_factory,
            $this->transaction_executor,
            $this->status_mapper,
            $this->date_retriever,
            $this->docman_permissions_manager
        );
    }

    public function testItShouldStoreTheNewVersionWhenLinkRepresentationIsCorrect(): void
    {
        $item = Mockery::mock(Docman_Link::class);
        $item->shouldReceive('getId')->andReturn(1);
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);

        $this->docman_permissions_manager->shouldReceive('_itemIsLockedForUser')->andReturn(false);

        $date                        = new \DateTimeImmutable();
        $date                        = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date                        = $date->setTime(0, 0, 0);
        $obsolescence_date           = $date->modify('+1 day');
        $obsolescence_date_formatted = $obsolescence_date->format('Y-m-d');

        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->with('rejected')->andReturn(103);

        $this->date_retriever->shouldReceive('getTimeStampOfDate')->withArgs(
            [$obsolescence_date_formatted, $date]
        )->andReturn($obsolescence_date->getTimestamp());

        $this->docman_link_version_factory->shouldReceive('getLatestVersion')->once();
        $this->version_factory->shouldReceive('getCurrentVersionForItem')->once();
        $this->event_manager->shouldReceive('processEvent')->once();

        $representation                            = new DocmanLinkPATCHRepresentation();
        $representation->change_log                = 'changelog';
        $representation->version_title             = 'version title';
        $representation->should_lock_file          = false;
        $representation->link_properties           = new LinkPropertiesRepresentation();
        $representation->link_properties->link_url = 'https://example.com';
        $representation->approval_table_action     = 'copy';
        $representation->status                    = 'rejected';
        $representation->obsolescence_date         = $obsolescence_date_formatted;

        $this->updator->shouldReceive('updateCommonData')->once();
        $this->transaction_executor->shouldReceive('execute')->once();

        $this->link_updator->updateLink($item, $user, $representation, $date);
    }

    public function testItThrowAnExceptionWhenLinkURlIsIncorrect(): void
    {
        $item = Mockery::mock(Docman_Link::class);
        $item->shouldReceive('getId')->andReturn(1);
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);

        $this->docman_permissions_manager->shouldReceive('_itemIsLockedForUser')->andReturn(false);

        $date                        = new \DateTimeImmutable();
        $date                        = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date                        = $date->setTime(0, 0, 0);
        $obsolescence_date           = $date->modify('+1 day');
        $obsolescence_date_formatted = $obsolescence_date->format('Y-m-d');

        $representation                            = new DocmanLinkPATCHRepresentation();
        $representation->change_log                = 'changelog';
        $representation->version_title             = 'version title';
        $representation->should_lock_file          = false;
        $representation->link_properties           = new LinkPropertiesRepresentation();
        $representation->link_properties->link_url = 'example.com';
        $representation->approval_table_action     = 'copy';
        $representation->status                    = 'rejected';
        $representation->obsolescence_date         = $obsolescence_date_formatted;

        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->never();
        $this->transaction_executor->shouldReceive('execute')->never();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->link_updator->updateLink($item, $user, $representation, $date);
    }

    public function testItThrowsAnExceptionWhenItemIsLocked(): void
    {
        $item = Mockery::mock(Docman_Link::class);
        $item->shouldReceive('getId')->andReturn(1);
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);

        $this->docman_permissions_manager->shouldReceive('_itemIsLockedForUser')->andReturn(true);

        $date                        = new \DateTimeImmutable();
        $date                        = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date                        = $date->setTime(0, 0, 0);
        $obsolescence_date           = $date->modify('+1 day');
        $obsolescence_date_formatted = $obsolescence_date->format('Y-m-d');

        $representation                            = new DocmanLinkPATCHRepresentation();
        $representation->change_log                = 'changelog';
        $representation->version_title             = 'version title';
        $representation->should_lock_file          = false;
        $representation->link_properties           = new LinkPropertiesRepresentation();
        $representation->link_properties->link_url = 'https://example.com';
        $representation->approval_table_action     = 'copy';
        $representation->status                    = 'rejected';
        $representation->obsolescence_date         = $obsolescence_date_formatted;

        $this->status_mapper->shouldReceive('getItemStatusIdFromItemStatusString')->never();
        $this->transaction_executor->shouldReceive('execute')->never();

        $this->expectException(ExceptionItemIsLockedByAnotherUser::class);

        $this->link_updator->updateLink($item, $user, $representation, $date);
    }
}
