<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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

namespace Tuleap\GraphOnTrackersV5\GraphicLibrary;

use BarPlot;
use GroupBarPlot;
use Tuleap\Chart\Chart;

class GraphOnTrackersV5_Engine_Bar extends GraphOnTrackersV5_Engine
{
    public $title;
    public $description;
    public $field_base;
    public $field_group;
    public $height;
    public $width;
    public array $legend = [];
    public array $xaxis  = [];
    public array $labels = [];
    private $keys;

    /**
     * Builds bar chart object
     */
    public function buildGraph()
    {
        if (! $this->width) {
            if (! is_null($this->xaxis)) {
                $this->width = (count($this->data) * count($this->data[0]) * 25) + (2 * 150);
            } else {
                $this->width = (count($this->data) * 100) + (2 * 150);
            }
        }

        $right_margin = 50;

        $this->graph = new Chart($this->width, $this->height);
        $this->graph->SetScale('textlint');
        $this->graph->title->Set($this->title);
        if (is_null($this->description)) {
            $this->description = '';
        }
        $this->graph->subtitle->Set($this->description);

        // x axis formating
        $this->graph->xaxis->SetTickSide(SIDE_DOWN);

        $this->graph->xaxis->title->setMargin(60, 20, 20, 20);

        if (! is_null($this->xaxis)) {
            ksort($this->xaxis);
            $this->graph->xaxis->SetTickLabels(array_values($this->xaxis));
        } else {
            $this->graph->xaxis->SetTickLabels(array_values($this->legend));
        }

        $colors = $this->getColors();

        if (is_null($this->xaxis)) {
            if ((is_array($this->data)) && (array_sum($this->data) > 0)) {
                $this->graph->add($this->getBarPlot($this->data, $colors));
            }
        } else {
            $this->keys = [];
            foreach ($this->data as $group => $data) {
                foreach ($data as $key => $nb) {
                    $this->keys[$key] = 1;
                }
            }
            $this->keys = array_keys($this->keys);
            sort($this->keys);
            foreach ($this->data as $group => $data) {
                foreach ($this->keys as $key) {
                    if (! isset($data[$key])) {
                        $this->data[$group][$key] = 0;
                    }
                }
                uksort($this->data[$group], [$this, 'sort']);
            }
            $l = 0;
            $b = [];
            foreach ($this->data as $base => $group) {
                $b[$l] = $this->getBarPlot(array_values($group), $colors[$base]);
                $b[$l]->SetLegend($this->legend[$base]);
                $l++;
            }
            $gbplot = new GroupBarPlot($b);
            $this->graph->add($gbplot);
            $right_margin = 150;
        }
        $this->graph->SetMargin(50, $right_margin, $this->graph->getTopMargin() + 40, 100);
        return $this->graph;
    }

    public function sort($a, $b)
    {
        $search_a = array_search($a, $this->keys);
        $search_b = array_search($b, $this->keys);
        if ($search_a === false || $search_b === false) {
            return 0;
        }
        return $search_a - $search_b;
    }

    public function getBarPlot($data, $color)
    {
        $b = new BarPlot($data);
        //parameters hard coded for the moment
        $b->SetAbsWidth(10);
        $b->value->Show(true);
        $b->value->SetColor($this->graph->getMainColor());
        $b->value->SetFormat('%d');
        $b->value->HideZero();
        $b->value->SetMargin(4);
        $b->value->SetFont($this->graph->getFont(), FS_NORMAL, 7);

        $b->SetWidth(0.4);
        if (is_array($color)) {
            $b->SetColor('#FFFFFF:0.7');
        } else {
            $b->SetColor($color . ':0.7');
        }
        $b->SetFillColor($color);
        // end hard coded parameter
        return $b;
    }

    public function toArray()
    {
        return $this->getChartData(
            [
                'title'  => $this->title,
                'height' => $this->height,
                'width'  => $this->width,
                'legend' => array_values($this->legend),
            ]
        );
    }

    private function getChartData(array $info)
    {
        $row = current($this->data);
        if (is_array($row)) {
            return $this->getGroupedBarChartData($info);
        } else {
            return $this->getBarChartData($info);
        }
    }

    private function getGroupedBarChartData(array $info): array
    {
        $bar_chart_data_builder = new BarChartDataBuilder();
        return $info + [
            'type'           => 'groupedbar',
            'grouped_labels' => array_values($this->legend),
            'values'         => $bar_chart_data_builder->buildGroupedBarChartData($this),
            'colors'         => $this->getColorPerLegend(),
        ];
    }

    private function getColorPerLegend()
    {
        $colors = [];
        foreach ($this->legend as $index => $name) {
            $colors[] = [
                'label' => $name,
                'color' => $this->getColorOrNull($this->colors[$index]),
            ];
        }

        return $colors;
    }

    private function getBarChartData(array $info)
    {
        return $info + [
            'type'   => 'bar',
            'data'   => array_values($this->data),
            'colors' => $this->toArrayColors(),
        ];
    }
}
