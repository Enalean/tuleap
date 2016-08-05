<?php
/**
 * Copyright (c) Enalean, 2016. All rights reserved
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
 
class HudsonJob {
    const API_XML = '/api/xml';

    protected $hudson_job_url;
    protected $hudson_dobuild_url;
    protected $dom_job;
    private $icons_path;
    /**
     * @var Http_Client
     */
    private $http_client;
    
    /**
     * Construct an Hudson job from a job URL
     */
    public function __construct($hudson_job_url, Http_Client $http_client, $name = null)
    {
        $parsed_url = parse_url($hudson_job_url);
        
        if ( ! $parsed_url || ! array_key_exists('scheme', $parsed_url) ) {
            throw new HudsonJobURLMalformedException($GLOBALS['Language']->getText('plugin_hudson','wrong_job_url', array($hudson_job_url)));
        }

        $this->setJobUrl($hudson_job_url);

        $this->name        = $name;
        $controler         = $this->getHudsonControler();
        $this->icons_path  = $controler->getIconsPath();
        $this->http_client = $http_client;
    }

    private function setJobUrl($url) {
        $matches = array();
        if (preg_match(Jenkins_Client::BUILD_WITH_PARAMETERS_REGEXP, $url, $matches)) {
            $this->hudson_job_url     = $matches['job_url'] . self::API_XML;
            $this->hudson_dobuild_url = $url;
        } else {
            $this->hudson_job_url     = $url . self::API_XML;
            $this->hudson_dobuild_url = $url . "/build";
        }
    }

    public function getJobUrl() {
        return $this->hudson_job_url;
    }

    public function getDoBuildUrl() {
        return $this->hudson_dobuild_url;
    }

    function getHudsonControler() {
        return new hudson();
    }

    protected function getDomJob() {
        if (!$this->dom_job) {
            $this->buildJobObject();
        }
        return $this->dom_job;
    }

    public function buildJobObject() {
         $this->dom_job = $this->_getXMLObject($this->hudson_job_url);
    }
    
    protected function _getXMLObject($hudson_job_url)
    {
        $this->http_client->setOption(CURLOPT_URL, $hudson_job_url);
        $this->http_client->doRequest();

        $xmlstr = $this->http_client->getLastResponse();
        if ($xmlstr !== false) {
            $xmlobj = simplexml_load_string($xmlstr);
            if ($xmlobj !== false) {
                return $xmlobj;
            } else {
                throw new HudsonJobURLFileException($GLOBALS['Language']->getText('plugin_hudson', 'job_url_file_error',
                    array($hudson_job_url)));
            }
        } else {
            throw new HudsonJobURLFileNotFoundException($GLOBALS['Language']->getText('plugin_hudson',
                'job_url_file_not_found', array($hudson_job_url)));
        }
    }
    
    function getProjectStyle() {
        return $this->getDomJob()->getName();
    }
    function getName() {
        try {
            if (!$this->name) {
                $this->name = $this->getDomJob()->name;
            }
        } catch (Exception $e) {

        }
        return $this->name;
    }
    function getUrl() {
        return $this->getDomJob()->url;
    }
    function getColor() {
        return $this->getDomJob()->color;
    }
    function getColorNoAnime() {
        $color = $this->getColor();
        if (strpos($color, "_anime")) {
            $color = substr($color, 0, strpos($color, "_anime"));
        }
        return $color;
    }
    function getStatus() {
        switch ($this->getColor()) {
            case "blue":
                // The last build was successful.
                return $GLOBALS['Language']->getText('plugin_hudson','status_blue');
                break;
            case "blue_anime":
                // The last build was successful. A new build is in progress.
                return $GLOBALS['Language']->getText('plugin_hudson','status_blue_anime');
                break;
            case "yellow":
                // The last build was successful but unstable. This is primarily used to represent test failures.
                return $GLOBALS['Language']->getText('plugin_hudson','status_yellow'); 
                break;
            case "yellow_anime":
                // The last build was successful but unstable. This is primarily used to represent test failures. A new build is in progress.
                return $GLOBALS['Language']->getText('plugin_hudson','status_yellow_anime'); 
                break;
            case "red":
                // The last build fatally failed.
                return $GLOBALS['Language']->getText('plugin_hudson','status_red');
                break;
            case "red_anime":
                // The last build fatally failed. A new build is in progress.
                return $GLOBALS['Language']->getText('plugin_hudson','status_red_anime');
                break;
            case "grey":
                // The project has never been built before, or the project is disabled.
                return $GLOBALS['Language']->getText('plugin_hudson','status_grey');
                break;
            case "grey_anime":
                // The project has never been built before, or the project is disabled. The first build of this project is in progress.
                return $GLOBALS['Language']->getText('plugin_hudson','status_grey_anime');
                break;
            default:
                // Can we have anime icons here?
                return $GLOBALS['Language']->getText('plugin_hudson','status_unknown');
                break;
        }
    }
    
    function getIconsPath() {
        return $this->icons_path;
    }
    function getStatusIcon() {
        switch ($this->getColor()) {
            case "blue":
                // The last build was successful.
                return $this->getIconsPath()."status_blue.png";
                break;
            case "blue_anime":
                // The last build was successful. A new build is in progress.
                return $this->getIconsPath()."status_blue.png";
                break;
            case "yellow":
                // The last build was successful but unstable. This is primarily used to represent test failures.
                return $this->getIconsPath()."status_yellow.png"; 
                break;
            case "yellow_anime":
                // The last build was successful but unstable. A new build is in progress.
                return $this->getIconsPath()."status_yellow.png";
                break;
            case "red":
                // The last build fatally failed.
                return $this->getIconsPath()."status_red.png";
                break;
            case "red_anime":
                // The last build fatally failed. A new build is in progress.
                return $this->getIconsPath()."status_red.png";
                break;
            case "grey":
                // The project has never been built before, or the project is disabled.
                return $this->getIconsPath()."status_grey.png";
                break;
            case "grey_anime":
                // The first build of the project is in progress.
                return $this->getIconsPath()."status_grey.png";
                break;
            default:
                // Can we have anime icons here?
                return $this->getIconsPath()."status_unknown.png";
                break;
        }
    }
    
    function isBuildable() {
        return ($this->getDomJob()->buildable == "true");
    }
    
    function hasBuilds() {
        return ((int)$this->getLastBuildNumber() !== 0); 
    }
    
    function getLastBuildNumber() {
        return $this->getDomJob()->lastBuild->number;
    }
    function getLastBuildUrl() {
        return $this->getDomJob()->lastBuild->url;
    }
    
    function getLastSuccessfulBuildNumber() {
        return $this->getDomJob()->lastSuccessfulBuild->number;
    }
    function getLastSuccessfulBuildUrl() {
        return $this->getDomJob()->lastSuccessfulBuild->url;
    }
    
    function getLastFailedBuildNumber() {
        return $this->getDomJob()->lastFailedBuild->number;
    }
    function getLastFailedBuildUrl() {
        return $this->getDomJob()->lastFailedBuild->url;
    }
    
    function getNextBuildNumber() {
        return $this->getDomJob()->nextBuildNumber;
    }
    
    function getHealthScores() {
        $scores = array();
        foreach ($this->getDomJob()->healthReport as $health_report) {
            $scores[] = $health_report->score;
        }
        return $scores;
    }
    function getHealthDescriptions() {
        $descs = array();
        foreach ($this->getDomJob()->healthReport as $health_report) {
            $scores[] = $health_report->description;
        }
        return $descs;
    }
    function getHealthAverageScore() {
        $arr = $this->getHealthScores();
        $sum = 0;
        foreach ($arr as $score) {
            $sum += (int)$score;
        }
        $num = sizeof($arr);
        if ($num != 0) {
            return floor($sum/$num);
        } else {
            return 0;
        }
    }
    
    function getWeatherReportIcon() {
        $score = $this->getHealthAverageScore();
        if ($score >= 80) {
            return $this->getIconsPath()."health_80_plus.gif";
        } elseif ($score >= 60) {
            return $this->getIconsPath()."health_60_to_79.gif";
        } elseif ($score >= 40) {
            return $this->getIconsPath()."health_40_to_59.gif";
        } elseif ($score >= 20) {
            return $this->getIconsPath()."health_20_to_39.gif";
        } else {
            return $this->getIconsPath()."health_00_to_19.gif";
        }
    }
}
