<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * CodeXJRI */
require_once('common/mvc/Controler.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('CodeXJRIViews.class.php');
require_once('CodeXJRIActions.class.php');
class CodeXJRI extends Controler {
    
    function CodeXJRI() {
        
    }
    
    function request() {
        $request =& HTTPRequest::instance();
        
        $this->view = 'index';
    }
}

?>