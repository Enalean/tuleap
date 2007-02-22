<?php
/**
 * CodeX Command-line Interface
 *
 * Portion of this file is inspired from the  GForge Command-line Interface
 * contained in GForge.
 * Copyright 2005 GForge, LLC
 * http://gforge.org/
 *
 * @version   $Id$
 */

require_once(CODEX_CLI_DIR.'/CLI_Module.class.php');

require_once('CLI_Action_Docman_CreateFolder.class.php');
require_once('CLI_Action_Docman_Delete.class.php');
require_once('CLI_Action_Docman_Monitor.class.php');
require_once('CLI_Action_Docman_Move.class.php');

class CLI_Module_Docman extends CLI_Module {
    function CLI_Module_Docman() {
        $this->CLI_Module("docman", "Manage documents");
        $this->addAction(new CLI_Action_Docman_CreateFolder());
        $this->addAction(new CLI_Action_Docman_Delete());
        $this->addAction(new CLI_Action_Docman_Monitor());
        $this->addAction(new CLI_Action_Docman_Move());
    }
}
?>