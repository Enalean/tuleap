<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id:Sanitizer.class.php 4446 2006-12-08 16:18:48 +0000 (Fri, 08 Dec 2006) ahardyau $
 *
 * Sanitizer (Abstract)
 */
class Sanitizer {
    
    function Sanitizer() {
        $this->_construct();
    }

    /**
     * @see Constructor
     */
    function _construct() {
    }


    /**
     * sanitize the string
     * @param $html the string which may contain invalid 
     */
    function sanitize($html) {
        trigger_error("Sanitizer::sanitize() not yet implemented.");
    }
}
?>
