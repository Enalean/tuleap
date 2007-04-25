<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* 
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Docman_CreateFolder extends CLI_Action {
	
    function CLI_Action_Docman_CreateFolder() {
        $this->CLI_Action('createFolder', 'Create a folder');
        $this->setSoapCommand('createDocmanFolder');
        $this->addParam(array(
            'name'           => 'parent_id',
            'description'    => '--parent_id=<item_id>     ID of the parent the folder will be created in'
        ));
        $this->addParam(array(
            'name'           => 'title',
            'description'    => '--title=<title>     Title of the new folder'
        ));
        $this->addParam(array(
            'name'           => 'description',
            'description'    => '--description=<description>     Description of the new folder'
        ));
        $this->addParam(array(
            'name'           => 'ordering',
            'description'    => '--ordering=<begin|end>     Place where the new folder will be hosted'
        ));
    }
    
    function validate_parent_id(&$parent_id) {
        if (!isset($parent_id)) {
            echo $this->help();
            exit_error("You must specify the parent ID of the folder with the --parent_id parameter");
        }
        return true;
    }
    function validate_title(&$title) {
        if (!isset($title) || trim($title) == '') {
            echo $this->help();
            exit_error("You must specify the title of the folder with the --title parameter");
        }
        return true;
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

?>
