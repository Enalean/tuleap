<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * SOAPRequest
 */

require_once('common/include/Codendi_Request.class.php');
class SOAPRequest extends Codendi_Request {
    
    function SOAPRequest($params) {
        parent::Codendi_Request($params);
    }
    
    function registerShutdownFunction() {
    }
}
?>
