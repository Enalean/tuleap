<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* $Id$
*
* Docman_View_Details
*/

require_once('Docman_View_Display.class.php');
require_once('Docman_View_DocmanError.class.php');


class Docman_View_Embedded extends Docman_View_Display {
    
    
    function _content($params) {
        if (isset($params['version_number'])) {
            $version_factory =& $this->_getVersionFactory($params);
            $version =& $version_factory->getSpecificVersion($params['item'], $params['version_number']);
        } else {
            $version =& $params['item']->getCurrentVersion();
        }
        if ($version) {
            if (file_exists($version->getPath())) {
                $event_manager =& EventManager::instance();
                $event_manager->processEvent(PLUGIN_DOCMAN_EVENT_ACCESS, array(
                    'group_id' => $params['group_id'],
                    'item'     => &$params['item'],
                    'version'  => $version->getNumber(),
                    'user'     => &$params['user']
                ));
                $mime = explode('/', $version->getFiletype());
                if (in_array($mime[1], array('plain', 'css', 'javascript'))) {
                    $balise = 'pre';
                } else {
                    $balise = 'div';
                }
                echo '<'. $balise .' style="clear:both">';
                readfile($version->getPath());
                echo '</'. $balise .'>';
            } else {
                $this->_controller->feedback->log('error', $GLOBALS['Language']->getText('plugin_docman', 'error_filenotfound'));
                $v =& new Docman_View_DocmanError($this->_controller);
                $v->display($params);
            }
        }
    }
}

?>
