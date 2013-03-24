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

abstract class ChartDataBuilderV5 {
    
    protected $chart;
    protected $artifacts;
    
    function __construct($chart, $artifacts) {
        $this->chart     = $chart;
        $this->artifacts = $artifacts;
    }
    
    function buildProperties($engine) {
        $engine->title       = $this->chart->getTitle();
        $engine->description = $this->chart->getDescription();
        $engine->height      = $this->chart->getHeight();
        $engine->width       = $this->chart->getWidth();
    }

    /**
     * @return array (r,g,b) color from $data if exist, else a null triple
     */
    protected function getColor(array $data) {
        if (! isset($data['red'])) {
            return array(null, null, null);
        }

        return array($data['red'], $data['green'], $data['blue']);
    }
}
?>
