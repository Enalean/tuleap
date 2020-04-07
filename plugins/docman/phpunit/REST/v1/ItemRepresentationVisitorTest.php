<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1;

use Docman_ItemFactory;
use Docman_LinkVersionFactory;
use Docman_VersionFactory;
use EventManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\REST\v1\Wiki\WikiPropertiesRepresentation;

class ItemRepresentationVisitorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var EventManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $event_manager;
    /**
     * @var Docman_VersionFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $docman_version_factory;
    /**
     * @var Docman_LinkVersionFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $link_version_factory;

    /**
     * @var Docman_ItemFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $item_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ItemRepresentationBuilder
     */
    private $item_representation_builder;
    /**
     * @var ItemRepresentationVisitor
     */
    private $item_visitor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->item_representation_builder = Mockery::mock(ItemRepresentationBuilder::class);
        $this->item_factory                = Mockery::mock(Docman_ItemFactory::class);
        $this->link_version_factory        = Mockery::mock(Docman_LinkVersionFactory::class);
        $this->docman_version_factory      = Mockery::mock(Docman_VersionFactory::class);
        $this->event_manager               = Mockery::mock(EventManager::class);
        $this->item_visitor                = new ItemRepresentationVisitor(
            $this->item_representation_builder,
            $this->docman_version_factory,
            $this->link_version_factory,
            $this->item_factory,
            $this->event_manager
        );
    }

    public function testItVisitAFolder(): void
    {
        $item   = Mockery::mock(\Docman_Folder::class);
        $params = ['current_user' => Mockery::mock(\PFUser::class)];

        $this->item_representation_builder->shouldReceive('buildItemRepresentation')->withArgs(
            [
                $item,
                $params['current_user'],
                ItemRepresentation::TYPE_FOLDER,
                null,
                null,
                null
            ]
        )->once();

        $this->item_visitor->visitFolder($item, $params);
    }

    public function testItVisitAWiki(): void
    {
        $item = Mockery::mock(\Docman_Wiki::class);
        $item->shouldReceive('getPagename')->atLeast()->andReturn('A wiki page');
        $item->shouldReceive('getGroupId')->once()->andReturn(102);

        $this->item_factory->shouldReceive('getIdInWikiOfWikiPageItem')->withArgs(['A wiki page', 102])->once(
        )->andReturn(10);

        $wiki_representation = new WikiPropertiesRepresentation();
        $wiki_representation->build($item, 10);

        $params = ['current_user' => Mockery::mock(\PFUser::class)];

        $this->item_representation_builder->shouldReceive('buildItemRepresentation')->once();

        $this->item_visitor->visitWiki($item, $params);
    }

    public function testItVisitALink(): void
    {
        $item   = Mockery::mock(\Docman_Link::class);
        $params = ['current_user' => Mockery::mock(\PFUser::class)];

        $this->item_representation_builder->shouldReceive('buildItemRepresentation')->once();

        $this->link_version_factory->shouldReceive('getLatestVersion')->never();

        $this->item_visitor->visitLink($item, $params);
    }

    public function testItVisitALinkAndStoreTheAccess(): void
    {
        $item   = Mockery::mock(\Docman_Link::class);
        $item->shouldReceive('getGroupId')->once();

        $version = Mockery::mock(\Docman_Version::class);
        $version->shouldReceive('getNumber')->andReturn(1);

        $item->shouldReceive('getCurrentVersion')->andReturn($version);
        $params = ['current_user' => Mockery::mock(\PFUser::class), 'is_a_direct_access' => true];

        $this->item_representation_builder->shouldReceive('buildItemRepresentation')->once();

        $this->link_version_factory->shouldReceive('getLatestVersion')->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->item_visitor->visitLink($item, $params);
    }

    public function testItVisitAFile(): void
    {
        $item   = Mockery::mock(\Docman_File::class);
        $params = ['current_user' => Mockery::mock(\PFUser::class)];

        $this->item_representation_builder->shouldReceive('buildItemRepresentation')->once();

        $this->docman_version_factory->shouldReceive('getCurrentVersionForItem')->once();

        $this->item_visitor->visitFile($item, $params);
    }

    public function testItVisitAnEmbeddedFile(): void
    {
        $item   = Mockery::mock(\Docman_EmbeddedFile::class);
        $params = ['current_user' => Mockery::mock(\PFUser::class)];

        $this->item_representation_builder->shouldReceive('buildItemRepresentation')->once();

        $this->docman_version_factory->shouldReceive('getCurrentVersionForItem')->once();

        $this->event_manager->shouldReceive('processEvent')->never();

        $this->item_visitor->visitEmbeddedFile($item, $params);
    }

    public function testItVisitAnEmbeddedFileAndStoreTheAccess(): void
    {
        $item   = Mockery::mock(\Docman_EmbeddedFile::class);
        $item->shouldReceive('getGroupId')->once();

        $version = Mockery::mock(\Docman_Version::class);
        $version->shouldReceive('getNumber')->andReturn(1);

        $item->shouldReceive('getCurrentVersion')->andReturn($version);
        $params = ['current_user' => Mockery::mock(\PFUser::class), 'is_a_direct_access' => true];

        $this->item_representation_builder->shouldReceive('buildItemRepresentation')->once();

        $this->docman_version_factory->shouldReceive('getCurrentVersionForItem')->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->item_visitor->visitEmbeddedFile($item, $params);
    }

    public function testItVisitAnEmpty(): void
    {
        $item   = Mockery::mock(\Docman_Empty::class);
        $params = ['current_user' => Mockery::mock(\PFUser::class)];

        $this->item_representation_builder->shouldReceive('buildItemRepresentation')->once();

        $this->item_visitor->visitEmpty($item, $params);
    }
}
