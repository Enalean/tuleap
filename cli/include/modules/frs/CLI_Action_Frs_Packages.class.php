<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* $Id$
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Frs_Packages extends CLI_Action {
    function CLI_Action_Frs_Packages() {
        $this->CLI_Action('getPackages', 'Returns the list of packages that belongs to a project.');
    }
}

?>
