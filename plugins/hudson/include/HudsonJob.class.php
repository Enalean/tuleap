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
    const API_XML    = '/api/xml';

    private $hudson_job_url;
    private $hudson_dobuild_url;
    private $dom_job;
    /**
     * @var Http_Client
     */
    private $http_client;
    /**
     * @var null|string
     */
    private $name;

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

    protected function getDomJob() {
        if (!$this->dom_job) {
            $this->buildJobObject();
        }
        return $this->dom_job;
    }

    private function buildJobObject()
    {
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

    public function getName()
    {
        try {
            if ($this->name === null) {
                $this->name = $this->getDomJob()->name;
            }
        } catch (Exception $e) {

        }
        return $this->name;
    }

    public function getUrl()
    {
        return $this->getDomJob()->url;
    }

    private function getColor()
    {
        return $this->getDomJob()->color;
    }

    public function getStatus()
    {
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

    public function getStatusIcon()
    {
        switch ($this->getColor()) {
            case "blue":
                // The last build was successful.
                return hudsonPlugin::ICONS_PATH."status_blue.png";
                break;
            case "blue_anime":
                // The last build was successful. A new build is in progress.
                return hudsonPlugin::ICONS_PATH."status_blue.png";
                break;
            case "yellow":
                // The last build was successful but unstable. This is primarily used to represent test failures.
                return hudsonPlugin::ICONS_PATH."status_yellow.png";
                break;
            case "yellow_anime":
                // The last build was successful but unstable. A new build is in progress.
                return hudsonPlugin::ICONS_PATH."status_yellow.png";
                break;
            case "red":
                // The last build fatally failed.
                return hudsonPlugin::ICONS_PATH."status_red.png";
                break;
            case "red_anime":
                // The last build fatally failed. A new build is in progress.
                return hudsonPlugin::ICONS_PATH."status_red.png";
                break;
            case "grey":
                // The project has never been built before, or the project is disabled.
                return hudsonPlugin::ICONS_PATH."status_grey.png";
                break;
            case "grey_anime":
                // The first build of the project is in progress.
                return hudsonPlugin::ICONS_PATH."status_grey.png";
                break;
            default:
                // Can we have anime icons here?
                return hudsonPlugin::ICONS_PATH."status_unknown.png";
                break;
        }
    }
    
    public function hasBuilds()
    {
        return ((int)$this->getLastBuildNumber() !== 0); 
    }
    
    public function getLastBuildNumber()
    {
        return $this->getDomJob()->lastBuild->number;
    }
    
    public function getLastSuccessfulBuildNumber()
    {
        return $this->getDomJob()->lastSuccessfulBuild->number;
    }

    public function getLastSuccessfulBuildUrl()
    {
        return $this->getDomJob()->lastSuccessfulBuild->url;
    }
    
    public function getLastFailedBuildNumber()
    {
        return $this->getDomJob()->lastFailedBuild->number;
    }

    public function getLastFailedBuildUrl()
    {
        return $this->getDomJob()->lastFailedBuild->url;
    }
    
    private function getHealthScores()
    {
        $scores = array();
        foreach ($this->getDomJob()->healthReport as $health_report) {
            $scores[] = $health_report->score;
        }
        return $scores;
    }

    private function getHealthAverageScore()
    {
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
    
    public function getWeatherReportIcon()
    {
        $score = $this->getHealthAverageScore();
        if ($score >= 80) {
            return hudsonPlugin::ICONS_PATH."health_80_plus.gif";
        } elseif ($score >= 60) {
            return hudsonPlugin::ICONS_PATH."health_60_to_79.gif";
        } elseif ($score >= 40) {
            return hudsonPlugin::ICONS_PATH."health_40_to_59.gif";
        } elseif ($score >= 20) {
            return hudsonPlugin::ICONS_PATH."health_20_to_39.gif";
        } else {
            return hudsonPlugin::ICONS_PATH."health_00_to_19.gif";
        }
    }
}
