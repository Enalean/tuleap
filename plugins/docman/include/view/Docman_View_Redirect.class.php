<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* $Id$
*
* Docman_View_Redirect
*/

require_once('Docman_View_View.class.php');

class Docman_View_Redirect extends Docman_View_View /* implements Visitor */ {
    
    /* protected */ function _content($params) {
        if (isset($params['redirect_to'])) {
            $url = $params['redirect_to'];
        } else if(isset($params['item'])) {
            $event_manager =& EventManager::instance();
            $event_manager->processEvent(PLUGIN_DOCMAN_EVENT_ACCESS, array(
                'group_id' => $params['group_id'],
                'item'     => &$params['item'],
                'user'     => &$params['user']
            ));
            $url = $params['item']->accept($this);
        } else {
            $url = '/';
        }
        user_set_preference('plugin_docman_flash', addslashes(serialize($this->_controller->feedback)));
        header('Location: '. $url);
    }
    function visitFolder(&$item, $params = array()) {
        trigger_error('Redirect view cannot be applied to Folders');
    }
    function visitWiki(&$item, $params = array()) {
        return '/wiki/?group_id='. $item->getGroupId() .'&pagename='. $item->getPagename();
    }
    function visitLink(&$item, $params = array()) {
        return $item->getUrl();
    }
    function visitFile(&$item, $params = array()) {
        trigger_error('Redirect view cannot be applied to Files');
    }
    function visitEmbeddedFile(&$item, $params = array()) {
        trigger_error('Redirect view cannot be applied to Embedded Files');
    }

    function visitEmpty(&$item, $params = array()) {
        trigger_error('Redirect view cannot be applied to Empty documents');
    }
}
?>
