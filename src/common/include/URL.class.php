<?php

class URL {
    /**
    * @see http://www.ietf.org/rfc/rfc2396.txt Annex B
    */
    /* static */ function parse($url) {
        $components = array();
        preg_match('`^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?`i',$url, $components);
        return $components;
    }
    /* static */ function getHost($url) {
        $components = URL::parse($url);
        return $components[4];
    }
}
?>
