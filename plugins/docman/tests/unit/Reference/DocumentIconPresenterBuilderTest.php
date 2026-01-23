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
use Tuleap\Docman\Item\Icon\ItemIconPresenter;
use Tuleap\Docman\Item\Icon\ItemIconPresenterBuilder;
use Tuleap\Docman\Item\Icon\ItemIconPresenterEvent;
use Tuleap\Docman\Item\OtherDocument;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocumentIconPresenterBuilderTest extends TestCase
{
    public function testBuildForItem(): void
    {
        $builder = new ItemIconPresenterBuilder(
            EventDispatcherStub::withCallback(
                static function (object $event): object {
                    if ($event instanceof ItemIconPresenterEvent && ! str_contains($event->getPresenter()->getIcon(), 'fa-folder')) {
                        $event->setPresenter(new ItemIconPresenter('my-icon', 'inca-silver'));
                    }

                    if ($event instanceof ItemIconPresenterEvent && $event->getPresenter()->getIcon() === 'my-icon') {
                        $event->setPresenter(new ItemIconPresenter('fa-solid fa-rocket', 'fiesta-red'));
                    }

                    return $event;
                }
            ),
            $this->createStub(\Docman_VersionFactory::class)
        );

        $folder_icon = $builder->buildForItem(new Docman_Folder());
        self::assertEquals('fa-regular fa-folder item-icon-color tlp-swatch-inca-silver', $folder_icon->getIconWithColor());

        $other_document_icon = $builder->buildForItem(new class ([]) extends OtherDocument {
        });
        self::assertEquals('fa-solid fa-rocket tlp-swatch-fiesta-red', $other_document_icon->getIconWithColor());
    }

    public function testBuildForItemDefaultsToBinaryFile(): void
    {
        $builder = new ItemIconPresenterBuilder(EventDispatcherStub::withIdentityCallback(), $this->createStub(\Docman_VersionFactory::class));

        $document_icon = $builder->buildForItem(new class ([]) extends OtherDocument {
        });
        self::assertEquals('fa-regular fa-file item-icon-color tlp-swatch-firemist-silver', $document_icon->getIconWithColor());
    }
}
