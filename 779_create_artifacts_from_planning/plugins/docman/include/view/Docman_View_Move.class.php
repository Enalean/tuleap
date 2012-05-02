<?php

/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
* 
* 
*
* Docman_View_Move
*/

require_once('Docman_View_Details.class.php');

require_once('Docman_View_ItemDetailsSectionMove.class.php');


class Docman_View_Move extends Docman_View_Details {
    
    function _getTitle($params) {
        $hp = Codendi_HTMLPurifier::instance();
        return $GLOBALS['Language']->getText('plugin_docman', 'move',  $hp->purify($params['item']->getTitle(), CODENDI_PURIFIER_CONVERT_HTML) );
    }
    
    function _content($params) {
        $token = isset($params['token']) ? $params['token'] : null;
        parent::_content(
            $params, 
            new Docman_View_ItemDetailsSectionMove(
                $params['item'], 
                $params['default_url'], 
                $this->_controller, 
                array_merge(
                    array('docman_icons' => $this->_getDocmanIcons($params)),
                    $params
                ),
                $token
            ), 
            'actions'
        );
        
    }
}

?>
