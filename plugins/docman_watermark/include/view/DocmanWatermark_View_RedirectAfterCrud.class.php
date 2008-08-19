<?php

require_once(dirname(__FILE__).'/../../../docman/include/view/Docman_View_View.class.php');

class DocmanWatermark_View_RedirectAfterCrud extends Docman_View_View {
    
    function _content($params) {
        if (isset($params['redirect_to'])) {
            $url = $params['redirect_to'];
        } else if (isset($params['default_url_params'])) {
            $url = $this->buildUrl($params['default_url'], $params['default_url_params'], false);
        } else {
            $url = $params['default_url'];
        }
        user_set_preference('plugin_docman_flash', serialize($this->_controller->feedback));
        $GLOBALS['Response']->redirect($url);
    }
}

?>
