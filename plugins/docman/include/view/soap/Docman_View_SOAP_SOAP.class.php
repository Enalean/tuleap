<?php

class Docman_View_SOAP_SOAP {
	
    var $_controller;

    function Docman_View_SOAP_SOAP(&$controller) {
        $this->_controller = $controller;
    }
    function display() {
    	   return true;
    }
}

?>