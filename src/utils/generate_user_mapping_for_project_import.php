#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

require_once __DIR__ . '/../www/include/pre.php';

use Tuleap\Project\XML\Import;

$posix_user = posix_getpwuid(posix_geteuid());
$sys_user   = $posix_user['name'];
if ($sys_user !== 'root' && $sys_user !== 'codendiadm') {
    fwrite(STDERR, 'Unsufficient privileges for user ' . $sys_user . PHP_EOL);
    exit(1);
}

$usage_options  = '';
$usage_options .= 'u:'; // give me a user
$usage_options .= 'i:'; // give me the archive path to import
$usage_options .= 'o:'; // give me the output path of the csv file

/**
 * @psalm-return never-return
 */
function usage(): void
{
    global $argv;

    echo <<< EOT
Usage: $argv[0] -u user_name -i path_to_archive -o path_to_generated_csv_content

Generate the file mapping that is needed for project import.

  -u <user_name>  The user used to import
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
    assert(is_string($username));
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
$logger        = ProjectXMLImporter::getLogger();
$console       = new Log_ConsoleLogger();
$builder       = new User\XML\Import\UsersToBeImportedCollectionBuilder(
    $user_manager,
    $security,
    $xml_validator
);

try {
    $user = $user_manager->forceLogin($username);
    if (! $user->isActive() || ! $user->isSuperUser()) {
        throw new RuntimeException("User $username must be site administrator");
    }

    if (is_dir($archive_path)) {
        $archive = new Import\DirectoryArchive($archive_path);
    } else {
        $archive = new Import\ZipArchive($archive_path, ForgeConfig::get('tmp_dir'));
    }

    $collection = $builder->build($archive);
    $collection->toCSV($output);

    exit(0);
} catch (XML_ParseException $exception) {
    foreach ($exception->getErrors() as $parse_error) {
        $console->error($parse_error);
    }
} catch (Exception $exception) {
    $console->error($exception->getMessage());
}
exit(1);
