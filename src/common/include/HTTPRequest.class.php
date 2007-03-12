<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * HTTPRequest
 */

require_once('browser.php');
require_once('common/include/Request.class.php');
class HTTPRequest extends Request {
    
    function HTTPRequest() {    
    }
    
    function get($variable) {
        if ($this->exist($variable)) {
            return (get_magic_quotes_gpc()?$this->_stripslashes($_REQUEST[$variable]):$_REQUEST[$variable]);
        } else {
            return false;
        }
    }
    
    function exist($variable) {
        return isset($_REQUEST[$variable]);
    }
    
    function &instance() {
        static $_httprequest_instance;
        if (!$_httprequest_instance) {
            $_httprequest_instance = new HTTPRequest();
        }
        return $_httprequest_instance;
    }
    
    function _stripslashes($value) {
        if (is_string($value)) {
            $value = stripslashes($value);
        } else if (is_array($value)) {
            foreach($value as $key => $val) {
                $value[$key] = $this->_stripslashes($val);
            }
        }
        return $value;
    }

    function browserIsNetscape4() {
        return browser_is_netscape4();
    }

    /** For debug only */
    function dump() {
        var_dump($_REQUEST);
    }
}
?>
