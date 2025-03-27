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

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_ItemFactory;
use Docman_Link;
use Docman_LinkVersion;
use Docman_LinkVersionFactory;
use Docman_Version;
use Docman_VersionFactory;
use Docman_Wiki;
use EventManager;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ItemRepresentationVisitorTest extends TestCase
{
    private EventManager&MockObject $event_manager;
    private Docman_VersionFactory&MockObject $docman_version_factory;
    private Docman_LinkVersionFactory&MockObject $link_version_factory;
    private Docman_ItemFactory&MockObject $item_factory;
    private ItemRepresentationBuilder&MockObject $item_representation_builder;
    private ItemRepresentationVisitor $item_visitor;

    protected function setUp(): void
    {
        $this->item_representation_builder = $this->createMock(ItemRepresentationBuilder::class);
        $this->item_factory                = $this->createMock(Docman_ItemFactory::class);
        $this->link_version_factory        = $this->createMock(Docman_LinkVersionFactory::class);
        $this->docman_version_factory      = $this->createMock(Docman_VersionFactory::class);
        $this->event_manager               = $this->createMock(EventManager::class);
        $event_adder                       = $this->createMock(DocmanItemsEventAdder::class);
        $event_adder->expects($this->once())->method('addLogEvents');
        $this->item_visitor = new ItemRepresentationVisitor(
            $this->item_representation_builder,
            $this->docman_version_factory,
            $this->link_version_factory,
            $this->item_factory,
            $this->event_manager,
            $event_adder
        );
    }

    public function testItVisitAFolder(): void
    {
        $item   = new Docman_Folder();
        $params = ['current_user' => UserTestBuilder::buildWithDefaults()];

        $this->item_representation_builder->expects($this->once())->method('buildItemRepresentation')->with(
            $item,
            $params['current_user'],
            ItemRepresentation::TYPE_FOLDER,
            null,
            null,
            null,
            null,
            null,
        );

        $this->item_visitor->visitFolder($item, $params);
    }

    public function testItVisitAFolderAndReturnsItsSize(): void
    {
        $folder = new Docman_Folder();
        $user   = UserTestBuilder::buildWithDefaults();
        $params = [
            'current_user' => $user,
            'with_size'    => true,
        ];

        $this->item_factory->method('getItemTree')->with(
            $folder,
            $user,
            false,
            true
        );

        $this->item_representation_builder->expects($this->once())->method('buildItemRepresentation');

        $this->item_visitor->visitFolder($folder, $params);
    }

    public function testItVisitAWiki(): void
    {
        $item = new Docman_Wiki(['wiki_page' => 'A wiki page', 'group_id' => 102]);

        $this->item_factory->expects($this->once())->method('getIdInWikiOfWikiPageItem')->with('A wiki page', 102)->willReturn(10);

        $params = ['current_user' => UserTestBuilder::buildWithDefaults()];

        $this->item_representation_builder->expects($this->once())->method('buildItemRepresentation');

        $this->item_visitor->visitWiki($item, $params);
    }

    public function testItVisitALink(): void
    {
        $item   = new Docman_Link(['link_url' => '']);
        $params = ['current_user' => UserTestBuilder::buildWithDefaults()];

        $this->item_representation_builder->expects($this->once())->method('buildItemRepresentation');

        $this->link_version_factory->expects(self::never())->method('getLatestVersion');

        $this->item_visitor->visitLink($item, $params);
    }

    public function testItVisitALinkAndStoreTheAccess(): void
    {
        $item = new Docman_Link(['link_url' => '']);

        $version = $this->createMock(Docman_LinkVersion::class);
        $version->method('getNumber')->willReturn(1);

        $item->setCurrentVersion($version);
        $params = ['current_user' => UserTestBuilder::buildWithDefaults(), 'is_a_direct_access' => true];

        $this->item_representation_builder->expects($this->once())->method('buildItemRepresentation');

        $this->link_version_factory->expects($this->once())->method('getLatestVersion');

        $this->event_manager->expects($this->once())->method('processEvent');

        $this->item_visitor->visitLink($item, $params);
    }

    public function testItVisitAFile(): void
    {
        $item   = new Docman_File();
        $params = ['current_user' => UserTestBuilder::buildWithDefaults()];

        $this->item_representation_builder->expects($this->once())->method('buildItemRepresentation');

        $this->docman_version_factory->expects($this->once())->method('getCurrentVersionForItem');

        $this->item_visitor->visitFile($item, $params);
    }

    public function testItVisitAnEmbeddedFile(): void
    {
        $item   = new Docman_EmbeddedFile();
        $params = ['current_user' => UserTestBuilder::buildWithDefaults()];

        $this->item_representation_builder->expects($this->once())->method('buildItemRepresentation');

        $this->docman_version_factory->expects($this->once())->method('getCurrentVersionForItem');

        $this->event_manager->expects(self::never())->method('processEvent');

        $this->item_visitor->visitEmbeddedFile($item, $params);
    }

    public function testItVisitAnEmbeddedFileAndStoreTheAccess(): void
    {
        $item    = new Docman_EmbeddedFile();
        $version = new Docman_Version(['number' => 1]);
        $item->setCurrentVersion($version);

        $params = ['current_user' => UserTestBuilder::buildWithDefaults(), 'is_a_direct_access' => true];

        $this->item_representation_builder->expects($this->once())->method('buildItemRepresentation');

        $this->docman_version_factory->expects($this->once())->method('getCurrentVersionForItem');

        $this->event_manager->expects($this->once())->method('processEvent');

        $this->item_visitor->visitEmbeddedFile($item, $params);
    }

    public function testItVisitAnEmpty(): void
    {
        $item   = new Docman_Empty();
        $params = ['current_user' => UserTestBuilder::buildWithDefaults()];

        $this->item_representation_builder->expects($this->once())->method('buildItemRepresentation');

        $this->item_visitor->visitEmpty($item, $params);
    }
}
