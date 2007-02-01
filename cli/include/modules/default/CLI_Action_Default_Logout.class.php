<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* $Id$
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Default_Logout extends CLI_Action {
    function CLI_Action_Default_Logout() {
        $this->CLI_Action('logout', 'Terminate the session');
    }
    function addProjectParam() {
    }
    function soapResult($soap_result, $fieldnames = array(), $params = array()) {
        $GLOBALS['soap']->endSession();
        echo "Session terminated.\n";
    }
}

?>
