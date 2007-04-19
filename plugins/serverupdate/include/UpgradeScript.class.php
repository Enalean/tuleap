<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 * @version $Id: SVNCommit.class.php 2658 2006-04-11 14:36:30Z mnazaria $
 *
 * Upgrade
 */

//$Language->loadLanguageMsg('svnupdate/svnupdate');


require_once("UpgradeScriptExecution.class.php");

class UpgradeScript extends SVNCommitedFile {
    
    /**
     * @var string $_classname the name of the class representing the script
     */
    var $_classname;
    /**
    * @var array $_executions array of {UpgradeScriptExecution}
     */
    var $_executions;
    var $branch;
    
    /**
     * Upgrade constructor
     */
    function UpgradeScript($branch) {
        $this->branch = $branch;
    }
    
    function getClassname() {
        return $this->_classname;
    }
    function setClassname($classname) {
        $this->_classname = $classname;
    }
    function getExecutions() {
        return $this->_executions;
    }
    function addExecution($execution) {
        $this->_executions[] = $execution;
    }
    function setExecutions($executions) {
        $this->_executions = $executions;
    }
    
    
    /**
     * Returns the name of the class regarding the name of the file
     *
     * @static
     * @param string $file the name of the file (name + extension)
     * @return string the name of the class
     */
    function className($file) {
        //return substr($file, 0, (strlen($file) - 6));
        return 'Update_'. substr(basename($file, '.class.php'), 0, 3);
    }
    
    /**
     * Returns the name of the class regarding the name of the file
     *
     * @static
     * @param string $file the name of the file (name + extension)
     * @return string the name of the class
     */
    function getClassNameFromPath($path) {
        $file = basename($path);
        return UpgradeScript::className($file);
    }
    
    function hasBeenSuccessfullyApplied() {
        $successfullyApplied = false;
        foreach ($this->getExecutions() as $exec) {
            if ($exec->getSuccessfullyApplied() == 1) {
                $successfullyApplied = true;
            }
        }
        return $successfullyApplied;
    }
    
    
    function _setAllExecutions() {
        $executions = array();
        $sql = "SELECT * FROM plugin_serverupdate_upgrade WHERE script = '".$this->getClassname()."' ORDER BY DATE ASC";
        $resource = db_query($sql);
        while ($exec = db_fetch_array($resource)) {
            $current_exec = new UpgradeScriptExecution($exec['script']);
            $current_exec->setDate($exec['date']);
            $current_exec->setExecutionMode($exec['execution_mode']);
            $current_exec->setSuccessfullyApplied(($exec['success'] == 1));
            $current_exec->setErrors($exec['error']);
            $executions[] = $current_exec;
        }
        $this->setExecutions($executions);
    }
    
    
    function isWellImplemented() {
        $is_ok = false;
        // Check if the file is a class file that extends CodeXUpgrade
        $classname = $this->getClassname();
        
        include_once($GLOBALS['codex_dir'].substr($this->getPath(), strlen($this->branch)));
        
        $obj = new $classname();
        if (is_a($obj, 'CodeXUpgrade')) {
            // Check if the method _process is implemented
            if (method_exists($obj, '_process')) {
                $is_ok = true;
            }
        }
        return $is_ok;
    }
    
    /**
     * @static
     */
    function isInScriptDirectory($branch, $path) {
        // Check if the file is located in the script directory
        if (substr($path, 0, strlen($branch.UPGRADE_SCRIPT_PATH)) == $branch.UPGRADE_SCRIPT_PATH) {
            return true;
        }
        return false;
    }
    
    /**
    * @static
    */
    function isTheGenericScript($branch, $path) {
        // Check if the file is located in the script directory
        if (! UpgradeScript::isInScriptDirectory($branch, $path)) {
            return false;
        }
        if (basename($path) == 'CodeXUpgrade.class.php') {
            return true;
        } else {
            return false;
        }
    }

    function showSpecials($iconsPath) {
        return '<img src="'.$iconsPath.'script_update.png" title="'.$GLOBALS['Language']->getText('plugin_serverupdate_script','Script_Upgrades').'" alt="'.$GLOBALS['Language']->getText('plugin_serverupdate_script','Script_Upgrades').'"/>';
    }
    
    function execute() {
        $classname = $this->getClassname();
        
        include_once($GLOBALS['codex_dir'].substr($this->getPath(), strlen($this->branch)));
        
        $upgrade = new $classname();
        $upgrade->apply();

        return $upgrade->getUpgradeErrors();
    }

}

?>
