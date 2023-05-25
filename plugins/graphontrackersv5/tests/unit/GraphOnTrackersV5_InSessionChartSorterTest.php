<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps, PSR1.Classes.ClassDeclaration.MissingNamespace
final class GraphOnTrackersV5_InSessionChartSorterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var GraphOnTrackersV5_Chart_Pie&\PHPUnit\Framework\MockObject\MockObject */
    private $pie;

    /** @var GraphOnTrackersV5_Chart_Bar&\PHPUnit\Framework\MockObject\MockObject */
    private $bar;

    /** @var GraphOnTrackersV5_Chart_Burndown&\PHPUnit\Framework\MockObject\MockObject */
    private $burndown;

    /** @var array<GraphOnTrackersV5_Chart&\PHPUnit\Framework\MockObject\MockObject> */
    private array $charts;

    /** @var Tracker_Report_Session&\PHPUnit\Framework\MockObject\MockObject */
    private $session;

    private GraphOnTrackersV5_InSessionChartSorter $sorter;

    public function setUp(): void
    {
        parent::setUp();

        $this->pie      = $this->createMock(\GraphOnTrackersV5_Chart_Pie::class);
        $this->bar      = $this->createMock(\GraphOnTrackersV5_Chart_Bar::class);
        $this->burndown = $this->createMock(\GraphOnTrackersV5_Chart_Burndown::class);
        $this->charts   = [
            $this->pie,
            $this->bar,
            $this->burndown,
        ];

        $this->pie->method('getId')->willReturn('pie');
        $this->pie->method('getRank')->willReturn(0);

        $this->bar->method('getId')->willReturn('bar');
        $this->bar->method('getRank')->willReturn(1);

        $this->burndown->method('getId')->willReturn('burndown');
        $this->burndown->method('getRank')->willReturn(2);

        $this->session = $this->createMock(\Tracker_Report_Session::class);

        $this->sorter = new GraphOnTrackersV5_InSessionChartSorter($this->session);
    }

    private function expectOrder(): void
    {
        $charts_in_order = func_get_args();
        $i               = 0;
        foreach ($charts_in_order as $chart) {
            $this->$chart->expects(self::once())->method('setRank')->with($i);
            $this->session->method('set');
            ++$i;
        }
    }

    public function testItDoesNotSort(): void
    {
        $this->expectOrder('pie', 'bar', 'burndown');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->bar,
            GraphOnTrackersV5_InSessionChartSorter::FREEZE__DONT_MOVE
        );
    }

    public function testItMovesToTheBeginning(): void
    {
        $this->expectOrder('bar', 'pie', 'burndown');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->bar,
            GraphOnTrackersV5_InSessionChartSorter::BEGINNING
        );
    }

    public function testItMovesToTheEnd(): void
    {
        $this->expectOrder('pie', 'burndown', 'bar');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->bar,
            GraphOnTrackersV5_InSessionChartSorter::END
        );
    }

    public function testItMovesAtASpecificPosition(): void
    {
        $this->expectOrder('bar', 'pie', 'burndown');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->pie,
            2
        );
    }

    public function testItMovesAtASpecificPositionAtTheEnd(): void
    {
        $this->expectOrder('bar', 'burndown', 'pie');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->pie,
            3
        );
    }

    public function testItMovesAtTheSamePosition(): void
    {
        $this->expectOrder('pie', 'bar', 'burndown');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->pie,
            1
        );
    }

    public function testItMovesAtTheBeginningOutOfBounds(): void
    {
        $this->expectOrder('pie', 'bar', 'burndown');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->pie,
            0
        );
    }
}
