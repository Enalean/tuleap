<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * ServerUpdateActions
 */
require_once('common/mvc/Actions.class.php');
require_once('common/include/HTTPRequest.class.php');

class ServerUpdateActions extends Actions {
    
    function ServerUpdateActions(&$controler, $view=null) {
        $this->Actions($controler);
        $GLOBALS['Language']->loadLanguageMsg('serverUpdate', 'serverupdate');
    }

}


?>
