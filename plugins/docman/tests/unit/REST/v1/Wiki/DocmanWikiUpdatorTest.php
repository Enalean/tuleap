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

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Wiki;

use DateTimeImmutable;
use DateTimeZone;
use Docman_ItemFactory;
use Docman_VersionFactory;
use Docman_Wiki;
use EventManager;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\REST\v1\DocmanItemUpdator;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanWikiUpdatorTest extends TestCase
{
    private DocmanWikiVersionCreator $wiki_updator;
    private Docman_VersionFactory&MockObject $version_factory;
    private Docman_ItemFactory&MockObject $docman_item_factory;
    private EventManager&MockObject $event_manager;
    private DocmanItemUpdator&MockObject $updator;

    protected function setUp(): void
    {
        $this->version_factory     = $this->createMock(Docman_VersionFactory::class);
        $this->docman_item_factory = $this->createMock(Docman_ItemFactory::class);
        $this->event_manager       = $this->createMock(EventManager::class);
        $this->updator             = $this->createMock(DocmanItemUpdator::class);

        $this->wiki_updator = new DocmanWikiVersionCreator(
            $this->version_factory,
            $this->docman_item_factory,
            $this->event_manager,
            $this->updator,
            new DBTransactionExecutorPassthrough(),
        );
    }

    public function testItShouldStoreTheNewVersionWhenFileRepresentationIsCorrect(): void
    {
        self::expectNotToPerformAssertions();

        $item = $this->createMock(Docman_Wiki::class);
        $item->method('getId')->willReturn(1);
        $item->method('getPagename')->willReturn('');
        $item->method('getGroupId')->willReturn(101);
        $user = UserTestBuilder::buildWithId(101);

        $this->version_factory->method('getNextVersionNumber')->willReturn(1);
        $this->version_factory->method('getCurrentVersionForItem');
        $this->docman_item_factory->method('update');
        $this->docman_item_factory->method('getWikiPageReferencers')->willReturn([]);
        $this->event_manager->method('processEvent');
        $this->updator->method('updateCommonDataWithoutApprovalTable');

        $date                        = new DateTimeImmutable();
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

        $this->wiki_updator->createWikiVersion($item, $user, $representation, 102, $date->getTimestamp(), 'title', '');
    }
}
