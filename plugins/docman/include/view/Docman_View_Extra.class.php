<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* 
*
* Docman_View_Extra
*/

require_once('Docman_View_Docman.class.php');

class Docman_View_Extra extends Docman_View_Docman {
    
    /* protected */ function _addDocmanTool($params, &$array) {
        $array[] = '<b><a href="'. $params['default_url'] .'">'. $GLOBALS['Language']->getText('plugin_docman', 'toolbar_docman') .'</a></b>';
    }
}

?>
