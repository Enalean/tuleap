<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
 *
 *
 */

require_once('CLI_Action_Docman_CreateDocument.class.php');

class CLI_Action_Docman_CreateEmptyDocument extends CLI_Action_Docman_CreateDocument  {

    function CLI_Action_Docman_CreateEmptyDocument() {
        $this->CLI_Action_Docman_CreateDocument('createEmptyDocument', 'Create a document of type empty');
        $this->setSoapCommand('createDocmanEmptyDocument');
    }
}

?>
