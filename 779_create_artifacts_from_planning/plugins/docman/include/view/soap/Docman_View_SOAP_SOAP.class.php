<?php

class Docman_View_SOAP_SOAP {
	
    var $_controller;

    function Docman_View_SOAP_SOAP(&$controller) {
        $this->_controller = $controller;
    }
    function display($params = array()) {
        return isset($params['action_result']) ? $params['action_result'] : true;
    }
}

?>