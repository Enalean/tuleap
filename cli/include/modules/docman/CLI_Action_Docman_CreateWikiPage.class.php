<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *
 */

require_once('CLI_Action_Docman_CreateDocument.class.php');

class CLI_Action_Docman_CreateWikiPage extends CLI_Action_Docman_CreateDocument  {

    function __construct() {
        parent::__construct('createWikiPage', 'Create a document of type wiki');
        $this->setSoapCommand('createDocmanWikiPage');

        $this->addParam(array(
            'name'           => 'content',
            'description'    => '--content=<page name>    Name of the wiki page',
            'soap'     => true,
        ));
    }

    function validate_content(&$content) {
        if (!isset($content) || trim($content) == '') {
            echo $this->help();
            exit_error("You must specify the name of the wiki page --content parameter");
        }
        return true;
    }
}
