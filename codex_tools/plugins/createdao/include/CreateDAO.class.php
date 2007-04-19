<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * CreateDAO */
require_once('common/mvc/Controler.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('CreateDAOViews.class.php');
require_once('CreateDAOActions.class.php');
class CreateDAO extends Controler {
    
    var $_plugin;
    function CreateDAO(&$plugin) {
        $this->_plugin =& $plugin;
        session_require(array('group'=>'1','admin_flags'=>'A'));
    }
    
    function &getPlugin() {
        return $this->_plugin;
    }
    
    function request() {
        $request =& HTTPRequest::instance();
        if ($request->get('action') === 'create' && $request->exist('action')) {
            $this->action = 'create';
        }
        $this->view = 'browse';
    }
}

?>