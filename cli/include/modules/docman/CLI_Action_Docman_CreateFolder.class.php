<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once('CLI_Action_Docman_CreateItem.class.php');

class CLI_Action_Docman_CreateFolder extends CLI_Action_Docman_CreateItem {

    function __construct() {
        parent::__construct('createFolder', 'Create a folder');
        $this->setSoapCommand('createDocmanFolder');
    }

    function validate_ordering(&$ordering) {
        $allowed_ordering = array("begin", "end");
        if (isset($ordering)) {
            // check that the value is allowed
            if (!in_array($ordering, $allowed_ordering)) {
            echo $this->help();
                exit_error("You must specify the ordering of the folder with the --ordering parameter, taking the value {".implode(",", $allowed_ordering)."}");
            }
        } else {
            // $ordering is not set
            $ordering = "begin";
        }
        return true;
    }
}
