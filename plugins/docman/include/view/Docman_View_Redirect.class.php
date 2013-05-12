<?php

/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
* 
* 
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
            $event_manager->processEvent('plugin_docman_event_access', array(
                'group_id' => $params['group_id'],
                'item'     => &$params['item'],
                'user'     => &$params['user']
            ));
            $url = $params['item']->accept($this);
        } else {
            $url = '/';
        }

        $GLOBALS['Response']->redirect($url);
    }
    function visitFolder(&$item, $params = array()) {
        trigger_error('Redirect view cannot be applied to Folders');
    }
    function visitWiki(&$item, $params = array()) {
        return '/wiki/?group_id='. $item->getGroupId() .'&pagename='. urlencode($item->getPagename());
    }
    function visitCloudstorage(&$item, $params = array()) {
        return '/plugins/cloudstorage/?group_id=' . $item->getGroupId() . '&action=' . urlencode($item->getServiceName()) . '&folder='. urlencode($item->getDocumentId());
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
