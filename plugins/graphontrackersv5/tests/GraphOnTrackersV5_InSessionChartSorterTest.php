<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
require_once(dirname(__FILE__) . '/../include/autoload.php');

class GraphOnTrackersV5_InSessionChartSorterTest extends TuleapTestCase {

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

    public function setUp() {
        parent::setUp();

        $this->pie      = mock('GraphOnTrackersV5_Chart_Pie');
        $this->bar      = mock('GraphOnTrackersV5_Chart_Bar');
        $this->burndown = mock('GraphOnTrackersV5_Chart_Burndown');
        $this->charts = array(
            $this->pie,
            $this->bar,
            $this->burndown
        );

        stub($this->pie)->getId()->returns('pie');
        stub($this->pie)->getRank()->returns(0);

        stub($this->bar)->getId()->returns('bar');
        stub($this->bar)->getRank()->returns(1);

        stub($this->burndown)->getId()->returns('burndown');
        stub($this->burndown)->getRank()->returns(2);

        $this->session = mock('Tracker_Report_Session');

        $this->sorter = new GraphOnTrackersV5_InSessionChartSorter($this->session);
    }

    private function expectOrder() {
        $charts_in_order = func_get_args();
        $i = 0;
        foreach ($charts_in_order as $chart) {
            expect($this->$chart)->setRank($i)->once("$chart should be at $i -> %s");
            expect($this->session)->set("charts.$chart.rank", $i);
            ++$i;
        }
    }

    public function itDoesNotSort() {
        $this->expectOrder('pie', 'bar', 'burndown');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->bar,
            GraphOnTrackersV5_InSessionChartSorter::FREEZE__DONT_MOVE
        );
    }

    public function itMovesToTheBeginning() {
        $this->expectOrder('bar', 'pie', 'burndown');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->bar,
            GraphOnTrackersV5_InSessionChartSorter::BEGINNING
        );
    }

    public function itMovesToTheEnd() {
        $this->expectOrder('pie', 'burndown', 'bar');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->bar,
            GraphOnTrackersV5_InSessionChartSorter::END
        );
    }

    public function itMovesAtASpecificPosition() {
        $this->expectOrder('bar', 'pie', 'burndown');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->pie,
            2
        );
    }

    public function itMovesAtASpecificPositionAtTheEnd() {
        $this->expectOrder('bar', 'burndown', 'pie');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->pie,
            3
        );
    }

    public function itMovesAtTheSamePosition() {
        $this->expectOrder('pie', 'bar', 'burndown');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->pie,
            1
        );
    }

    public function itMovesAtTheBeginningOutOfBounds() {
        $this->expectOrder('pie', 'bar', 'burndown');

        $this->sorter->sortChartInSession(
            $this->charts,
            $this->pie,
            0
        );
    }
}