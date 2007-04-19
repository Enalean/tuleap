<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 * @version 
 *
 * SVNUpdate
 */

//$Language->loadLanguageMsg('svnupdate/svnupdate');

require_once("UpgradeScript.class.php");

class SVNCommitedFile {
    
    /**
     * @var string $action the action done by svn (A for Added, D for Deleted, M for Modified)
     */
    var $action;
    
    /**
     * @var string $path path of the commited file 
     */
    var $path;
    
    /**
     * SVNCommit constructor
     */
    function SVNCommitedFile() {
    }
    
    function getAction() {
        return $this->action;
    }
    function setAction($action) {
        $this->action = $action;
    }
    function getPath() {
        return $this->path;
    }
    function setPath($path) {
        $this->path = $path;
    }
    
    function showSpecials() {
        return "";
    }

}

?>
