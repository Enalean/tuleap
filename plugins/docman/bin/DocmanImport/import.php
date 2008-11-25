<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require 'XMLDocmanImport.class.php';

$start = microtime(true);

// Login and password can be hardcoded during the tests
$login = '';
$password = '';

if (!$login) {
    // Ask for login and password
    echo "Login: ";
    $login = fgets(STDIN);
    $login = substr($login, 0, strlen($login)-1);
}

if (!$password) {
    echo "Password for $login: ";

    if ( PHP_OS != 'WINNT') {
        shell_exec('stty -echo');
        $password = fgets(STDIN);
        shell_exec('stty echo');
    } else {
        $password = fgets(STDIN);
    }
    $password = substr($password, 0, strlen($password)-1);
    echo PHP_EOL;
}

// Specify here the WSDL url to <codexurl>/soap/codex.wsdl.php?wsdl
$wsdl = "http://brame-farine.grenoble.xrce.xerox.com:8360/soap/codex.wsdl.php?wsdl";

if (!$wsdl) {
    exit("Error: You need to specify the WSDL url in this script.".PHP_EOL);
}

$xmlImport = new XMLDocmanImport(108, $wsdl, $login, $password);
$xmlImport->import('example', 594);

$end = microtime(true);
echo "Time elapsed: ".round($end-$start, 1)."s".PHP_EOL;
?>