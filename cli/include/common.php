<?php
/**
 * CodeX Command-line Interface
 *
 * Portion of this file is inspired from the  GForge Command-line Interface
 * contained in GForge.
 * Copyright 2005 GForge, LLC
 * http://gforge.org/
 *
 */

/**
 * common.php - Common functions
 */

/**
 * exit_error - Exits the program displaying an error and returning an error code
 */
function exit_error($msg, $errcode=1) {
    if (is_string($msg)) {
        echo "Fatal error: ".$msg."\n";
    } elseif (is_object($msg)) {
        echo "Fatal error: [".$msg->faultcode."] ".$msg->faultstring."\n";
    }
    exit (intval($errcode));
}


?>