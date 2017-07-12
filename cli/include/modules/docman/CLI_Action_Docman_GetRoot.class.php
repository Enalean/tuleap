<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Docman_GetRoot extends CLI_Action {
    function __construct() {
        parent::__construct('getRoot', 'Returns the document object id that is at the top of the docman given a group object.');
        $this->setSoapCommand('getRootFolder');
    }
}
