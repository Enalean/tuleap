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


require_once('CLI_Action_Frs_AddRelease.class.php');
require_once('CLI_Action_Frs_AddPackage.class.php');
require_once('CLI_Action_Frs_AddFile.class.php');
require_once('CLI_Action_Frs_GetFile.class.php');
require_once('CLI_Action_Frs_Releases.class.php');
require_once('CLI_Action_Frs_Packages.class.php');
require_once('CLI_Action_Frs_Files.class.php');
require_once('CLI_Action_Frs_UploadedFiles.class.php');

class CLI_Module_Frs extends CLI_Module {
    function CLI_Module_Frs() {
        $this->CLI_Module("frs", "Manage file releases");
        $this->addAction(new CLI_Action_Frs_Packages());
        $this->addAction(new CLI_Action_Frs_AddPackage());
        $this->addAction(new CLI_Action_Frs_Releases());
        $this->addAction(new CLI_Action_Frs_AddRelease());
        $this->addAction(new CLI_Action_Frs_GetFile());
        $this->addAction(new CLI_Action_Frs_Files());
        $this->addAction(new CLI_Action_Frs_AddFile());
        $this->addAction(new CLI_Action_Frs_UploadedFiles());
    }
}

?>