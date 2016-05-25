<?php
/**
 * Codendi Command-line Interface
 *
 * Portion of this file is inspired from the  GForge Command-line Interface
 * contained in GForge.
 * Copyright 2005 GForge, LLC
 * http://gforge.org/
 *
 */

require_once(CODENDI_CLI_DIR.'/CLI_Module.class.php');


require_once('CLI_Action_Frs_AddRelease.class.php');
require_once('CLI_Action_Frs_AddPackage.class.php');
require_once('CLI_Action_Frs_AddFile.class.php');
require_once('CLI_Action_Frs_GetFile.class.php');
require_once('CLI_Action_Frs_Releases.class.php');
require_once('CLI_Action_Frs_Packages.class.php');
require_once('CLI_Action_Frs_Files.class.php');
require_once('CLI_Action_Frs_UploadedFiles.class.php');
require_once('CLI_Action_Frs_DeleteFile.class.php');
require_once('CLI_Action_Frs_FileInfo.class.php');
require_once('CLI_Action_Frs_DeleteEmptyPackage.class.php');
require_once('CLI_Action_Frs_DeleteAllEmptyPackages.class.php');
require_once('CLI_Action_Frs_DeleteEmptyRelease.class.php');
require_once('CLI_Action_Frs_DeleteAllEmptyReleases.class.php');

class CLI_Module_Frs extends CLI_Module {
    function __construct() {
        parent::__construct("frs", "Manage file releases");
        $this->addAction(new CLI_Action_Frs_Packages());
        $this->addAction(new CLI_Action_Frs_AddPackage());
        $this->addAction(new CLI_Action_Frs_Releases());
        $this->addAction(new CLI_Action_Frs_AddRelease());
        $this->addAction(new CLI_Action_Frs_GetFile());
        $this->addAction(new CLI_Action_Frs_Files());
        $this->addAction(new CLI_Action_Frs_AddFile());
        $this->addAction(new CLI_Action_Frs_UploadedFiles());
        $this->addAction(new CLI_Action_Frs_DeleteFile());
        $this->addAction(new CLI_Action_Frs_FileInfo());
        $this->addAction(new CLI_Action_Frs_DeleteEmptyPackage());
        $this->addAction(new CLI_Action_Frs_DeleteAllEmptyPackages());
        $this->addAction(new CLI_Action_Frs_DeleteEmptyRelease());
        $this->addAction(new CLI_Action_Frs_DeleteAllEmptyReleases());
    }
}
