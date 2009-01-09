<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 *
 * HudsonBuild
 */

require_once('hudson.class.php');
require_once('HudsonJobURLMalformedException.class.php');
require_once('HudsonJobURLFileException.class.php');
require_once('HudsonJobURLFileNotFoundException.class.php');
 
class HudsonBuild {

    protected $hudson_build_url;
    protected $dom_build;
        
    /**
     * Construct an Hudson build from a build URL
     */
    function HudsonBuild($hudson_build_url) {
        $parsed_url = parse_url($hudson_build_url);
        
        if ( ! $parsed_url || ! array_key_exists('scheme', $parsed_url) ) {
            throw new HudsonJobURLMalformedException($GLOBALS['Language']->getText('plugin_hudson','wrong_job_url', array($hudson_build_url)));
        }
                
        $this->hudson_build_url = $hudson_build_url . "/api/xml";
        
        $this->buildBuildObject();
        
    }
    
    public function buildBuildObject() {
        $this->dom_build = $this->_getXMLObject($this->hudson_build_url);
    }
    
    protected function _getXMLObject($hudson_build_url) {
        if (@file_get_contents($hudson_build_url) !== false) {
            $xmlobj = simplexml_load_file($hudson_build_url);
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
}

?>