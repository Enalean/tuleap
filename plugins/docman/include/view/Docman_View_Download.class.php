<?php

/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
* 
* 
*
* Docman_View_Download
*/

require_once('Docman_View_View.class.php');
require_once('Docman_View_DocmanError.class.php');

class Docman_View_Download extends Docman_View_View {
    
    /* protected */ function _content($params) {
        $version_factory =& $this->_getVersionFactory($params);
        if (isset($params['version_number'])) {
            $version =& $version_factory->getSpecificVersion($params['item'], $params['version_number']);
        } else {
            $version =& $params['item']->getCurrentVersion();
        }
        if ($version) {
            if (file_exists($version->getPath())) {
                $event_manager =& EventManager::instance();
                $event_manager->processEvent('plugin_docman_event_access', array(
                    'group_id' => $params['group_id'],
                    'item'     => &$params['item'],
                    'version'  => $version->getNumber(),
                    'user'     => &$params['user']
                ));
                $version_factory->callVersionEvents($params['item'], $params['user'], $version, &$this->_controller);
                header('Expires: Mon, 26 Nov 1962 00:00:00 GMT');  // IE & HTTPS
                header('Pragma: private');                         // IE & HTTPS
                header('Cache-control: private, must-revalidate'); // IE & HTTPS
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
