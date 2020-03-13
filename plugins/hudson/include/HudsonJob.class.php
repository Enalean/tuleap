<?php
/**
 * Copyright (c) Enalean, 2016-2018. All rights reserved
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

class HudsonJob
{
    /**
     * @var null|string
     */
    private $name;
    /**
     * @var SimpleXMLElement
     */
    private $xml_content;

    public function __construct($name, SimpleXMLElement $xml_content)
    {
        $this->name        = $name;
        $this->xml_content = $xml_content;
    }

    public function getName()
    {
        if (! $this->name && isset($this->xml_content->name)) {
            $this->name = (string) $this->xml_content->name;
        }
        return $this->name;
    }

    public function getUrl()
    {
        if (isset($this->xml_content->url)) {
            return (string) $this->xml_content->url;
        }
        return '';
    }

    private function getColor()
    {
        if (isset($this->xml_content->color)) {
            return (string) $this->xml_content->color;
        }
        return '';
    }

    public function getStatus()
    {
        switch ($this->getColor()) {
            case "blue":
                // The last build was successful.
                return $GLOBALS['Language']->getText('plugin_hudson', 'status_blue');
                break;
            case "blue_anime":
                // The last build was successful. A new build is in progress.
                return $GLOBALS['Language']->getText('plugin_hudson', 'status_blue_anime');
                break;
            case "yellow":
                // The last build was successful but unstable. This is primarily used to represent test failures.
                return $GLOBALS['Language']->getText('plugin_hudson', 'status_yellow');
                break;
            case "yellow_anime":
                // The last build was successful but unstable. This is primarily used to represent test failures. A new build is in progress.
                return $GLOBALS['Language']->getText('plugin_hudson', 'status_yellow_anime');
                break;
            case "red":
                // The last build fatally failed.
                return $GLOBALS['Language']->getText('plugin_hudson', 'status_red');
                break;
            case "red_anime":
                // The last build fatally failed. A new build is in progress.
                return $GLOBALS['Language']->getText('plugin_hudson', 'status_red_anime');
                break;
            case "grey":
                // The project has never been built before, or the project is disabled.
                return $GLOBALS['Language']->getText('plugin_hudson', 'status_grey');
                break;
            case "grey_anime":
                // The project has never been built before, or the project is disabled. The first build of this project is in progress.
                return $GLOBALS['Language']->getText('plugin_hudson', 'status_grey_anime');
                break;
            default:
                // Can we have anime icons here?
                return $GLOBALS['Language']->getText('plugin_hudson', 'status_unknown');
                break;
        }
    }

    public function getStatusIcon()
    {
        switch ($this->getColor()) {
            case "blue":
                // The last build was successful.
                return hudsonPlugin::ICONS_PATH . "status_blue.png";
                break;
            case "blue_anime":
                // The last build was successful. A new build is in progress.
                return hudsonPlugin::ICONS_PATH . "status_blue.png";
                break;
            case "yellow":
                // The last build was successful but unstable. This is primarily used to represent test failures.
                return hudsonPlugin::ICONS_PATH . "status_yellow.png";
                break;
            case "yellow_anime":
                // The last build was successful but unstable. A new build is in progress.
                return hudsonPlugin::ICONS_PATH . "status_yellow.png";
                break;
            case "red":
                // The last build fatally failed.
                return hudsonPlugin::ICONS_PATH . "status_red.png";
                break;
            case "red_anime":
                // The last build fatally failed. A new build is in progress.
                return hudsonPlugin::ICONS_PATH . "status_red.png";
                break;
            case "grey":
                // The project has never been built before, or the project is disabled.
                return hudsonPlugin::ICONS_PATH . "status_grey.png";
                break;
            case "grey_anime":
                // The first build of the project is in progress.
                return hudsonPlugin::ICONS_PATH . "status_grey.png";
                break;
            default:
                // Can we have anime icons here?
                return hudsonPlugin::ICONS_PATH . "status_unknown.png";
                break;
        }
    }

    public function hasBuilds()
    {
        return $this->getLastBuildNumber() !== 0;
    }

    public function getLastBuildNumber()
    {
        if ($this->xml_content->lastBuild->number) {
            return (int) $this->xml_content->lastBuild->number;
        }
        return 0;
    }

    public function getLastSuccessfulBuildNumber()
    {
        if (isset($this->xml_content->lastSuccessfulBuild->number)) {
            return (int) $this->xml_content->lastSuccessfulBuild->number;
        }
        return 0;
    }

    public function getLastSuccessfulBuildUrl()
    {
        if (isset($this->xml_content->lastSuccessfulBuild->url)) {
            return (string) $this->xml_content->lastSuccessfulBuild->url;
        }
        return '';
    }

    public function getLastFailedBuildNumber()
    {
        if ($this->xml_content !== null) {
            return (int) $this->xml_content->lastFailedBuild->number;
        }
        return 0;
    }

    public function getLastFailedBuildUrl()
    {
        if (isset($this->xml_content->lastFailedBuild->url)) {
            return (string) $this->xml_content->lastFailedBuild->url;
        }
        return '';
    }

    private function getHealthScores()
    {
        if (! isset($this->xml_content->healthReport)) {
            return [];
        }
        $scores = array();
        foreach ($this->xml_content->healthReport as $health_report) {
            if (isset($health_report->score)) {
                $scores[] = (int) $health_report->score;
            }
        }
        return $scores;
    }

    private function getHealthAverageScore()
    {
        $health_scores = $this->getHealthScores();
        if (count($health_scores) <= 0) {
            return 0;
        }
        return floor(array_sum($health_scores) / count($health_scores));
    }

    public function getWeatherReportIcon()
    {
        $score = $this->getHealthAverageScore();
        if ($score >= 80) {
            return hudsonPlugin::ICONS_PATH . "health_80_plus.gif";
        } elseif ($score >= 60) {
            return hudsonPlugin::ICONS_PATH . "health_60_to_79.gif";
        } elseif ($score >= 40) {
            return hudsonPlugin::ICONS_PATH . "health_40_to_59.gif";
        } elseif ($score >= 20) {
            return hudsonPlugin::ICONS_PATH . "health_20_to_39.gif";
        } else {
            return hudsonPlugin::ICONS_PATH . "health_00_to_19.gif";
        }
    }
}
