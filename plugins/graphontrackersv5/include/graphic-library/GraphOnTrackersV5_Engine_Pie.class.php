<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class GraphOnTrackersV5_Engine_Pie extends GraphOnTrackersV5_Engine
{

    public $title;
    public $field_base;
    public $height;
    public $width;
    public $size_pie;
    public $legend;


    public function validData()
    {
        if ((is_array($this->data)) && (array_sum($this->data) > 0)) {
            return true;
        } else {
            $GLOBALS['Response']->addFeedback(
                'error',
                $GLOBALS['Language']->getText('plugin_graphontrackersv5_engine', 'no_datas', array($this->title))
            );

            return false;
        }
    }

    /**
     * Builds pie graph
     */
    public function buildGraph()
    {
        $this->graph = new Chart_Pie($this->width, $this->height);

        // title setup
        $this->graph->title->Set($this->title);

        if (is_null($this->description)) {
            $this->description = "";
        }
        $this->graph->subtitle->Set($this->description);

        $colors = $this->getColors();

        if ((is_array($this->data)) && (array_sum($this->data) > 0)) {
            $p = new PiePlot($this->data);

            $p->setSliceColors($colors);

            $p->SetCenter(0.4, 0.6);
            $p->SetLegends($this->legend);

            $p->value->HideZero();
            $p->value->SetFont($this->graph->getFont(), FS_NORMAL, 8);
            $p->value->SetColor($this->graph->getMainColor());
            $p->value->SetMargin(0);

            $this->graph->Add($p);
        }
        return $this->graph;
    }

    public function toArray()
    {
        return parent::toArray() + array(
            'type'   => 'pie',
            'title'  => $this->title,
            'height' => $this->height,
            'width'  => $this->width,
            'legend' => $this->legend,
            'data'   => $this->data,
        );
    }
}
