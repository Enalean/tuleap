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
 
class HudsonBuild {

    protected $hudson_build_url;

    /**
     * @var SimpleXMLElement
     */
    protected $dom_build;
    /**
     * @var Http_Client
     */
    private $http_client;
    /**
     * Construct an Hudson build from a build URL
     */
    public function __construct($hudson_build_url, Http_Client $http_client)
    {
        $parsed_url = parse_url($hudson_build_url);
        
        if ( ! $parsed_url || ! array_key_exists('scheme', $parsed_url) ) {
            throw new HudsonJobURLMalformedException($GLOBALS['Language']->getText('plugin_hudson','wrong_job_url', array($hudson_build_url)));
        }
                
        $this->hudson_build_url = $hudson_build_url . "/api/xml";
        $this->http_client      = $http_client;

        $this->dom_build = $this->_getXMLObject($this->hudson_build_url);
    }
    
    protected function _getXMLObject($hudson_build_url)
    {
        $this->http_client->setOption(CURLOPT_URL, $hudson_build_url);
        $this->http_client->doRequest();

        $xmlstr = $this->http_client->getLastResponse();
        if ($xmlstr !== false) {
            $xmlobj = simplexml_load_string($xmlstr);
            if ($xmlobj !== false) {
                return $xmlobj;
            } else {
                throw new HudsonJobURLFileException($GLOBALS['Language']->getText('plugin_hudson','job_url_file_error', array($hudson_build_url)));
            }
        } else {
            throw new HudsonJobURLFileNotFoundException($GLOBALS['Language']->getText('plugin_hudson','job_url_file_not_found', array($hudson_build_url)));
        }
    }
    
    function getDom() {
        return $this->dom_build;
    }
    
    function getBuildStyle() {
        return $this->dom_build->getName();
    }

    function isBuilding() {
        return ($this->dom_build->building == "true");
    }

    function getUrl() {
        return (string) $this->dom_build->url;
    }

    function getResult() {
        return (string) $this->dom_build->result;
    }

    function getNumber() {
        return (int) $this->dom_build->number;
    }

    function getDuration() {
        return (int) $this->dom_build->duration;
    }

    function getTimestamp() {
        return (int) $this->dom_build->timestamp;
    }

    function getBuildTime() {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt'), substr($this->getTimestamp(), 0, -3));
    }

    function getStatusIcon() {
        $color = 'red';
        if ($this->getResult() == 'SUCCESS') {
            $color = 'blue';
        } else if ($this->getResult() == 'UNSTABLE') {
            $color = 'yellow';
        }
        return hudsonPlugin::ICONS_PATH .'status_'. $color .'.png';
    }
}
