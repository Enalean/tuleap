<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Reference Instance class
 * Stores a reference as extracted from some user text.
 * Only valid Reference Instances are created (i.e., the corresponding "Reference" object must exist).
 */
class ReferenceInstance {
    
    var $match;
    var $gotoLink;
    var $reference;
    var $value;

    /** 
     * Constructor 
     * Note that we need a valid reference parameter 
     */
    function ReferenceInstance($match,$ref,$value) {
        $this->reference = $ref;
        $this->match = $match;
        $this->value = $value;
    }

    /** Accessors */
    function getMatch() { return $this->match;}

    /**
     * @return Reference
     */
    function getReference() { return $this->reference;}
    function getGotoLink() { return $this->gotoLink;}
    function getValue() { return $this->value;}

    /**
     @return full link (with http://servername...) if needed. 
    */
    function getFullGotoLink() { return get_server_url().$this->gotoLink;}

    /**
     * Compute GotoLink according to the extracted match.
     */
    function computeGotoLink($keyword,$value,$group_id) {
        // If no group_id from context, the default is "100". 
        // Don't use it in the link...
        $group_param = '';
        if ($group_id!=100) { $group_param="&group_id=$group_id";}

        $this->gotoLink="/goto?key=".urlencode($keyword)."&val=".urlencode($value).$group_param;
    }

}

?>
