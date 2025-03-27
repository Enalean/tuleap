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

namespace Tuleap\Docman\Version;

use Docman_Empty;
use Docman_ItemFactory;
use Docman_Link;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LinkVersionDataUpdatorTest extends TestCase
{
    private Docman_ItemFactory&MockObject $item_factory;
    private LinkVersionDataUpdator $link_data_updator;

    protected function setUp(): void
    {
        $this->item_factory      = $this->createMock(Docman_ItemFactory::class);
        $this->link_data_updator = new LinkVersionDataUpdator($this->item_factory);
    }

    public function testItShouldUpdateAndReturnALink(): void
    {
        $empty = new Docman_Empty(['item_id' => 1, 'group_id' => 100, 'title' => 'Tradition', 'user_id' => 2]);

        $version_data = ['link_url' => 'https://example.test'];

        $row = [
            'id'        => $empty->getId(),
            'group_id'  => $empty->getGroupId(),
            'title'     => $empty->getTitle(),
            'user_id'   => $empty->getOwnerId(),
            'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_LINK,
            'link_url'  => $version_data['link_url'],
        ];
        $this->item_factory->expects($this->once())->method('update')->with($row);

        $link = new Docman_Link(['link_url' => '']);
        $this->item_factory->method('getItemFromDb')->with($empty->getId())->willReturn($link);

        $this->item_factory->method('createNewLinkVersion')->with($link, $version_data);

        $updated_link = $this->link_data_updator->updateLinkFromEmptyVersionData($empty, $version_data);

        self::assertEquals($updated_link, $link);
    }
}
