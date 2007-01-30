<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * SOAPRequest
 */


class SOAPRequest {
    
    var $params;
    function SOAPRequest($params) {
    	   $this->params = $params;
    }
    
    function get($variable) {
        if ($this->exist($variable)) {
            return $this->params[$variable];
        } else {
            return false;
        }
    }
    
    function exist($variable) {
        return isset($this->params[$variable]);
    }
}
?>
