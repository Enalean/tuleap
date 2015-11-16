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
$usage_options .= 'm:'; // give me the path of the mapping file

function usage() {
    global $argv;

    echo <<< EOT
Usage: $argv[0] -u username -i path_to_archive -m path_to_mapping

Check that the user mapping file is well formed and can be used for the import.

  -u <user_name>  The user used to export
  -i <path>       The path of the archive of the exported XML + data
  -m <path>       The path of the user mapping file
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

if (! isset($arguments['m'])) {
    usage();
} else {
    $mapping_path = $arguments['m'];
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
$transformer   = new User\XML\Import\MappingFileOptimusPrimeTransformer($user_manager);
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
    $xml_element  = $security->loadString($xml_contents);

    $rng_path = realpath(__DIR__ .'/../common/xml/resources/users.rng');
    $xml_validator->validate($xml_element, $rng_path);

    $collection_from_archive = $builder->build($xml_element);
    $transformer->transform($collection_from_archive, $mapping_path);
    $console->info('Everything is awesome! ♪♫');

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
