#!/usr/share/tuleap/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;
use Tuleap\Project\XML\Export;

$posix_user = posix_getpwuid(posix_geteuid());
$sys_user   = $posix_user['name'];
if ($sys_user !== 'root' && $sys_user !== 'codendiadm') {
    fwrite(STDERR, 'Unsufficient privileges for user ' . $sys_user . PHP_EOL);
    exit(1);
}

$usage_options  = '';
$usage_options .= 'p:'; // give me a project
$usage_options .= 'u:'; // give me a user
$usage_options .= 't:'; // give me a tracker
$usage_options .= 'o:'; // give me the archive path
$usage_options .= 'f';  // should we force the export
$usage_options .= 'h';  // should we display the usage
$usage_options .= 'x';  // should we display the XML content

$long_options = array(
    'dir', 'all'
);

/**
 * @psalm-return never-return
 */
function usage(): void
{
    global $argv;

    echo <<< EOT
Usage: $argv[0] -p project -u user_name -o path_to_archive [-t tracker_id] [-f] [-x]

Dump a project structure to XML format

  -p <project>    The id or shortname of the project to export
  -u <user_name>  The user used to export
  -t <tracker_id> The id of the tracker to include in the export (optional)
  -o <path>       The full path where the archive (project in XML + data) will be created (example: /tmp/archive.zip)
  -f              Force the export (for example if there are too many artifacts). Use at your own risks.
  -x              Display the XML content
  --dir           Generate a Directory archive (default is zip archive)
  --all           Export all compatible services with data (Tracker)
  -h              Display this help


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
    $project_id = $arguments['p'];
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
    assert(is_string($output));
}

$display_xml = false;
if (isset($arguments['x'])) {
    $display_xml = true;
}

$options = array();
if (isset($arguments['t'])) {
    $options['tracker_id'] = (int) $arguments['t'];
}

$options['force'] = isset($arguments['f']);
$options['all']   = false;
if (isset($arguments['all'])) {
    $options['all'] = true;
}

try {
    $project =  ProjectManager::instance()->getValidProjectByShortNameOrId($project_id);

    $rng_validator    = new XML_RNGValidator();
    $users_collection = new UserXMLExportedCollection($rng_validator, new XML_SimpleXMLCDATAFactory());

    $xml_exporter = new ProjectXMLExporter(
        EventManager::instance(),
        new UGroupManager(),
        $rng_validator,
        new UserXMLExporter(UserManager::instance(), $users_collection),
        new SynchronizedProjectMembershipDetector(new SynchronizedProjectMembershipDao()),
        ProjectXMLExporter::getLogger(),
    );

    if (isset($arguments['dir'])) {
        $archive = new Export\DirectoryArchive($output);
    } else {
        $archive = new Export\ZipArchive($output);
    }

    $xml_security = new XML_Security();
    $xml_security->enableExternalLoadOfEntities();

    $user = UserManager::instance()->forceLogin($username);
    $temporary_dump_path_on_filesystem = $archive->getArchivePath() . time();

    $xml_content       = $xml_exporter->export($project, $options, $user, $archive, $temporary_dump_path_on_filesystem);
    $users_xml_content = $users_collection->toXML();

    if ($display_xml) {
        echo $xml_content;
        echo PHP_EOL;
        echo $users_xml_content;
    }

    $archive->addFromString(Export\ArchiveInterface::PROJECT_FILE, $xml_content);
    $archive->addFromString(Export\ArchiveInterface::USER_FILE, $users_xml_content);

    $xml_security->disableExternalLoadOfEntities();

    $archive->close();

    $system_command = new System_Command();
    $command = "rm -rf $temporary_dump_path_on_filesystem";
    $system_command->exec($command);

    fwrite(STDOUT, "Archive $output created." . PHP_EOL);

    exit(0);
} catch (XML_ParseException $exception) {
    fwrite(STDERR, "*** PARSE ERROR: " . $exception->getIndentedXml() . PHP_EOL);
    foreach ($exception->getErrors() as $parse_error) {
        fwrite(STDERR, "*** PARSE ERROR: " . $parse_error . PHP_EOL);
    }
    fwrite(STDERR, "RNG path: " . $exception->getRngPath() . PHP_EOL);
    exit(1);
} catch (Project_NotFoundException $exception) {
    fwrite(STDERR, "*** ERROR: Invalid -p <project> parameter: project not found" . PHP_EOL);
    exit(1);
} catch (Exception $exception) {
    fwrite(STDERR, "*** ERROR: " . $exception->getMessage() . PHP_EOL);
    exit(1);
}

class ProjectXMLExport_Archive extends ZipArchive
{

    private $archive_path;

    public function open($filename, $flags = null)
    {
        $this->archive_path = $filename;
        return mkdir($filename, 0700, true);
    }

    public function close()
    {
        return true;
    }

    public function addEmptyDir($dirname)
    {
        if (!is_dir($this->archive_path . DIRECTORY_SEPARATOR . $dirname)) {
            return mkdir($this->archive_path . DIRECTORY_SEPARATOR . $dirname, 0700);
        }
        return true;
    }

    public function addFile($filename, $localname = null, $start = 0, $length = 0)
    {
        return copy($filename, $this->archive_path . DIRECTORY_SEPARATOR . $localname);
    }

    public function addFromString($localname, $contents)
    {
        file_put_contents($this->archive_path . DIRECTORY_SEPARATOR . $localname, $contents);
        return true;
    }
}
