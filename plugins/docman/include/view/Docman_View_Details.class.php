<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* 
*
* Docman_View_Details
*/

require_once('Docman_View_Display.class.php');

require_once('Docman_View_ItemDetails.class.php');
require_once('Docman_View_ItemDetailsSectionProperties.class.php');
require_once('Docman_View_ItemDetailsSectionEditProperties.class.php');
require_once('Docman_View_ItemDetailsSectionPermissions.class.php');
require_once('Docman_View_ItemDetailsSectionNotifications.class.php');
require_once('Docman_View_ItemDetailsSectionHistory.class.php');
require_once('Docman_View_ItemDetailsSectionActions.class.php');
require_once('Docman_View_ItemDetailsSectionApproval.class.php');

class Docman_View_Details extends Docman_View_Display {
    
    /* protected */ function _getTitle($params) {
        return $GLOBALS['Language']->getText('plugin_docman', 'details_title', $params['item']->getTitle());
    }
    
    function _content($params, $view = null, $section = null) {
        $url = $params['default_url'];
        
        $token = isset($params['token']) ? $params['token'] : null;        

        $user_can_manage = $this->_controller->userCanManage($params['item']->getId());
        $user_can_write = $user_can_manage || $this->_controller->userCanWrite($params['item']->getId());
        $user_can_read  = $user_can_write || $this->_controller->userCanRead($params['item']->getId());

        $user_can_read_obsolete = false;
        if($params['item']->isObsolete()) {
            // Restrict access to non docman admin.
            if(!$this->_controller->userCanAdmin()) {
                $user_can_manage = false;
                $user_can_write  = false;
                // Save read value to let user (according to their rights) to see
                // the properties.
                $user_can_read_obsolete = $user_can_read;
                $user_can_read   = false;
            }
        }
        
        $item_factory =& $this->_getItemFactory($params);
        $details      =& new Docman_View_ItemDetails($params['item'], $url);
        $sections     = array();
        if ($user_can_read || $user_can_read_obsolete) {
            if ($view && $section == 'properties') {
                $props =& $view;
            } else {
                $props =& new Docman_View_ItemDetailsSectionProperties($params['item'], $params['default_url'], $params['theme_path'], $user_can_write, $token);
            }
            $sections['properties'] = true;
            $details->addSection($props);
        }
        if ($user_can_write) {
            if ($view && $section == 'actions') {
                $actions =& $view;
            } else {
                $actions =& new Docman_View_ItemDetailsSectionActions($params['item'], $params['default_url'], $item_factory->isMoveable($params['item']), !$item_factory->isRoot($params['item']), $this->_controller, $token);
            }
            $sections['actions'] = true;
            $details->addSection($actions);
        }
        if ($user_can_manage) {
            $sections['permissions'] = true;
            $details->addSection(new Docman_View_ItemDetailsSectionPermissions($params['item'], $params['default_url'], $token));
        }
        
        if ($user_can_read) {
            $sections['notifications'] = true;
            $details->addSection(new Docman_View_ItemDetailsSectionNotifications($params['item'], $params['default_url'], $this->_controller->notificationsManager, $token));
        }
        
        if ($user_can_read) {
            if ($view && $section == 'approval') {
                $approval =& $view;
            } else {
                $approval = new Docman_View_ItemDetailsSectionApproval($params['item'], $params['default_url'], $params['theme_path'], $this->_controller->notificationsManager);
            }
            $sections['approval'] = true;
            $details->addSection($approval);
        }

        if ($user_can_read) {
            $sections['history'] = true;
            $logger = $this->_controller->getLogger();
            $details->addSection(new Docman_View_ItemDetailsSectionHistory($params['item'], $params['default_url'], $user_can_manage, $logger));
        }
        if ($section && isset($sections[$section])) {
            $details->setCurrentSection($section);
        } else if (isset($params['section']) &&  isset($sections[$params['section']])) {
            $details->setCurrentSection($params['section']);
        } else if ($this->_controller->request->get('action') == 'permissions' &&  isset($sections['permissions'])) {
            $details->setCurrentSection('permissions');
        }
        $details->display();
    }
}

?>
