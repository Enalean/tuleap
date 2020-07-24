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

class GraphOnTrackersV5_InSessionChartSorter
{
    public const FREEZE__DONT_MOVE = '--';
    public const BEGINNING         = 'beginning';
    public const END               = 'end';

    /**
     * @var Tracker_Report_Session
     */
    private $session;

    public function __construct(Tracker_Report_Session $session)
    {
        $this->session = $session;
    }

    public function sortChartInSession(array $charts, GraphOnTrackersV5_Chart $edited_chart, $wanted_position)
    {
        $this->moveElementInCollection($charts, $edited_chart, $wanted_position);

        $rank = 0;
        foreach ($charts as $chart) {
            $id = $chart->getId();
            $this->session->set("charts.$id.rank", $rank);
            $chart->setRank($rank);
            ++$rank;
        }
    }

    private function moveElementInCollection(array &$charts, GraphOnTrackersV5_Chart $edited_chart, $wanted_position)
    {
        if ($wanted_position === self::FREEZE__DONT_MOVE) {
            return;
        }

        $this->removeElementFromCollection($charts, $edited_chart);
        $this->addElementInCollection($charts, $edited_chart, $wanted_position);
    }

    private function removeElementFromCollection(
        array &$charts,
        GraphOnTrackersV5_Chart $edited_chart
    ) {
        foreach ($charts as $key => $chart) {
            if ($chart->getId() === $edited_chart->getId()) {
                unset($charts[$key]);
            }
        }
    }

    private function addElementInCollection(
        array &$charts,
        GraphOnTrackersV5_Chart $edited_chart,
        $wanted_position
    ) {
        switch ($wanted_position) {
            case self::BEGINNING:
                array_unshift($charts, $edited_chart);
                break;
            case self::END:
                array_push($charts, $edited_chart);
                break;
            default:
                $elements_to_move = array_splice($charts, $wanted_position - 1);
                $charts = array_merge($charts, [$edited_chart], $elements_to_move);
        }
    }
}
