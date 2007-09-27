<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* $Id$
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Docman_List extends CLI_Action {
    function CLI_Action_Docman_List() {
        $this->CLI_Action('list', 'List folder contents.');
        $this->setSoapCommand('listFolder');
        $this->addParam(array(
            'name'           => 'item_id',
            'description'    => '--id=<item_id>        ID of the folder',
            'parameters'     => array('id'),
        ));
    }
    
    function validate_item_id(&$item_id) {
        if (!$item_id) {
            exit_error("You must specify the ID of the folder with the --id parameter");
        }
        return true;
    }
}

?>
