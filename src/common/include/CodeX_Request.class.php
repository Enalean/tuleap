<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * CodeX_Request
 */


/* abstract */ class CodeX_Request {
    
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
    function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtoupper($_SERVER['HTTP_X_REQUESTED_WITH']) == 'XMLHTTPREQUEST';
    }

}
?>
