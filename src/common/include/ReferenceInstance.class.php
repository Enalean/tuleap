<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2006. All rights reserved
 * 
 * 
 *
 * Reference Instance class
 * Stores a reference as extracted from some user text.
 * Only valid Reference Instances are created (i.e., the corresponding "Reference" object must exist).
 */


class ReferenceInstance {
    
    var $match;
    var $gotoLink;
    var $reference;

    /** 
     * Constructor 
     * Note that we need a valid reference parameter 
     */
    function ReferenceInstance($match,$ref) {
        $this->reference =& $ref;
        $this->match = $match;
    }

    /** Accessors */
    function getMatch() { return $this->match;}
    function &getReference() { return $this->reference;}
    function getGotoLink() { return $this->gotoLink;}

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
        if ($group_id!=100) { $group_param="&group_id=$group_id";}

        $this->gotoLink="/goto?key=".urlencode($keyword)."&val=".urlencode($value).$group_param;
    }

}
