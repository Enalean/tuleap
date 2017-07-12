<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *
 */

require_once('CLI_Action_Docman_CreateDocument.class.php');

class CLI_Action_Docman_CreateEmbeddedFile extends CLI_Action_Docman_CreateDocument  {

    function __construct() {
        parent::__construct('createEmbeddedFile', 'Create a document of type embedded');
        $this->setSoapCommand('createDocmanEmbeddedFile');

        $this->addParam(array(
            'name'           => 'content',
            'description'    => '--content=<raw content>    content of the embedded file',
            'soap'     => true,
        ));
    }

    function validate_content(&$content) {
        if (!isset($content) || trim($content) == '') {
            echo $this->help();
            exit_error("You must specify the content of the document with the --content parameter");
        }
        return true;
    }
}
