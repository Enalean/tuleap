<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 *
 */

require_once('CLI_Action_Docman_CreateDocument.class.php');

class CLI_Action_Docman_CreateLink extends CLI_Action_Docman_CreateDocument  {

    function __construct() {
        parent::__construct('createLink', 'Create a document of type link');
        $this->setSoapCommand('createDocmanLink');

        $this->addParam(array(
            'name'           => 'content',
            'description'    => '--content=<url>    Url of the link',
            'soap'     => true,
        ));
    }

    function validate_content(&$content) {
        if (!isset($content) || trim($content) == '') {
            echo $this->help();
            exit_error("You must specify the url with the --content parameter");
        }
        return true;
    }
}
