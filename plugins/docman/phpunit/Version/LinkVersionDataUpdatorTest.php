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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class LinkVersionDataUpdatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Docman_ItemFactory|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $item_factory;
    /**
     * @var LinkVersionDataUpdator
     */
    private $link_data_updator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->item_factory      = \Mockery::mock(\Docman_ItemFactory::class);
        $this->link_data_updator = new LinkVersionDataUpdator($this->item_factory);
    }

    public function testItShouldUpdateAndReturnALink(): void
    {
        $empty = \Mockery::mock(\Docman_Empty::class);
        $empty->shouldReceive('getId')->andReturn(1);
        $empty->shouldReceive('getGroupId')->andReturn(100);
        $empty->shouldReceive('getTitle')->andReturn('Tradition');
        $empty->shouldReceive('getOwnerId')->andReturn(2);

        $version_data = ['link_url' => 'https://example.test'];

        $row = [
            'id'        => $empty->getId(),
            'group_id'  => $empty->getGroupId(),
            'title'     => $empty->getTitle(),
            'user_id'   => $empty->getOwnerId(),
            'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_LINK,
            'link_url'  => $version_data['link_url'],
        ];
        $this->item_factory->shouldReceive('update')->with($row)->once();

        $link = \Mockery::mock(\Docman_Link::class);
        $this->item_factory->shouldReceive('getItemFromDb')->with($empty->getId())->andReturn($link);

        $this->item_factory->shouldReceive('createNewLinkVersion')->withArgs([$link, $version_data]);

        $updated_link = $this->link_data_updator->updateLinkFromEmptyVersionData($empty, $version_data);

        $this->assertEquals($updated_link, $link);
    }
}
