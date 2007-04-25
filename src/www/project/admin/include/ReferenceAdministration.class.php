<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * ReferencesAdministration
 */
require_once('common/mvc/Controler.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('ReferenceAdministrationViews.class.php'); 
require_once('ReferenceAdministrationActions.class.php');

class ReferenceAdministration extends Controler {
    
    function ReferenceAdministration() {
    }
    
    function request() {
        $request =& HTTPRequest::instance();
        
        session_require(array('group'=>$request->get('group_id'),'admin_flags'=>'A'));
        
        if ($request->exist('view')) {
            switch ($request->get('view')) {
                case 'creation':
                    $this->view = 'creation';
                    break;
                case 'edit':
                    $this->view = 'edit';
                    break;
                default:
                    $this->view = 'browse';
                    break;
            }
        } else {
            $this->view = 'browse';
        }
        
        if ($request->exist('action')) {
            switch ($request->get('action')) {
                case 'do_edit':
                    $this->action = 'do_edit';
                    break;
                case 'do_create':
                    $this->action = 'do_create';
                    break;
                case 'do_delete':
                    $this->action = 'do_delete';
                    break;
                default:
                    break;
            }
        }
    }
}


?>
