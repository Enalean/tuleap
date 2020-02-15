<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

abstract class ChartDataBuilderV5
{

    protected $chart;
    protected $artifacts;

    public function __construct($chart, $artifacts)
    {
        $this->chart     = $chart;
        $this->artifacts = $artifacts;
    }

    public function buildProperties($engine)
    {
        $engine->title       = $this->chart->getTitle();
        $engine->description = $this->chart->getDescription();
        $engine->height      = $this->chart->getHeight();
        $engine->width       = $this->chart->getWidth();
    }

    /**
     * @return string or array (r,g,b) color from $data if exist, else a null triple
     */
    protected function getColor(array $data)
    {
        if (isset($data['tlp_color_name'])) {
            return $data['tlp_color_name'];
        }

        if (! isset($data['red'])) {
            return array(null, null, null);
        }

        return array($data['red'], $data['green'], $data['blue']);
    }

    protected function getTracker()
    {
        return TrackerFactory::instance()->getTrackerById($this->chart->renderer->report->tracker_id);
    }

    protected function displayNoFieldError()
    {
        $error_message = $GLOBALS['Language']->getText(
            'plugin_graphontrackersv5',
            'field_not_found',
            $this->chart->getTitle()
        );
        echo "<p class='feedback_error'>$error_message</p>";
    }
}
