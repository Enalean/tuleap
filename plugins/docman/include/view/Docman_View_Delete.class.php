<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* 
*
* Docman_View_Details
*/

require_once('Docman_View_Details.class.php');

require_once('Docman_View_ItemDetailsSectionDelete.class.php');

class Docman_View_Delete extends Docman_View_Details {
    
    
    /* protected */ function _getTitle($params) {
        return $GLOBALS['Language']->getText('plugin_docman', 'details_delete_title', $params['item']->getTitle());
    }
    
    /* protected */ function _content($params) {
        $token = isset($params['token']) ? $params['token'] : null;
        parent::_content($params, new Docman_View_ItemDetailsSectionDelete($params['item'], $params['default_url'], $this->_controller, $token), 'actions');
    }
}

?>
