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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\REST\v1\DocmanItemUpdator;
use Tuleap\Docman\REST\v1\Links\DocmanLinkPATCHRepresentation;
use Tuleap\Docman\REST\v1\Links\DocmanLinkVersionCreator;
use Tuleap\Docman\REST\v1\Links\LinkPropertiesPOSTPATCHRepresentation;
use Tuleap\Docman\REST\v1\Links\LinkPropertiesRepresentation;
use Tuleap\Docman\REST\v1\PostUpdateEventAdder;
use Tuleap\Docman\Version\LinkVersionDataUpdator;

class DocmanLinkVersionCreatorTest extends TestCase
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
     * @var \Docman_LinkVersionFactory|Mockery\MockInterface
     */
    private $docman_link_version_factory;
    /**
     * @var Mockery\MockInterface|DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var DocmanLinkVersionCreator
     */
    private $version_creator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PostUpdateEventAdder
     */
    private $post_update_event_adder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LinkVersionDataUpdator
     */
    private $link_data_updator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->version_factory             = Mockery::mock(Docman_VersionFactory::class);
        $this->updator                     = Mockery::mock(DocmanItemUpdator::class);
        $this->item_factory                = Mockery::mock(Docman_ItemFactory::class);
        $this->event_manager               = Mockery::mock(EventManager::class);
        $this->docman_link_version_factory = Mockery::mock(\Docman_LinkVersionFactory::class);
        $this->transaction_executor        = Mockery::mock(DBTransactionExecutor::class);
        $this->post_update_event_adder     = Mockery::mock(PostUpdateEventAdder::class);
        $this->link_data_updator           = Mockery::mock(LinkVersionDataUpdator::class);

        $this->version_creator = new DocmanLinkVersionCreator(
            $this->version_factory,
            $this->updator,
            $this->item_factory,
            $this->event_manager,
            $this->docman_link_version_factory,
            $this->transaction_executor,
            $this->post_update_event_adder,
            $this->link_data_updator
        );
    }

    public function testItShouldStoreTheNewVersionWhenLinkRepresentationIsCorrect(): void
    {
        $item = Mockery::mock(Docman_Link::class);
        $item->shouldReceive('getId')->andReturn(1);
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);

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

        $this->transaction_executor->shouldReceive('execute')->once();

        $this->version_creator->createLinkVersion(
            $item,
            $user,
            $representation,
            $date,
            103,
            $obsolescence_date->getTimestamp(),
            "title",
            "description"
        );
    }

    public function testItShouldStoreANewLinkVersionDocumentFromAnEmptyDocument(): void
    {
        $item = Mockery::mock(\Docman_Empty::class);
        $item->shouldReceive('getId')->andReturn(1);
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);

        $representation           = new LinkPropertiesPOSTPATCHRepresentation();
        $representation->link_url = "https://example.test";

        $this->transaction_executor->shouldReceive('execute')->once();

        $this->version_creator->createLinkVersionFromEmpty($item, $user, $representation, new \DateTimeImmutable());
    }
}
