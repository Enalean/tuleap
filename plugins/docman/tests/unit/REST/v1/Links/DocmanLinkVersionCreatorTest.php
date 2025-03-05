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

namespace Tuleap\Docman\REST\v1\Links;

use DateTimeImmutable;
use DateTimeZone;
use Docman_Empty;
use Docman_ItemFactory;
use Docman_Link;
use Docman_LinkVersion;
use Docman_LinkVersionFactory;
use Docman_VersionFactory;
use EventManager;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Docman\REST\v1\DocmanItemUpdator;
use Tuleap\Docman\REST\v1\PostUpdateEventAdder;
use Tuleap\Docman\Version\LinkVersionDataUpdator;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanLinkVersionCreatorTest extends TestCase
{
    private DBTransactionExecutor&MockObject $transaction_executor;
    private DocmanLinkVersionCreator $version_creator;

    protected function setUp(): void
    {
        $this->transaction_executor = $this->createMock(DBTransactionExecutor::class);
        $this->version_creator      = new DocmanLinkVersionCreator(
            $this->createMock(Docman_VersionFactory::class),
            $this->createMock(DocmanItemUpdator::class),
            $this->createMock(Docman_ItemFactory::class),
            $this->createMock(EventManager::class),
            $this->createMock(Docman_LinkVersionFactory::class),
            $this->transaction_executor,
            $this->createMock(PostUpdateEventAdder::class),
            $this->createMock(LinkVersionDataUpdator::class)
        );
    }

    public function testItShouldStoreTheNewVersionWhenLinkRepresentationIsCorrect(): void
    {
        $item = $this->createMock(Docman_Link::class);
        $item->method('getId')->willReturn(1);
        $user = UserTestBuilder::buildWithId(101);

        $date                        = new DateTimeImmutable();
        $date                        = $date->setTimezone(new DateTimeZone('GMT+1'));
        $date                        = $date->setTime(0, 0, 0);
        $obsolescence_date           = $date->modify('+1 day');
        $obsolescence_date_formatted = $obsolescence_date->format('Y-m-d');

        $representation                   = new DocmanLinkPATCHRepresentation();
        $representation->change_log       = 'changelog';
        $representation->version_title    = 'version title';
        $representation->should_lock_file = false;
        $docman_link                      = $this->createMock(Docman_LinkVersion::class);
        $docman_link->method('getLink')->willReturn('https://example.com');
        $representation->link_properties       = LinkPropertiesRepresentation::build($docman_link);
        $representation->approval_table_action = 'copy';
        $representation->status                = 'rejected';
        $representation->obsolescence_date     = $obsolescence_date_formatted;

        $this->transaction_executor->expects(self::once())->method('execute');

        $this->version_creator->createLinkVersion(
            $item,
            $user,
            $representation,
            $date,
            103,
            $obsolescence_date->getTimestamp(),
            'title',
            'description'
        );
    }

    public function testItShouldStoreANewLinkVersionDocumentFromAnEmptyDocument(): void
    {
        $item = new Docman_Empty(['item_id' => 1]);
        $user = UserTestBuilder::buildWithId(101);

        $representation           = new LinkPropertiesPOSTPATCHRepresentation();
        $representation->link_url = 'https://example.test';

        $this->transaction_executor->expects(self::once())->method('execute');

        $this->version_creator->createLinkVersionFromEmpty($item, $user, $representation, new DateTimeImmutable());
    }
}
