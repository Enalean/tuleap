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

require_once('CLI_Action_Docman_GetRoot.class.php');
require_once('CLI_Action_Docman_List.class.php');
require_once('CLI_Action_Docman_CreateFile.class.php');
require_once('CLI_Action_Docman_CreateEmbeddedFile.class.php');
require_once('CLI_Action_Docman_CreateWikiPage.class.php');
require_once('CLI_Action_Docman_CreateLink.class.php');
require_once('CLI_Action_Docman_CreateEmptyDocument.class.php');
require_once('CLI_Action_Docman_CreateFolder.class.php');
require_once('CLI_Action_Docman_Delete.class.php');
require_once('CLI_Action_Docman_Monitor.class.php');
require_once('CLI_Action_Docman_Move.class.php');
require_once('CLI_Action_Docman_GetFile.class.php');

class CLI_Module_Docman extends CLI_Module {
    function __construct() {
        parent::__construct("docman", "Manage documents");
        $this->addAction(new CLI_Action_Docman_GetRoot());
        $this->addAction(new CLI_Action_Docman_List());
        $this->addAction(new CLI_Action_Docman_CreateFile());
        $this->addAction(new CLI_Action_Docman_CreateEmbeddedFile());
        $this->addAction(new CLI_Action_Docman_CreateWikiPage());
        $this->addAction(new CLI_Action_Docman_CreateLink());
        $this->addAction(new CLI_Action_Docman_CreateEmptyDocument());
        $this->addAction(new CLI_Action_Docman_CreateFolder());
        $this->addAction(new CLI_Action_Docman_Delete());
        $this->addAction(new CLI_Action_Docman_Monitor());
        $this->addAction(new CLI_Action_Docman_Move());
        $this->addAction(new CLI_Action_Docman_GetFile());
    }
}
