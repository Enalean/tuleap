<?php
/**
 * CodeX Command-line Interface
 *
 * Portion of this file is inspired from the  GForge Command-line Interface
 * contained in GForge.
 * Copyright 2005 GForge, LLC
 * http://gforge.org/
 *
 * @version   $Id$
 */

/**
 * common.php - Common functions
 */

/**
 * exit_error - Exits the program displaying an error and returning an error code
 */
function exit_error($msg, $errcode=1) {
    echo "Fatal error: ".$msg."\n";
    exit (intval($errcode));
}


?>
