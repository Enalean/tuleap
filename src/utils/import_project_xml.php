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

$posix_user = posix_getpwuid(posix_geteuid());
$sys_user   = $posix_user['name'];
if ( $sys_user !== 'root' && $sys_user !== 'codendiadm' ) {
    fwrite(STDERR, 'Unsufficient privileges for user '.$sys_user.PHP_EOL);
    exit(1);
}

$usage_options  = '';
$usage_options .= 'p:'; // give me a project
$usage_options .= 'u:'; // give me a user
$usage_options .= 'i:'; // give me the archive path to import
$usage_options .= 'm:'; // give me the path of the user mapping file

$long_options = array(
    'force-create-all-users'
);

function usage() {
    global $argv;

    echo <<< EOT
Usage: $argv[0] -p project_id -u user_name -i path_to_archive -m path_to_mapping

Import a project structure

  -p <project_id> The id of the project to import the archive
  -u <user_name>  The user used to import
  -i <path>       The path of the archive of the exported XML + data
  -m <path>       The path of the user mapping file
  -h              Display this help

  --force-create-all-users Force creation/activation of all users before import

EOT;
    exit(1);
}

$arguments = getopt($usage_options, $long_options);

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

if (! isset($arguments['m'])) {
    usage();
} else {
    $mapping_path = $arguments['m'];
}

$force_create_all_users_active = isset($arguments['force-create-all-users']);

$user_manager  = UserManager::instance();
$security      = new XML_Security();
$xml_validator = new XML_RNGValidator();
$xml_importer  = new ProjectXMLImporter(
    EventManager::instance(),
    ProjectManager::instance(),
    $xml_validator,
    new UGroupManager(),
    $user_manager,
    new XMLImportHelper(),
    new ProjectXMLImporterLogger()
);

$transformer = new User\XML\Import\MappingFileOptimusPrimeTransformer($user_manager);
$logger      = new ProjectXMLImporterLogger();
$builder     = new User\XML\Import\UsersToBeImportedCollectionBuilder(
    $user_manager,
    $logger,
    $security,
    $xml_validator
);

$console = new Log_ConsoleLogger();

try {
    $user = $user_manager->forceLogin($username);
    if ((! $user->isSuperUser() && ! $user->isAdmin($project_id)) || ! $user->isActive()) {
        throw new RuntimeException($GLOBALS['Language']->getText('project_import', 'invalid_user', array($username)));
    }

    $archive = new ZipArchive();
    if ($archive->open($archive_path) !== true) {
        $console->error("Unable to open archive $archive_path");
        exit(1);
    }

    if ($force_create_all_users_active) {
        $collection_from_archive = $builder->buildFromArchive($archive);
        $users_collection        = $transformer->transform($collection_from_archive, $mapping_path);
        $users_collection->process($user_manager, $console);
    }

    $xml_importer->importFromArchive($project_id, $archive);

    $archive->close();

    exit(0);
} catch (XML_ParseException $exception) {
    foreach ($exception->getErrors() as $parse_error) {
        $console->error('XML: '.$parse_error.' line:'.$exception->getSourceXMLForError($parse_error));
    }
} catch (Exception $exception) {
    $console->error($exception->getMessage());
}
exit(1);
