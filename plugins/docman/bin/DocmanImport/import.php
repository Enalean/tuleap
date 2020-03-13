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

require_once __DIR__ . '/../../../../src/www/include/pre.php';
require_once __DIR__ . '/XMLDocmanImport.class.php';
require_once __DIR__ . '/XMLDocmanUpdate.class.php';
require_once __DIR__ . '/parameters.php';

$console = new Log_ConsoleLogger();
$usage = "
Usage: import.php --url=<Tuleap URL> --project=<destination project unix name> --archive=<archive path>
       import.php --help";

function help($console)
{
    global $usage;

    $console->info("Imports a set of Tuleap Docman documents to a project
$usage
Required parameters:
    --url=<Tuleap URL>                     URL of the Tuleap home page (ex: http://tuleap.mycompany.com:81
    --archive=<archive path>                Path of the archive folder that must contain an XML file
    --project=<destination project>         Destination project unix name

Optional parameters:
    --project-id=<destination project ID>   Destination project ID (use instead of --project)
    --folder-id=<destination folder ID>     Destination folder ID. The imported documents will be created in this folder (default: project root folder)
    --force                                 Continue even if some users (authors, owners) don't exist on the remote server
    --reorder                               The items will be reordered in alphabetical order, folders before documents
    --update                                Update the document tree. Warning! This will create, update or remove documents
    --continue                              Continue the upload: this will only create missing items
    --path=<path to import>                 Path to import in the archive (default: \"/Project Documentation\")
    --import-metadata=<metadata title>      Dynamic metadata that will be appended by import messages. If not defined, the messages will be appended to the item description.
    --auto-retry                            In case of error, retry 5 times before asking the user what to do
    --login                                 Provide the username through the cli
    --password                              Provide the user password through the cli
    --help                                  Show this help");
    die;
}

if (getParameter($argv, 'help') || getParameter($argv, 'h')) {
    help($console);
}

if (($url = getParameter($argv, 'url', true)) === null) {
    $console->error('Missing parameter: --url');
}

$folderId = getParameter($argv, 'folder-id', true);

if (($archive = getParameter($argv, 'archive', true)) === null) {
    $console->error("Missing parameter: --archive");
} elseif (is_dir($archive)) {
    if (!is_file("$archive/" . basename($archive) . ".xml")) {
        $console->error("The archive folder must contain an XML file with the same name");
        $archive = null;
    }
} else {
    $console->error("The archive must be an existing folder");
    $archive = null;
}

$project = getParameter($argv, 'project');
$projectId = getParameter($argv, 'project-id');
if ($project === null && $projectId === null) {
    $console->error("One of the following parameters is required: --project, --project-id");
}

$force                 = getParameter($argv, 'force');
$reorder               = getParameter($argv, 'reorder');
$update                = getParameter($argv, 'update');
$continue              = getParameter($argv, 'continue');
$path                  = getParameter($argv, 'path');
$importMessageMetadata = getParameter($argv, 'import-metadata');
$autoRetry             = getParameter($argv, 'auto-retry');
$login                 = getParameter($argv, 'login');
$password              = getParameter($argv, 'password');

// Path parameter check
if ($path === null) {
    $path = '/Project Documentation';
} else {
    if (!preg_match('/^(\/[^\/]+)+$/', $path)) {
        $console->error("The path must follow the pattern: /folder/subfolder(/subfolder...)");
        die;
    }
}

if ($url === null || ($project === null && $projectId === null) || $archive === null) {
    $console->error($usage);
    die;
}

// Ask for login and password
if (!isset($login)) {
    echo "Login: ";
    $login = fgets(STDIN);
    $login = substr($login, 0, strlen($login) - 1);
}

if (!isset($password)) {
    echo "Password for $login: ";

    if (PHP_OS != 'WINNT') {
        shell_exec('stty -echo');
        $password = fgets(STDIN);
        shell_exec('stty echo');
    } else {
        $password = fgets(STDIN);
    }
    $password = substr($password, 0, strlen($password) - 1);
}

$start = microtime(true);

// WSDL URL
$wsdl = "$url/soap/codendi.wsdl.php?wsdl";

// Command line (for printing in log file)
$command = implode(' ', $argv);

if ($update || $continue) {
    // Connectecho
    $xmlUpdate = new XMLDocmanUpdate($command, $project, $projectId, $wsdl, $login, $password, $force, $reorder, $importMessageMetadata, $autoRetry, $console);

    // Update
    if ($update) {
        try {
            $xmlUpdate->updatePath($archive, $folderId, $path);
        } catch (Exception $e) {
            $console->error($e->getMessage());
            exit(1);
        }
    } elseif ($continue) {
        try {
            $xmlUpdate->continuePath($archive, $folderId, $path);
        } catch (Exception $e) {
            $console->error($e->getMessage());
            exit(1);
        }
    }
} else {
    // Connect
    $xmlImport = new XMLDocmanImport($command, $project, $projectId, $wsdl, $login, $password, $force, $reorder, $importMessageMetadata, $autoRetry, $console);

    try {
        // Import
        $xmlImport->importPath($archive, $folderId, $path);
    } catch (Exception $e) {
        $console->error($e->getMessage());
        exit(1);
    }
}

$end = microtime(true);
$console->info("Time elapsed: " . round($end - $start, 1) . "s");
