<?php
require_once("Sanitizer.class.php");

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * SimpleSanitizer 
 */
class SimpleSanitizer extends Sanitizer {
    
    function SimpleSanitizer() {
        $this->_construct();
    }

    /**
     * @see Constructor
     */
    function _construct() {
        parent::_construct();
    }


    /**
     * sanitize the string
     * @param $html the string which may contain invalid 
     */
    function sanitize($html) {
        $pattern = array('@<@', '@>@');
        $replacement = array('&lt;', '&gt;');
        return preg_replace($pattern, $replacement, $html);
    }
}
?>
