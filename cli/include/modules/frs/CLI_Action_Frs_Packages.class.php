<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Frs_Packages extends CLI_Action {
    function __construct() {
        parent::__construct('getPackages', 'Returns the list of packages that belongs to a project.');
    }
}
