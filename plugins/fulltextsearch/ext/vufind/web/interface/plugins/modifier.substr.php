<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     substr
 * Purpose:  Performs the PHP function substr
 * -------------------------------------------------------------
 */
function smarty_modifier_substr($str, $start, $length = null) {
    return substr($str, $start, $length);
}
?>