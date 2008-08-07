<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* 
*
* Docman_View_Download
*/

require_once('Docman_View_View.class.php');
require_once('Docman_View_DocmanError.class.php');

class Docman_View_Download extends Docman_View_View {
    
    /* protected */ function _content($params) {
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
                $event_manager->processEvent('plugin_docman_file_before_download', array(
                    'group_id'  => $params['group_id'],
                    'item'      => &$params['item'],
                    'user'      => &$params['user'],
                    'headers' => array('mime_type' => $version->getFiletype(),
                                       'file_name' => $version->getFilename()),
                    'path'      => $version->getPath()
                    ));
                header('Content-Type: '. $version->getFiletype());
                header('Content-Length: '. $version->getFilesize());
                header('Content-Disposition: filename="'. $version->getFilename() .'"');
                readfile($version->getPath());
            } else {
                $this->_controller->feedback->log('error', 'The file cannot be found.');
                $v =& new Docman_View_DocmanError($this->_controller);
                $v->display($params);
            }
        }
    }
    
}

?>
