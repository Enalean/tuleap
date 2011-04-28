<?php

/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
* 
* 
*
* Docman_View_RedirectAfterCrud
*/

require_once('Docman_View_View.class.php');

/* abstract */ class Docman_View_RedirectAfterCrud extends Docman_View_View {
    
    function _content($params) {
        if (isset($params['redirect_to'])) {
            $url = $params['redirect_to'];
        } else if (isset($params['default_url_params'])) {
            $url = $this->buildUrl($params['default_url'], $params['default_url_params'], false);
        } else {
            $url = $params['default_url'];
        }
        
        if (isset($params['redirect_anchor'])) {
            $url .= $params['redirect_anchor'];
        }

        $GLOBALS['Response']->redirect($url);
    }
}

?>
