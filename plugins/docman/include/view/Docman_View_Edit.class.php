<?php

/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
* 
* 
*
* Docman_View_Edit
*/

require_once('Docman_View_Details.class.php');

require_once('Docman_View_ItemDetailsSectionEditProperties.class.php');

class Docman_View_Edit extends Docman_View_Details {
    
    
    /* protected */ function _getTitle($params) {
        $hp = Codendi_HTMLPurifier::instance();
        return $GLOBALS['Language']->getText('plugin_docman', 'details_edit_title',  $hp->purify($params['item']->getTitle(), CODENDI_PURIFIER_CONVERT_HTML) );
    }
    
    /* protected */ function _content($params) {
        $force    = isset($params['force_item']) ? $params['force_item'] : null;
        $token = isset($params['token']) ? $params['token'] : null;
        $updateConfirmed = isset($params['updateConfirmed']) ? $params['updateConfirmed'] : true;
        $recurse = isset($params['recurse']) ? $params['recurse'] : array();
        $recurseOnDocs = isset($params['recurseOnDocs']) ? $params['recurseOnDocs'] : false;

        $section = new Docman_View_ItemDetailsSectionEditProperties($params['item'], $params['default_url'], $params['theme_path'], $force, $token, $updateConfirmed, $recurse, $recurseOnDocs);
        parent::_content($params, $section, 'properties');
    }
}

?>
