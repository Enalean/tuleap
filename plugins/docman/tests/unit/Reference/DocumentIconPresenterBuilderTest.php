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
use Tuleap\Test\PHPUnit\TestCase;

final class DocumentIconPresenterBuilderTest extends TestCase
{
    public function testBuildForItem(): void
    {
        $builder = new DocumentIconPresenterBuilder();

        $folder_icon = $builder->buildForItem(new Docman_Folder());
        self::assertEquals('fa fa-folder', $folder_icon->icon);
        self::assertEquals('inca-silver', $folder_icon->color);

        $link_icon = $builder->buildForItem(new Docman_Link());
        self::assertEquals('fa fa-link', $link_icon->icon);
        self::assertEquals('flamingo-pink', $link_icon->color);
    }
}
