<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* $Id$
*/

require_once(CODEX_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Docman_Move extends CLI_Action {
    function CLI_Action_Docman_Move() {
        $this->CLI_Action('move', 'Move an item from a folder to another one.');
        $this->addParam(array(
            'name'           => 'item_id',
            'description'    => '--id=<item_id>        ID of the item we want to delete',
            'parameters'     => array('id'),
        ));
        $this->addParam(array(
            'name'           => 'parent',
            'description'    => '--parent=<folder_id>  ID of the new folder',
        ));
    }
    
    function validate_item_id(&$item_id) {
        if (!$item_id) {
            exit_error("You must specify the ID of the document with the --id parameter");
        }
        return true;
    }
    function validate_parent(&$parent) {
        if (!$parent) {
            exit_error("You must specify the ID of the dst folder with the --parent parameter");
        }
        return true;
    }
}

?>
