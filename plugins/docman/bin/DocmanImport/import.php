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

require_once 'XMLDocmanImport.class.php';
require_once 'XMLDocmanUpdate.class.php';
require_once 'parameters.php';

function usage() {
    echo PHP_EOL."Usage: import.php --wsdl=<WSDL URL> --projectId=<destination project ID> --archive=<archive path>".PHP_EOL;
    echo         "       import.php --help".PHP_EOL.PHP_EOL;
}

function help() {
    echo "Imports a set of Codendi Docman documents to a project".PHP_EOL;
    usage();
    echo "Required parameters:".PHP_EOL;
    echo "    --wsdl=<WSDL URL>                       URL of the Codendi WSDL. Usually <codendi_home>/soap/codex.wsdl.php?wsdl".PHP_EOL;
    echo "    --projectId=<destination project ID>    Destination project ID".PHP_EOL;
    echo "    --archive=<archive path>                Path of the archive folder that must contain an XML file".PHP_EOL.PHP_EOL;
    echo "Optional parameters:".PHP_EOL;
    echo "    --folderId=<destination folder ID>      Destination folder ID. The imported documents will be created in this folder (default: project root folder)".PHP_EOL;
    echo "    --force                                 Continue even if some users (authors, owners) don't exist on the remote server".PHP_EOL;
    echo "    --reorder                               The items will be reordered in alphabetical order, folders before documents".PHP_EOL;
    echo "    --update                                Update the document tree. Warning! This will create, update or remove documents".PHP_EOL;
    echo "    --help                                  Show this help".PHP_EOL.PHP_EOL; 
    die;
}

if (getParameter($argv, 'help') || getParameter($argv, 'h')) {
    help();
}

if (($wsdl = getParameter($argv, 'wsdl', true)) === null) {
    echo "Missing parameter: --wsdl".PHP_EOL;
}

if (($projectId = getParameter($argv, 'projectId', true)) === null) {
    echo "Missing parameter: --projectId".PHP_EOL;
}

$folderId = getParameter($argv, 'folderId', true);

if (($archive = getParameter($argv, 'archive', true)) === null) {
    echo "Missing parameter: --archive".PHP_EOL;
} else if (is_dir($archive)) {
    if (!is_file("$archive/".basename($archive).".xml")) {
        echo "The archive folder must contain an XML file with the same name".PHP_EOL;   
        $archive = null; 
    }
} else {
    echo "The archive must be an existing folder".PHP_EOL;
    $archive = null;
}

$force = getParameter($argv, 'force');
$reorder = getParameter($argv, 'reorder');
$update = getParameter($argv, 'update');

if ($wsdl === null || $projectId === null || $archive === null) {
    usage();
    die;
}

// Ask for login and password
if (!isset($login)) {
    echo "Login: ";
    $login = fgets(STDIN);
    $login = substr($login, 0, strlen($login)-1);
}

if (!isset($password)) {
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

$start = microtime(true);

if ($update) {
    // Connect
    $xmlUpdate = new XMLDocmanUpdate($projectId, $wsdl, $login, $password, $force, $reorder);
    
    // Update
    $xmlUpdate->updatePath($archive, $folderId, 'Project Documentation');
} else {
    // Connect
    $xmlImport = new XMLDocmanImport($projectId, $wsdl, $login, $password, $force, $reorder);
    
    // Import
    $xmlImport->importPath($archive, $folderId, 'Project Documentation');
}

$end = microtime(true);
echo "Time elapsed: ".round($end-$start, 1)."s".PHP_EOL;
?>