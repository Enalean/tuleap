<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class GraphOnTrackersV5_InSessionChartSorterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var GraphOnTrackersV5_Chart_Pie */
    private $pie;

    /** @var GraphOnTrackersV5_Chart_Bar */
    private $bar;

    /** @var GraphOnTrackersV5_Chart_Burndown */
    private $burndown;

    /** @var GraphOnTrackersV5_Chart[] */
    private $charts;

    /** @var Tracker_Report_Session */
    private $session;

    /** @var GraphOnTrackersV5_InSessionChartSorter */
    private $sorter;

    public function setUp(): void
    {
        parent::setUp();

        $this->pie      = \Mockery::spy(\GraphOnTrackersV5_Chart_Pie::class);
        $this->bar      = \Mockery::spy(\GraphOnTrackersV5_Chart_Bar::class);
        $this->burndown = \Mockery::spy(\GraphOnTrackersV5_Chart_Burndown::class);
        $this->charts = array(
            $this->pie,
            $this->bar,
            $this->burndown
        );

        $this->pie->shouldReceive('getId')->andReturn('pie');
        $this->pie->shouldReceive('getRank')->andReturn(0);

        $this->bar->shouldReceive('getId')->andReturn('bar');
        $this->bar->shouldReceive('getRank')->andReturn(1);

        $this->burndown->shouldReceive('getId')->andReturn('burndown');
        $this->burndown->shouldReceive('getRank')->andReturn(2);

        $this->session = \Mockery::spy(\Tracker_Report_Session::class);

        $this->sorter = new GraphOnTrackersV5_InSessionChartSorter($this->session);
    }

    private function expectOrder()
    {
        $charts_in_order = func_get_args();
        $i = 0;
        foreach ($charts_in_order as $chart) {
            $this->$chart->shouldReceive('setRank')->with($i)->once();
            $this->session->shouldReceive('set');
            ++$i;
        }
    }

    public function testItDoesNotSort()
    {
        $this->expectOrder('pie', 'bar', 'burndown');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->bar,
            GraphOnTrackersV5_InSessionChartSorter::FREEZE__DONT_MOVE
        );
    }

    public function testItMovesToTheBeginning()
    {
        $this->expectOrder('bar', 'pie', 'burndown');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->bar,
            GraphOnTrackersV5_InSessionChartSorter::BEGINNING
        );
    }

    public function testItMovesToTheEnd()
    {
        $this->expectOrder('pie', 'burndown', 'bar');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->bar,
            GraphOnTrackersV5_InSessionChartSorter::END
        );
    }

    public function testItMovesAtASpecificPosition()
    {
        $this->expectOrder('bar', 'pie', 'burndown');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->pie,
            2
        );
    }

    public function testItMovesAtASpecificPositionAtTheEnd()
    {
        $this->expectOrder('bar', 'burndown', 'pie');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->pie,
            3
        );
    }

    public function testItMovesAtTheSamePosition()
    {
        $this->expectOrder('pie', 'bar', 'burndown');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->pie,
            1
        );
    }

    public function testItMovesAtTheBeginningOutOfBounds()
    {
        $this->expectOrder('pie', 'bar', 'burndown');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->pie,
            0
        );
    }
}
