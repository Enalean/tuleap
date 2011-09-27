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
require_once('hudson.class.php');
require_once('HudsonJobURLMalformedException.class.php');
require_once('HudsonJobURLFileException.class.php');
require_once('HudsonJobURLFileNotFoundException.class.php');
 
class HudsonBuild {

    protected $hudson_build_url;
    protected $dom_build;
    
    private $context;
        
    /**
     * Construct an Hudson build from a build URL
     */
    function HudsonBuild($hudson_build_url) {
        $parsed_url = parse_url($hudson_build_url);
        
        if ( ! $parsed_url || ! array_key_exists('scheme', $parsed_url) ) {
            throw new HudsonJobURLMalformedException($GLOBALS['Language']->getText('plugin_hudson','wrong_job_url', array($hudson_build_url)));
        }
                
        $this->hudson_build_url = $hudson_build_url . "/api/xml";
        
        $this->_setStreamContext();
        
        $this->buildBuildObject();
        
        $controler = $this->getHudsonControler(); 
        $this->icons_path = $controler->getIconsPath();
    }
    function getHudsonControler() {
        return new hudson();
    }
    
    public function buildBuildObject() {
        $this->dom_build = $this->_getXMLObject($this->hudson_build_url);
    }
    
    protected function _getXMLObject($hudson_build_url) {
        $xmlstr = @file_get_contents($hudson_build_url, false, $this->context);
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
    
    private function _setStreamContext() {
        if (array_key_exists('sys_proxy', $GLOBALS) && $GLOBALS['sys_proxy']) {
            $context_opt = array(
                'http' => array(
                    'method' => 'GET',
                    'proxy' => $GLOBALS['sys_proxy'],
                    'request_fulluri' => True,
                    'timeout' => 5.0,
                ),
            );
            $this->context = stream_context_create($context_opt);
        } else {
            $this->context = null;
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
        return $this->dom_build->url;
    }
    function getResult() {
        return $this->dom_build->result;
    }
    function getNumber() {
        return $this->dom_build->number;
    }
    function getDuration() {
        return $this->dom_build->duration;
    }
    function getTimestamp() {
        return $this->dom_build->timestamp;
    }
    function getBuildTime() {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt'), substr($this->getTimestamp(), 0, -3));
    }
    function getStatusIcon() {
        if ($this->getResult() == 'SUCCESS') {
            return $this->icons_path .'status_blue.png';
        }
        return $this->icons_path .'status_red.png';
    }
}

?>