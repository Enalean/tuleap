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

$sys_user = getenv("USER");
if ( $sys_user !== 'root' && $sys_user !== 'codendiadm' ) {
    fwrite(STDERR, 'Unsufficient privileges for user '.$sys_user.PHP_EOL);
    exit(1);
}

$usage_options  = '';
$usage_options .= 'u:'; // give me a user
$usage_options .= 'i:'; // give me the archive path to import
$usage_options .= 'o:'; // give me the output path of the csv file

function usage() {
    global $argv;

    echo <<< EOT
Usage: $argv[0] -i path_to_archive

Generate the file mapping that is needed for project import.

  -u <user_name>  The user used to export
  -i <path>       The path of the archive of the exported XML + data
  -o <path>       The path of the generated CSV content
  -h              Display this help

EOT;
    exit(1);
}

$arguments = getopt($usage_options);

if (isset($arguments['h'])) {
    usage();
}

if (! isset($arguments['u'])) {
    usage();
} else {
    $username = $arguments['u'];
}

if (! isset($arguments['o'])) {
    usage();
} else {
    $output = $arguments['o'];
}

if (! isset($arguments['i'])) {
    usage();
} else {
    $archive_path = $arguments['i'];
}

$security      = new XML_Security();
$xml_validator = new XML_RNGValidator();
$user_manager  = UserManager::instance();
$logger        = new ProjectXMLImporterLogger();
$builder       = new User\XML\Import\UsersToBeImportedCollectionBuilder($user_manager, $logger);
$console       = new Log_ConsoleLogger();

try {
    $user = $user_manager->forceLogin($username);
    if (! $user->isActive() || ! $user->isSuperUser()) {
        throw new RuntimeException("User $username must be site administrator");
    }

    $archive = new ZipArchive();
    if ($archive->open($archive_path) !== true) {
        $console->error("Unable to open archive ".$archive_path);
        exit(1);
    }

    $xml_contents = $archive->getFromName('users.xml');
    if (! $xml_contents) {
        $console->error("users.xml is missing from archive");
        exit(1);
    }
    $xml_element  = $security->loadString($xml_contents);

    $rng_path = realpath(__DIR__ .'/../common/xml/resources/users.rng');
    $xml_validator->validate($xml_element, $rng_path);

    $collection = $builder->build($xml_element);
    $collection->toCSV($output);

    $archive->close();

    exit(0);
} catch (XML_ParseException $exception) {
    foreach ($exception->getErrors() as $parse_error) {
        $console->error($parse_error);
    }
} catch (Exception $exception) {
    $console->error($exception->getMessage());
}
exit(1);
