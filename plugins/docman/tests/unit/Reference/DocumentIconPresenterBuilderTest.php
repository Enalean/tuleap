<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Reference;

use Docman_Folder;
use Docman_Link;
use Tuleap\Docman\Item\Icon\DocumentIconPresenterEvent;
use Tuleap\Docman\Item\Icon\GetIconForItemEvent;
use Tuleap\Docman\Item\OtherDocument;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocumentIconPresenterBuilderTest extends TestCase
{
    public function testBuildForItem(): void
    {
        $builder = new DocumentIconPresenterBuilder(
            EventDispatcherStub::withCallback(
                static function (object $event): object {
                    if ($event instanceof GetIconForItemEvent) {
                        $event->setIcon('my-icon');
                    }

                    if ($event instanceof DocumentIconPresenterEvent && $event->icon === 'my-icon') {
                        $event->setPresenter(new DocumentIconPresenter('fa-solid fa-rocket', 'fiesta-red'));
                    }

                    return $event;
                }
            )
        );

        $folder_icon = $builder->buildForItem(new Docman_Folder());
        self::assertEquals('fa fa-folder', $folder_icon->icon);
        self::assertEquals('inca-silver', $folder_icon->color);

        $link_icon = $builder->buildForItem(new Docman_Link());
        self::assertEquals('fa fa-link', $link_icon->icon);
        self::assertEquals('flamingo-pink', $link_icon->color);

        $other_document_icon = $builder->buildForItem(new class ([]) extends OtherDocument {
        });
        self::assertEquals('fa-solid fa-rocket', $other_document_icon->icon);
        self::assertEquals('fiesta-red', $other_document_icon->color);
    }

    public function testBuildForItemDefaultsToBinaryFile(): void
    {
        $builder = new DocumentIconPresenterBuilder(EventDispatcherStub::withIdentityCallback());

        $document_icon = $builder->buildForItem(new class ([]) extends OtherDocument {
        });
        self::assertEquals('far fa-file', $document_icon->icon);
        self::assertEquals('firemist-silver', $document_icon->color);
    }
}
