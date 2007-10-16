<?php

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * CodeXUpgrade
 * Generic class for upgrading CodeX Server.
 *
 */
/*

Files must be XXX_filename.class.php where XXX = 000 to 999
Class must be Update_XXX where XXX is the same as the filename

---------8<---------------TEMPLATE:-----
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// 


require_once('CodeXUpgrade.class.php');

class Update_001 extends CodeXUpgrade {

    function _process() {
        echo $this->getLineSeparator();
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();
        echo "HERE PLACE THE PROCESS...";
        //$this->addUpgradeError("Erreur systeme");
        echo $this->getLineSeparator();
    }

}
---------8<-----------------------------

*/

// Defines all of the CodeX settings first (hosts, databases, etc.)
require_once(getenv('CODEX_LOCAL_INC')?getenv('CODEX_LOCAL_INC'):'/etc/codex/conf/local.inc');
require($GLOBALS['db_config_file']);
//database abstraction
require_once(dirname(__FILE__).'/../../common/dao/include/DataAccessObject.class.php');
require_once(dirname(__FILE__).'/../../common/dao/CodexDataAccess.class.php');

define("WEB_ENVIRONMENT", "web");
define("CONSOLE_ENVIRONMENT", "console");

/*abstract*/ class CodeXUpgrade extends DataAccessObject {

    //abstract public function _process();    // signature for the _process function.

    /**
     * @var array{string} $_upgradeError an array of errors appeared in upgrade process
     */
    var $_upgradeErrors = array();
    /**
     * @var string $_environment execution environment
     */
    var $_environment;
    
    
    function CodeXUpgrade() {
        $this->_upgradeError = null;
        $this->setEnvironment();
        $da =& CodexDataAccess::instance();
        parent::DataAccessObject($da);
    }
    
    function getUpgradeErrors() {
        return $this->_upgradeErrors;
    }
    function addUpgradeError($upgradeError) {
        $this->_upgradeErrors[] = $upgradeError;
    }
    function isUpgradeError() {
        return (count($this->getUpgradeErrors()) > 0); 
    }
    function setEnvironment() {
        $default_environment = WEB_ENVIRONMENT;
        $this->_environment = $default_environment;
        if ($this->_isWebExecution()) {
            $this->_environment = WEB_ENVIRONMENT;
        } else {
            $this->_environment = CONSOLE_ENVIRONMENT;
        }
    }
    function getEnvironment() {
        return $this->_environment;
    }
    function _isWebExecution() {
        if (isset($_SERVER["HTTP_HOST"])) {
            return true;
        }
        return false;
    }
    
    /**
     * Set a connection to the database
     */
    function databaseConnect() {
        return true;
    }
    /**
     * Returns if the database connection is set or not
     * @return true if the database connection is set, false otherwise
     */
    function isDatabaseConnected() {
        /*$isConnected = false;
        if (getConnection()) {
            $isConnected = true;
        }
        return $isConnected;*/
        return true;
    }
    
    /**
     * Test if the current upgrade has already been applied or not
     *
     * @return boolean true if the current upgrade has already been applied, false otherwise.
     */
    function isAlreadyApplied() {
        $upgrade_name = get_class($this);
        $sql = "SELECT * FROM plugin_serverupdate_upgrade WHERE script = '".$upgrade_name."'";
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            return true;
        }
        return false;
    }
    
    /**
     * Test if the current upgrade has already been applied WITH SUCCESS or not
     *
     * @return boolean true if the current upgrade has already been applied with success, false otherwise.
     */
    function isAlreadyAppliedWithSuccess() {
        $upgrade_name = get_class($this);
        $sql = "SELECT * FROM plugin_serverupdate_upgrade WHERE script = '".$upgrade_name."' AND success = 1";
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() > 0) {
            return true;
        }
        return false;
    }
    
    /**
     * Test if a table exists.
     *
     * @return boolean true if given table exists in database.
     */
    function tableExists($table) {
        $sql = sprintf('SHOW TABLES LIKE %s', 
                       $this->da->quoteSmart($table));
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Test if field exists in given table.
     *
     * @return boolean true if given field exists in given table.
     */
    function fieldExists($table, $field) {
        $sql = sprintf('SHOW COLUMNS FROM %s LIKE %s', 
                       $table,
                       $this->da->quoteSmart($field));
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Test if index exists in given table.
     *
     * @return boolean true if given index exists in given table.
     */
    function indexNameExists($table, $index) {
        $sql = sprintf('SHOW INDEX FROM %s',
                       $table);
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError()) {
            while($row = $dar->getRow()) {
                if($row['Key_name'] == $index) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Apply the upgrade
     * This is the generic function :
     * It checks some recurrent things (database connection, etc.)
     * and call the _process function redefined in the concrete subclasses
     *
     */
    function apply() {
        // 1) Connection to the database
        $this->databaseConnect();
        if ($this->isDatabaseConnected()) {
            // 2) Check that the script has not already been applied
            if (!$this->isAlreadyApplied()) {
                // 3) execute the upgrade
                $this->_process();
            } else {
                $this->addUpgradeError("Upgrade already applied.");
            }
            // 4) store the upgrade in database
            $store_success = $this->storeUpgrade();
            if (!$store_success) {
                $this->addUpgradeError("Upgrade store in database failed : ".$this->da->isError());
            }
        } else {
            // No database connection (impossible to store it in tha database, because no connection)
            $this->addUpgradeError("No database connection. Upgrade failed.");
        }
    }

    /**
     * Store the result of the upgrade in the database.
     *
     * @return boolean true if the storage was fine, false otherwise
     */
    function storeUpgrade() {
        $upgrade_stored = false;
        if ($this->isDatabaseconnected()) {
            /*$errors = array();
            foreach( $this->getUpgradeErrors() as $e) {
                $errors[] = $this->da->quoteSmart($e);
            }*/
            // Store the upgrade into database
            $sql = "INSERT INTO plugin_serverupdate_upgrade(date, script, execution_mode, success, error) ";
            $sql .= "VALUES (UNIX_TIMESTAMP(), '".get_class($this)."', '".$this->getEnvironment()."', '".(($this->isUpgradeError())?0:1)."', ".$this->da->quoteSmart(implode("; ", $this->getUpgradeErrors())).")";
            $updated = $this->update($sql);
            if($updated && $this->da->affectedRows() === 1) {
                $upgrade_stored = true;
            }
        }
        return $upgrade_stored;
    }


    /**
     * Write a message in the ad-hoc output.
     * - the web interface if the execution is a web one
     * - the standard output error if the execution if a console one
     *
     * @param string $feedback the text to display
     */
    function writeFeedback($feedback) {
        switch ($this->getEnvironment()) {
            case WEB_ENVIRONMENT:
                echo $feedback;
                break;
            case CONSOLE_ENVIRONMENT:
                $stderr = fopen('php://stderr', 'w');
                fwrite($stderr, $feedback);
                fclose($stderr);
                break;
            default:
                break;
        }
    }
    
    /** 
     * Returns the line separator regarding the execution environment
     *
     * @return string the string representing the line separator depending the execution mode
     */
    function getLineSeparator() {
        switch ($this->getEnvironment()) {
            case WEB_ENVIRONMENT:
                return  "<br />";
                break;
            case CONSOLE_ENVIRONMENT:
                return "\n";
                break;
            default:
                break;
        }
    }

}

?>
