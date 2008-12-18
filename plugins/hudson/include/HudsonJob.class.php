<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX, Codendi 2007-2008.
 *
 * This file is licensed under the GNU General Public License version 2. See the file COPYING.
 * 
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 *
 * HudsonJob
 */

require_once('hudson.class.php');
require_once('HudsonJobURLMalformedException.class.php');
require_once('HudsonJobURLFileException.class.php');
require_once('HudsonJobURLFileNotFoundException.class.php');
 
class HudsonJob {

    protected $hudson_job_url;
    protected $dom_job;
    private $icons_path;
    
    protected $projectStyle;
    protected $name;
    
    /**
     * Construct an Hudson job from a job URL
     */
    function HudsonJob($hudson_job_url) {
        $parsed_url = parse_url($hudson_job_url);
        
        if ( ! $parsed_url || ! array_key_exists('scheme', $parsed_url) ) {
            throw new HudsonJobURLMalformedException($GLOBALS['Language']->getText('plugin_hudson','wrong_job_url', array($hudson_job_url)));
        }
                
        $this->hudson_job_url = $hudson_job_url . "/api/xml";
        
        $controler = $this->getHudsonControler(); 
        $this->icons_path = $controler->getIconsPath();
        
        $this->buildJobObject();
        
    }
    function getHudsonControler() {
        return new hudson();
    }
    
    public function buildJobObject() {
        $this->dom_job = $this->_getXMLObject($this->hudson_job_url);
    }
    
    protected function _getXMLObject($hudson_job_url) {
        if (@file_get_contents($hudson_job_url) !== false) {
            $xmlobj = simplexml_load_file($hudson_job_url);
            if ($xmlobj !== false) {
                return $xmlobj;
            } else {
                throw new HudsonJobURLFileException($GLOBALS['Language']->getText('plugin_hudson','job_url_file_error', array($hudson_job_url)));
            }
        } else {
            throw new HudsonJobURLFileNotFoundException($GLOBALS['Language']->getText('plugin_hudson','job_url_file_not_found', array($hudson_job_url))); 
        }
    }
    
    function getProjectStyle() {
        return $this->dom_job->getName();
    }
    function getName() {
        return $this->dom_job->name;
    }
    function getUrl() {
        return $this->dom_job->url;
    }
    function getColor() {
        return $this->dom_job->color;
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
        return ($this->dom_job->buildable == "true");
    }
    function getLastSuccessfulBuildNumber() {
        return $this->dom_job->lastSuccessfulBuild->number;
    }
    function getLastSuccessfulBuildUrl() {
        return $this->dom_job->lastSuccessfulBuild->url;
    }
    
    function getLastFailedBuildNumber() {
        return $this->dom_job->lastFailedBuild->number;
    }
    function getLastFailedBuildUrl() {
        return $this->dom_job->lastFailedBuild->url;
    }
    
}

?>