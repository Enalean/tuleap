/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * <?=$class_name?>
 */
require_once('common/mvc/Controler.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('<?=$class_name?>Views.class.php');
require_once('<?=$class_name?>Actions.class.php');
class <?=$class_name?> extends Controler {
    
    function <?=$class_name?>() {
        session_require(array('group'=>'1','admin_flags'=>'A'));
    }
    
    function request() {
        $request =& HTTPRequest::instance();
        
        $this->view = 'hello';
    }
}

