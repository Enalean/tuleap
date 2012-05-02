<?php

/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
* 
* 
*
* Docman_View_Details
*/

require_once('Docman_View_Details.class.php');

require_once('Docman_View_ItemDetailsSectionDelete.class.php');

class Docman_View_Delete extends Docman_View_Details {
    
    
    /* protected */ function _getTitle($params) {
        $hp = Codendi_HTMLPurifier::instance();
        return $GLOBALS['Language']->getText('plugin_docman', 'details_delete_title',  $hp->purify($params['item']->getTitle(), CODENDI_PURIFIER_CONVERT_HTML) );
    }
    
    /* protected */ function _content($params) {
        $token = isset($params['token']) ? $params['token'] : null;
        parent::_content($params, new Docman_View_ItemDetailsSectionDelete($params['item'], $params['default_url'], $this->_controller, $token), 'actions');
    }
}

?>
