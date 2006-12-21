<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* $Id$
*
* Docman_View_Header
*/

require_once('Docman_View_View.class.php');

/* abstract */ class Docman_View_Header extends Docman_View_View {
    
    function _header($params) {
        header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        
        if (isset($params['title'])) {
            $htmlParams['title'] = $params['title'];
        } else {
            $htmlParams['title'] = $this->_getTitle($params);
        }
        
        $htmlParams = array_merge($htmlParams, $this->_getAdditionalHtmlParams($params));

        if(isset($params['pv']) && $params['pv'] > 0) {
            $htmlParams['pv'] = $params['pv'];
            $GLOBALS['HTML']->pv_header($htmlParams);
        }
        else {
            site_header($htmlParams);
        }
    }
    
    /* protected */ function _getTitle($params) {
        return $GLOBALS['Language']->getText('plugin_docman','title');
    }
    
    /* protected */ function _footer($params) {
        if(isset($params['pv']) && $params['pv'] > 0) {
            $GLOBALS['HTML']->pv_footer(array());
        }
        else {
            $GLOBALS['HTML']->footer(array());
        }
    }
    
    /* protected */ function _getAdditionalHtmlParams($params) {
        return  array();
    }
    
    /* protected */ function _feedback($params) {
        $this->_controller->feedback->display();
    }
    
}

?>
