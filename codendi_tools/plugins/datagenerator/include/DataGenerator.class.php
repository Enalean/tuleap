<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * DataGenerator */
require_once('common/mvc/Controler.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('DataGeneratorViews.class.php');
require_once('DataGeneratorActions.class.php');
class DataGenerator extends Controler {
    
    function DataGenerator() {
        session_require(array('group'=>'1','admin_flags'=>'A'));
    }
    
    function request() {
        $request =& HTTPRequest::instance();
        if ($request->get('action') == 'generate') {
            $this->action = 'generate';
        }
        $this->view = 'index';
    }
}

?>