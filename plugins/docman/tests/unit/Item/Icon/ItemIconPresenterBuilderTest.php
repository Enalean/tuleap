<?php
/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Item\Icon;

use Docman_Empty;
use Docman_Folder;
use Docman_Link;
use Docman_VersionFactory;
use Docman_Wiki;
use Override;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ItemIconPresenterBuilderTest extends TestCase
{
    private ItemIconPresenterBuilder $docman_icons;
    private Docman_VersionFactory&\PHPUnit\Framework\MockObject\MockObject $version_factory;

    #[Override]
    protected function setUp(): void
    {
        $event_dispatcher      = EventDispatcherStub::withIdentityCallback();
        $this->version_factory = $this->createMock(Docman_VersionFactory::class);

        $this->docman_icons = new ItemIconPresenterBuilder($event_dispatcher, $this->version_factory);
    }

    public function testItReturnsFolderIconFoFolder(): void
    {
        $folder_item = new Docman_Folder();

        $icon = $this->docman_icons->buildForItem($folder_item);

        $this->assertEquals('fa-regular fa-folder item-icon-color tlp-swatch-inca-silver', $icon->getIconWithColor());
    }

    public function testItReturnsOpenFolderIconWhenFolderIsExpanded(): void
    {
        $folder_item = new Docman_Folder();
        $params      = ['expanded' => true];

        $icon = $this->docman_icons->buildForItem($folder_item, $params);

        $this->assertEquals('fa-regular fa-folder-open item-icon-color tlp-swatch-inca-silver', $icon->getIconWithColor());
    }

    public function testItReturnsLinkIconForLink(): void
    {
        $link_item = new Docman_Link();

        $icon = $this->docman_icons->buildForItem($link_item);

        $this->assertEquals('fa-solid fa-link item-icon-color tlp-swatch-flamingo-pink', $icon->getIconWithColor());
    }

    public function testItReturnsWikiIconForPhpWiki(): void
    {
        $wiki_item = new Docman_Wiki();

        $icon = $this->docman_icons->buildForItem($wiki_item);

        $this->assertEquals('fa-brands fa-wikipedia-w item-icon-color tlp-swatch-inca-silver', $icon->getIconWithColor());
    }

    public function testItReturnsDefaultIconForEmptyItem(): void
    {
        $empty_item = new Docman_Empty();

        $icon = $this->docman_icons->buildForItem($empty_item);

        $this->assertEquals('fa-regular fa-file item-icon-color tlp-swatch-firemist-silver', $icon->getIconWithColor());
    }

    public function testItDispatchesEventWhenRetrievingIcon(): void
    {
        $event_dispatcher = EventDispatcherStub::withCallback(
            static function (object $event): object {
                if ($event instanceof ItemIconPresenterEvent) {
                    $event->setPresenter(new ItemIconPresenter('fa-solid fa-star', 'gold'));
                }
                return $event;
            }
        );

        $docman_icons = new ItemIconPresenterBuilder($event_dispatcher, $this->version_factory);

        $item = new Docman_Folder();
        $icon = $docman_icons->buildForItem($item);

        $this->assertEquals('fa-solid fa-star tlp-swatch-gold', $icon->getIconWithColor());
    }

    public function testItReturnsAudioIconForAudioMimeType(): void
    {
        $icon = $this->docman_icons->getIconPresenterForMimeType('audio/mpeg');

        $this->assertEquals('fa-solid fa-file-audio item-icon-color tlp-swatch-lake-placid-blue', $icon->getIconWithColor());
    }

    public function testItReturnsVideoIconForVideoMimeType(): void
    {
        $icon = $this->docman_icons->getIconPresenterForMimeType('video/mp4');

        $this->assertEquals('fa-solid fa-file-video item-icon-color tlp-swatch-ocean-turquoise', $icon->getIconWithColor());
    }

    public function testItReturnsImageIconForImageMimeType(): void
    {
        $icon = $this->docman_icons->getIconPresenterForMimeType('image/png');

        $this->assertEquals('fa-solid fa-file-image item-icon-color tlp-swatch-graffiti-yellow', $icon->getIconWithColor());
    }

    public function testItReturnsCodeIconForHtmlTextMimeType(): void
    {
        $icon = $this->docman_icons->getIconPresenterForMimeType('text/html');

        $this->assertEquals('fa-solid fa-file-code item-icon-color tlp-swatch-inca-silver', $icon->getIconWithColor());
    }

    public function testItReturnsPdfIconForPdfMimeType(): void
    {
        $icon = $this->docman_icons->getIconPresenterForMimeType('application/pdf');

        $this->assertEquals('fa-solid fa-file-pdf item-icon-color tlp-swatch-fiesta-red', $icon->getIconWithColor());
    }

    public function testItReturnsDefaultIconForUnknownMimeType(): void
    {
        $icon = $this->docman_icons->getIconPresenterForMimeType('unknown/mimetype');

        $this->assertEquals('fa-regular fa-file item-icon-color tlp-swatch-firemist-silver', $icon->getIconWithColor());
    }
}
