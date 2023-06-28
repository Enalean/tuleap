<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column;

use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\TrackerMappingPresenter;

final class ColumnPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testConstructAssignsTLPColor(): void
    {
        $mappings  = [new TrackerMappingPresenter(13, 123, [1024])];
        $column    = new \Cardwall_Column(97, 'On Going', 'teddy-brown');
        $presenter = new ColumnPresenter($column, false, $mappings);
        self::assertSame(97, $presenter->id);
        self::assertSame('On Going', $presenter->label);
        self::assertSame('teddy-brown', $presenter->color);
        self::assertFalse($presenter->is_collapsed);
        self::assertSame($mappings, $presenter->mappings);
    }

    public function testConstructAssignsHexColor(): void
    {
        $mappings  = [new TrackerMappingPresenter(13, 123, [1024])];
        $column    = new \Cardwall_Column(97, 'On Going', 'rgb(164,91,68)');
        $presenter = new ColumnPresenter($column, false, $mappings);
        self::assertSame('#A45B44', $presenter->color);
    }
}
