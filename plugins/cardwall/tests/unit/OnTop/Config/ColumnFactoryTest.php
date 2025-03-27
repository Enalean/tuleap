<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

namespace Tuleap\Cardwall\OnTop\Config;

use Cardwall_OnTop_ColumnDao;
use PHPUnit\Framework\MockObject\MockObject;
use TestHelper;
use Tracker;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ColumnFactoryTest extends TestCase
{
    private Tracker $tracker;
    private Cardwall_OnTop_ColumnDao&MockObject $dao;
    private ColumnFactory $factory;

    protected function setUp(): void
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId(42)->build();
        $this->dao     = $this->createMock(Cardwall_OnTop_ColumnDao::class);
        $this->factory = new ColumnFactory($this->dao);
    }

    public function testItBuildsColumnsFromTheDataStorage(): void
    {
        $this->dao->expects($this->once())->method('searchColumnsByTrackerId')
            ->with(42)
            ->willReturn([
                [
                    'id'             => 1,
                    'label'          => 'Todo',
                    'bg_red'         => '123',
                    'bg_green'       => '12',
                    'bg_blue'        => '10',
                    'tlp_color_name' => null,
                ],
                [
                    'id'             => 2,
                    'label'          => 'On Going',
                    'bg_red'         => null,
                    'bg_green'       => null,
                    'bg_blue'        => null,
                    'tlp_color_name' => null,
                ],
                [
                    'id'             => 2,
                    'label'          => 'Review',
                    'bg_red'         => null,
                    'bg_green'       => null,
                    'bg_blue'        => null,
                    'tlp_color_name' => 'peggy-pink',
                ],
            ]);
        $columns = $this->factory->getDashboardColumns($this->tracker);

        self::assertCount(3, $columns);
        self::assertSame('On Going', $columns[1]->getLabel());
        self::assertSame('rgb(123, 12, 10)', $columns[0]->getHeadercolor());
        self::assertSame('rgb(248,248,248)', $columns[1]->getHeadercolor());

        self::assertSame('Review', $columns[2]->getLabel());
        self::assertSame('peggy-pink', $columns[2]->getHeadercolor());
    }

    public function testItBuildsAnEmptyCollection(): void
    {
        $this->dao->method('searchColumnsByTrackerId')
            ->with(42)
            ->willReturn([]);
        $columns = $this->factory->getDashboardColumns($this->tracker);

        self::assertCount(0, $columns);
    }

    public function testGetColumnByIdReturnsNullWhenColumnCantBeFound(): void
    {
        $this->dao->expects($this->once())->method('searchByColumnId')
            ->with(79)
            ->willReturn(false);
        self::assertNull($this->factory->getColumnById(79));
    }

    public function testGetColumnByIdBuildsASingleColumn(): void
    {
        $this->dao->expects($this->once())->method('searchByColumnId')
            ->with(79)
            ->willReturn(TestHelper::arrayToDar([
                'id'             => 79,
                'label'          => 'Review',
                'bg_red'         => null,
                'bg_green'       => null,
                'bg_blue'        => null,
                'tlp_color_name' => 'acid-green',
            ]));
        $column = $this->factory->getColumnById(79);
        self::assertSame('Review', $column->getLabel());
        self::assertSame('acid-green', $column->getHeadercolor());
    }

    public function testItShouldNotFatalErrorOnInvalidBindValue(): void
    {
        $filter = [123, 234];
        $field  = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(692)->build()
        )->build()->getField();

        $this->expectNotToPerformAssertions();
        $this->factory->getFilteredRendererColumns($field, $filter);
    }
}
