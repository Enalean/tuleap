<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Default_Logout extends CLI_Action {
    function __construct() {
        parent::__construct('logout', 'Terminate the session');
    }
    function addProjectParam() {
    }
    function soapResult($params, $soap_result, $fieldnames = array(), $loaded_params = array()) {
        $GLOBALS['soap']->endSession();
        echo "Session terminated.\n";
    }
}
