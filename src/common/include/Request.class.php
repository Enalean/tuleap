<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id: SOAPRequest.class.php 4791 2007-01-30 16:42:38Z mnazaria $
 *
 * Request
 */


/* abstract */ class Request {
    
    /* abstract */ function get($variable) {}
    
    /* abstract */ function exist($variable) {}
    
    function getCookie($name) {
        $cookie_manager =& new CookieManager();
        return $cookie_manager->getCookie($name);
    }
    
    function isCookie($name) {
        $cookie_manager =& new CookieManager();
        return $cookie_manager->isCookie($name);
    }

}
?>
