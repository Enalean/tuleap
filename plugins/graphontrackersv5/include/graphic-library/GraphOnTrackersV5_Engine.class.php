<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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


/**
 * Graphic engine which builds a graph
 */
abstract class GraphOnTrackersV5_Engine
{

    public $graph;
    public $data;
    /** @var array */
    public $colors;

    /**
     * @return bool true if the data are valid to buid the chart
     */
    public function validData()
    {
        if (count($this->data) > 0) {
            return true;
        } else {
            $GLOBALS['Response']->addFeedback(
                'info',
                sprintf(dgettext('tuleap-graphontrackersv5', 'No datas to display for graph %1$s'), $this->title)
            );

            return false;
        }
    }

    /**
     * @return array of hexa colors
     */
    protected function getColors()
    {
        $available_colors = $this->graph->getThemedColors();
        $max_colors       = count($available_colors);
        $i = 0;
        foreach ($this->colors as $group => $color) {
            $this->colors[$group] = $this->fillTheBlanks($color, $available_colors, $max_colors, $i);
        }

        return $this->colors;
    }

    /**
     * If the given color is undefined(null), then we must take one from the current
     * theme (in available_colors).
     *
     * @return string hexadecimal representation of the color
     */
    private function fillTheBlanks($color, $available_colors, $max_colors, &$i)
    {
        if ($this->isColorUndefined($color)) {
            return $available_colors[$i++ % $max_colors];
        }
        return $this->getHexaColor($color);
    }

    /** @return bool */
    private function isColorUndefined($color)
    {
        return $color[0] === null || $color[1] === null || $color[2] === null;
    }

    /** @return string hexadecimal representation of the color */
    private function getHexaColor($color)
    {
        return ColorHelper::RGBToHexa($color[0], $color[1], $color[2]);
    }

    /**
     * Build graph based on data, title, description given to the engine
     */
    abstract public function buildGraph();

    /**
     * Return public data as Array (meant to be transformed into Json)
     * @return array
     */
    public function toArray()
    {
        return [
            'colors' => $this->toArrayColors(),
        ];
    }

    protected function toArrayColors()
    {
        return is_array($this->colors) ? $this->getArrayColors() : null;
    }

    private function getArrayColors()
    {
        $colors = [];
        foreach ($this->colors as $color) {
            $colors[] = $this->getColorOrNull($color);
        }
        return $colors;
    }

    protected function getColorOrNull($color)
    {
        if ($this->isColorATLPColor($color)) {
            return $color;
        }

        if (! $this->isColorUndefined($color)) {
            return $this->getHexaColor($color);
        }
        return null;
    }

    /**
     * @param $color
     * @return bool
     */
    private function isColorATLPColor($color)
    {
        return ! is_array($color) && strpos($color, '-') > 0;
    }
}
