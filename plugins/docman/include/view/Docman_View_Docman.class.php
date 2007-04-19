<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* $Id$
*
* Docman_View_Docman
*/

require_once('Docman_View_ProjectHeader.class.php');
require_once('Docman_View_ToolbarNewDocumentVisitor.class.php');

class Docman_View_Docman extends Docman_View_ProjectHeader {
    /* protected */ function _toolbar($params) {

        // No toolbar in printer version
        if(isset($params['pv']) && $params['pv'] > 0) {
            return;
        }

        $tools = array();
        $this->_addDocmanTool($params, $tools);
        
        $dPm  =& Docman_PermissionsManager::instance(($params['group_id']));
        $user =& $this->_controller->getUser();
        $oneFolderWritable = $dPm->oneFolderIsWritable($user);
        if ($oneFolderWritable) {
            $url_params = array('action' => 'newGlobalDocument');
            if (isset($params['item'])) {
                $url_params['id'] = $params['item']->accept(new Docman_View_ToolbarNewDocumentVisitor());
            }
            $tools[] = '<b><a href="'.$this->buildUrl($params['default_url'], $url_params).'">'. $GLOBALS['Language']->getText('plugin_docman', 'new_document') .'</a></b>';
            
            if($this->_controller->userCanAdmin()) {
                $tools[] = '<b><a href="'. $params['default_url'] .'&amp;action=admin">'. $GLOBALS['Language']->getText('plugin_docman', 'toolbar_admin') .'</a></b>';
            }
        }
        
        $tools[] = help_button('DocumentManagerPlugin.html',false,$GLOBALS['Language']->getText('global','help'));
        
        echo implode(' | ', $tools);
        echo "\n";
    }
    /* protected */ function _addDocmanTool(&$array) {
    }
}

?>
