<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once("Sanitizer.class.php");

/**
 * SimpleSanitizer 
 */
class SimpleSanitizer extends Tuleap_Sanitizer {
    
    function __construct() {
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
     * @deprecated See Codendi_HTMLPurifier
     */
    function sanitize($html) {
        $pattern = array('@<@', '@>@');
        $replacement = array('&lt;', '&gt;');
        return preg_replace($pattern, $replacement, $html);
    }

    /**
     * @deprecated
     */
    function unsanitize($html) {
        $pattern = array('@&lt;@', '@&gt;@');
        $replacement = array('<', '>');
        return preg_replace($pattern, $replacement, $html);
    }
}
