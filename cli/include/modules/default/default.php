<?php
/**
 * CodeX Command-line Interface
 *
 * Portion of this file is inspired from the  GForge Command-line Interface
 * contained in GForge.
 * Copyright 2005 GForge, LLC
 * http://gforge.org/
 *
 * @version   
 */

require_once(CODEX_CLI_DIR.'/CLI_Module.class.php');

require_once('CLI_Action_Default_Login.class.php');
require_once('CLI_Action_Default_Logout.class.php');

class CLI_Module_Default extends CLI_Module {
    function CLI_Module_Default() {
        $this->CLI_Module("default", "Default module");
        $this->addAction(new CLI_Action_Default_Login());
        $this->addAction(new CLI_Action_Default_Logout());
    }
}

?>
