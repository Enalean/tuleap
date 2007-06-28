<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* 
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Docman_Delete extends CLI_Action {
    function CLI_Action_Docman_Delete() {
        $this->CLI_Action('delete', 'Delete a document or a folder');
        $this->setSoapCommand('deleteDocmanItem');
        $this->addParam(array(
            'name'           => 'item_id',
            'description'    => '--id=<item_id>     ID of the item we want to delete',
            'parameters'     => array('id'),
        ));
    }
    
    function validate_item_id(&$item_id) {
        if (!$item_id) {
            exit_error("You must specify the ID of the document with the --id parameter");
        }
        return true;
    }
}

?>
