<?php
//
// this is a service for twisties - it just stores teh preference sent by javascript from a twistie page - see twistie.php
//
require_once('pre.php');
if (isset($item)) {
    if (isset($val)) {
        user_set_preference($item, $val);
    } else {
        user_del_preference($item);
    }
}
?>