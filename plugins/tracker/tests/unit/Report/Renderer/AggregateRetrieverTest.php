<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Renderer;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Report_Renderer_Table;
use Tracker_Report_Renderer_Table_FunctionsAggregatesDao;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AggregateRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Tracker_Report_Renderer_Table_FunctionsAggregatesDao&MockObject $aggregates_dao;
    private Tracker_Report_Renderer_Table&MockObject $renderer;
    private AggregateRetriever $retriever;

    protected function setUp(): void
    {
        $this->aggregates_dao = $this->createMock(Tracker_Report_Renderer_Table_FunctionsAggregatesDao::class);
        $this->renderer       = $this->createMock(Tracker_Report_Renderer_Table::class);

        $this->retriever = new AggregateRetriever($this->aggregates_dao, $this->renderer);
    }

    public function testItReturnsAggregatesFromDatabase(): void
    {
        $renderer_id = 101;

        $this->renderer->method('getId')->willReturn($renderer_id);
        $this->aggregates_dao
            ->expects($this->once())
            ->method('searchByRendererId')
            ->with($renderer_id)
            ->willReturn([
                ['field_id' => 200, 'aggregate' => 'SUM'],
                ['field_id' => 300, 'aggregate' => 'AVG'],
            ]);

        $columns = [200 => 'Todo', 300 => 'On Going'];

        $result = $this->retriever->retrieve(true, $columns);

        self::assertEquals(
            [
                200 => ['SUM'],
                300 => ['AVG'],
            ],
            $result
        );
    }

    public function testItReturnsAggregatesFromRenderer(): void
    {
        $this->renderer->expects($this->once())->method('getAggregates')->willReturn([
            [
                ['field_id' => 400, 'aggregate' => 'MAX'],
                ['field_id' => 500, 'aggregate' => 'MIN'],
            ],
        ]);

        $columns = [400 => 'Review', 500 => 'Done'];

        $result = $this->retriever->retrieve(false, $columns);

        self::assertEquals(
            [
                400 => ['MAX'],
                500 => ['MIN'],
            ],
            $result
        );
    }

    public function testItSkipsRowsWhenFieldIdIsNotInColumns(): void
    {
        $this->renderer->method('getAggregates')->willReturn([
            [
                ['field_id' => 600, 'aggregate' => 'SUM'],
                ['field_id' => 700, 'aggregate' => 'AVG'],
            ],
        ]);

        $columns = [600 => 'Cancelled'];

        $result = $this->retriever->retrieve(false, $columns);

        self::assertEquals(
            [
                600 => ['SUM'],
            ],
            $result
        );
    }

    public function testItHandlesEmptyAggregates(): void
    {
        $this->renderer->method('getAggregates')->willReturn([]);

        $columns = [600 => 'Cancelled'];

        $result = $this->retriever->retrieve(false, $columns);

        $this->assertEmpty($result);
    }

    public function testItGroupsAggregatesByFieldId(): void
    {
        $this->renderer->method('getAggregates')->willReturn([
            [
                ['field_id' => 900, 'aggregate' => 'SUM'],
                ['field_id' => 900, 'aggregate' => 'AVG'],
            ],
        ]);

        $columns = [900 => 'Deleted'];

        $result = $this->retriever->retrieve(false, $columns);

        self::assertEquals(
            [
                900 => ['SUM', 'AVG'],
            ],
            $result
        );
    }

    public function testItReturnsEmptyWhenAggregatesAreNull(): void
    {
        $this->renderer->method('getAggregates')->willReturn(null);

        $columns = [900 => 'Deleted'];

        $result = $this->retriever->retrieve(false, $columns);

        self::assertEquals([], $result);
    }
}
