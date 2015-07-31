#!/usr/share/codendi/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2013 - 2015. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'pre.php';

$usage_options  = '';
$usage_options .= 'p:'; // give me a project
$usage_options .= 'u:'; // give me a user
$usage_options .= 'i:'; // give me the archive path to import

function usage() {
    global $argv;

    echo <<< EOT
Usage: $argv[0] -p project_id -u user_name -i path_to_archive

Import a project structure

  -p <project_id> The id of the project to export
  -u <user_name>  The user used to export
  -i <path>       The path of the archive of the exported XML + data
  -h              Display this help

EOT;
    exit(1);
}

$arguments = getopt($usage_options);

if (isset($arguments['h'])) {
    usage();
}

if (! isset($arguments['p'])) {
    usage();
} else {
    $project_id = (int)$arguments['p'];
}

if (! isset($arguments['u'])) {
    usage();
} else {
    $username = $arguments['u'];
}

if (! isset($arguments['i'])) {
    usage();
} else {
    $archive_path = $arguments['i'];
}

$user_manager = UserManager::instance();
$xml_importer = new ProjectXMLImporter(
    EventManager::instance(),
    ProjectManager::instance(),
    new XML_RNGValidator(),
    new UGroupManager(),
    UserManager::instance(),
    new XMLImportHelper(),
    new ProjectXMLImporterLogger()
);

try {
    $user = $user_manager->forceLogin($username);
    if ((! $user->isSuperUser() && ! $user->isAdmin($project_id)) || ! $user->isActive()) {
        throw new RuntimeException($GLOBALS['Language']->getText('project_import', 'invalid_user', array($username)));
    }

    $archive = new ZipArchive();
    if ($archive->open($archive_path) !== true) {
        fwrite(STDERR, "*** ERROR: Unable to open archive ".$archive_path.PHP_EOL);
        exit(1);
    }

    $xml_importer->importFromArchive($project_id, $archive);

    $archive->close();

    exit(0);
} catch (XML_ParseException $exception) {
    foreach ($exception->getErrors() as $parse_error) {
        fwrite(STDERR, "*** ERROR: ".$parse_error.PHP_EOL);
    }
    exit(1);
} catch (Exception $exception) {
    fwrite(STDERR, "*** ERROR: ".$exception->getMessage().PHP_EOL);
    exit(1);
}
