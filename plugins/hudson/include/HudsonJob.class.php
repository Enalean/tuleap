<?php
/**
 * @copyright Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * HudsonJob
 */

require_once('hudson.class.php');
//require_once('PluginFactory.class.php');
require_once('HudsonJobURLMalformedException.class.php');
 
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
        
        if ( ! $parsed_url) {
            throw new HudsonJobURLMalformedException($GLOBALS['Language']->getText('plugin_hudson','malformed_url'));
        }
        if ( ! array_key_exists('scheme', $parsed_url)) {
            throw new HudsonJobURLMalformedException($GLOBALS['Language']->getText('plugin_hudson','malformed_url_scheme'));
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
        return simplexml_load_file($hudson_job_url);
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