<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* $Id$
*
* Docman_View_Update
*/

require_once('Docman_View_Details.class.php');

require_once('Docman_View_ItemDetailsSectionUpdate.class.php');

class Docman_View_Update extends Docman_View_Details {
    
    
    /* protected */ function _getTitle($params) {
        return $GLOBALS['Language']->getText('plugin_docman', 'details_update_title', $params['item']->getTitle());
    }
    
    /* protected */ function _content($params) {
        $force    = isset($params['force_item']) ? $params['force_item'] : null;
        parent::_content($params, new Docman_View_ItemDetailsSectionUpdate($params['item'], $params['default_url'], $this->_controller, $force), 'actions');
    }
}

?>
