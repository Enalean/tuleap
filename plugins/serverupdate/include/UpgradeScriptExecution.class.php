<?php
/**
 * @copyright Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * @author Marc Nazarian <marc.nazarian@xrce.xerox.com>
 * 
 *
 * UpgradeScriptExecution
 */

//$Language->loadLanguageMsg('svnupdate/svnupdate');

class UpgradeScriptExecution {
    
    /**
     * @var string $_date date of the script execution (unix timestamp format)
     */
    var $_date;
    /**
     * @var string $_script name of the script
     */
    var $_script;
    /**
     * @var string $_executionMode name of the execution mode
     */
    var $_executionMode;
    
    /**
     * @var boolean $_successfullyApplied true if the upgrade has been successfully applied, false otherwise
     */
    var $_successfullyApplied;
    /**
     * @var string $_errors errors occurs during the apply
     */
    var $_errors;
    
    /**
     * constructor
     */
    function UpgradeScriptExecution($script) {
        $this->_script = $script;
    }
    
    function getDate() {
        return $this->_date;
    }
    function setDate($date) {
        $this->_date = $date;
    }
    function getScript() {
        return $this->_script;
    }
    function setScript($script) {
        $this->_script = $script;
    }
    function getExecutionMode() {
        return $this->_mode;
    }
    function setExecutionMode($mode) {
        $this->_mode = $mode;
    }
    function getSuccessfullyApplied() {
        return $this->_successfullyApplied;
    }
    function setSuccessfullyApplied($applied_result) {
        $this->_successfullyApplied = $applied_result;
    }
    function getErrors() {
        return $this->_errors;
    }
    function setErrors($errors) {
        $this->_errors = $errors;
    }
    
    
    
    

}

?>
