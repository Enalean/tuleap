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

if ($argc < 4) {
    echo <<< EOT
Usage: $argv[0] project_id admin_user_name archive_path

EOT;
    exit(1);
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
    $project_id = $argv[1];

    $user = $user_manager->forceLogin($argv[2]);
    if ((! $user->isSuperUser() && ! $user->isAdmin($project_id)) || ! $user->isActive()) {
        throw new RuntimeException($GLOBALS['Language']->getText('project_import', 'invalid_user', array($user_name)));
    }

    $archive_path = $argv[3];

    $archive = new ZipArchive();
    if ($archive->open($archive_path) !== true) {
        fwrite(STDERR, "*** ERROR: Unable to open archive ".$argv[3].PHP_EOL);
        exit(1);
    }

    $xml_importer->importFromArchive($project_id, $archive);

    $archive->close();
} catch (XML_ParseException $exception) {
    foreach ($exception->getErrors() as $parse_error) {
        fwrite(STDERR, "*** ERROR: ".$parse_error.PHP_EOL);
    }
    exit(1);
} catch (Exception $exception) {
    fwrite(STDERR, "*** ERROR: ".$exception->getMessage().PHP_EOL);
    exit(1);
}
