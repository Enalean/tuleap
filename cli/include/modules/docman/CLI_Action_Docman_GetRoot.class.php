<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* 
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Docman_GetRoot extends CLI_Action {
    function CLI_Action_Docman_GetRoot() {
        $this->CLI_Action('getRoot', 'Returns the document object id that is at the top of the docman given a group object.');
        $this->setSoapCommand('getRootFolder');
    }
}

?>
