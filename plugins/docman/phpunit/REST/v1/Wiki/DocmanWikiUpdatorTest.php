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
use Docman_Wiki;
use EventManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\REST\v1\DocmanItemUpdator;
use Tuleap\Docman\REST\v1\Wiki\DocmanWikiPATCHRepresentation;
use Tuleap\Docman\REST\v1\Wiki\DocmanWikiVersionCreator;
use Tuleap\Docman\REST\v1\Wiki\WikiPropertiesPOSTPATCHRepresentation;

class DocmanWikiUpdatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Docman_VersionFactory|Mockery\MockInterface
     */
    private $version_factory;
    /**
     * @var Docman_ItemFactory|Mockery\MockInterface
     */
    private $docman_item_factory;
    /**
     * @var EventManager|Mockery\MockInterface
     */
    private $event_manager;
    /**
     * @var Mockery\MockInterface|DocmanItemUpdator
     */
    private $updator;
    /**
     * @var Mockery\MockInterface|DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var DocmanWikiVersionCreator
     */
    public $wiki_updator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->version_factory            = Mockery::mock(\Docman_VersionFactory::class);
        $this->docman_item_factory        = Mockery::mock(Docman_ItemFactory::class);
        $this->event_manager              = Mockery::mock(\EventManager::class);
        $this->updator                    = Mockery::mock(DocmanItemUpdator::class);
        $this->transaction_executor       = Mockery::mock(DBTransactionExecutor::class);

        $this->wiki_updator = new DocmanWikiVersionCreator(
            $this->version_factory,
            $this->docman_item_factory,
            $this->event_manager,
            $this->updator,
            $this->transaction_executor
        );
    }

    public function testItShouldStoreTheNewVersionWhenFileRepresentationIsCorrect(): void
    {
        $item = Mockery::mock(Docman_Wiki::class);
        $item->shouldReceive('getId')->andReturn(1);
        $user = Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturn(101);

        $date                        = new \DateTimeImmutable();
        $date                        = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date                        = $date->setTime(0, 0, 0);
        $obsolescence_date           = $date->modify('+1 day');
        $obsolescence_date_formatted = $obsolescence_date->format('Y-m-d');

        $representation                             = new DocmanWikiPATCHRepresentation();
        $representation->should_lock_file           = false;
        $representation->wiki_properties            = new WikiPropertiesPOSTPATCHRepresentation();
        $representation->wiki_properties->page_name = 'wiki name';
        $representation->status                     = 'rejected';
        $representation->obsolescence_date          = $obsolescence_date_formatted;

        $this->transaction_executor->shouldReceive('execute')->once();

        $this->wiki_updator->createWikiVersion($item, $user, $representation, 102, $date->getTimestamp(), 'title', '');
    }
}
