<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* 
*
* Docman_View_NewVersion
*/

require_once('Docman_View_Details.class.php');

require_once('Docman_View_ItemDetailsSectionNewVersion.class.php');

class Docman_View_NewVersion extends Docman_View_Details {
    
    
    /* protected */ function _getTitle($params) {
        return $GLOBALS['Language']->getText('plugin_docman', 'details_newversion_title', $params['item']->getTitle());
    }
    
    /* protected */ function _content($params) {
        $force    = isset($params['force_item']) ? $params['force_item'] : null;
        $token = isset($params['token']) ? $params['token'] : null;
        parent::_content($params, new Docman_View_ItemDetailsSectionNewVersion($params['item'], $params['default_url'], $this->_controller, $force, $token), 'actions');
    }
}

?>
