<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */


/**
 * Graphic engine which builds a graph
 */
abstract class GraphOnTrackersV5_Engine {
    
    public $graph;
    public $data;
    /** @var array */
    public $colors;
    
    /**
     * @return boolean true if the data are valid to buid the chart
     */
    public function validData() {
        if (count($this->data) > 0) {
            return true;
        }else{
            echo ' <p class="feedback_info">';
            echo $GLOBALS['Language']->getText('plugin_graphontrackersv5_engine','no_datas',array($this->title));
            echo '</p>';
            return false;
        }
    }

    /**
     * @return array of hexa colors
     */
    protected function getColors() {
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
    private function fillTheBlanks($color, $available_colors, $max_colors, &$i) {
        if ($this->isColorUndefined($color)) {
            return $available_colors[$i++ % $max_colors];
        }
        return $this->getHexaColor($color);
    }

    /** @return bool */
    private function isColorUndefined($color) {
        return $color[0] == NULL || $color[1] == NULL || $color[2] == NULL;
    }

    /** @return string hexadecimal representation of the color */
    private function getHexaColor($color) {
        return ColorHelper::RGBToHexa($color[0], $color[1], $color[2]);
    }

    /**
     * Build graph based on data, title, description given to the engine
     */
    abstract public function buildGraph();
}
?>
