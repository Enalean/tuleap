#!/usr/share/codendi/src/utils/php-launcher.sh
<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All Rights Reserved.
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

use Tuleap\Project\XML\Import;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\UgroupDuplicator;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FRSPermissionDao;
use Tuleap\Project\XML\Import\ImportNotValidException;
use Tuleap\Project\UserRemover;
use Tuleap\Project\UserRemoverDao;

$posix_user = posix_getpwuid(posix_geteuid());
$sys_user   = $posix_user['name'];
if ( $sys_user !== 'root' && $sys_user !== 'codendiadm' ) {
    fwrite(STDERR, 'Unsufficient privileges for user '.$sys_user.PHP_EOL);
    exit(1);
}

$usage_options  = '';
$usage_options .= 'p:'; // give me a project
$usage_options .= 'n:'; // give me a project name override
$usage_options .= 'u:'; // give me a user
$usage_options .= 'i:'; // give me the archive path to import
$usage_options .= 'm:'; // give me the path of the user mapping file
$usage_options .= 'h';  // help message
$usage_long_options = array(
    'force::',
    'help',
    'automap::',
    'type::',
    'use-lame-password',
);

function usage() {
    global $argv;

    echo <<< EOT
Usage: $argv[0] -p project -u user_name -i path_to_archive -m path_to_mapping

Import a project structure

  -p <project>    The id or shortname of the project to import the archive
  -n <name>       Override project name (when -p is not specified)
  -u <user_name>  The user used to import
  -i <path>       The path of the archive of the exported XML + data
  -m <path>       The path of the user mapping file
  -h              Display this help

Long options:
  --automap=strategy,action    Automatically map users without taking email into account
                               the second argument is the default action for accounts to
                               create.
                               Supported strategies:
                                   no-email    Map with matching ldap id or username.
                                               Email is not taken into account
                               Supported actions:
                                   create:A    Create account with status Active
                                   create:S    Create account with status Suspended

  --force=<something>          Force something
                               Supported values:
                                   references

  --type=template              If the project is created, then it can be defined as a template

  --help                       Display this help

EOT;
    exit(1);
}

$configuration = new ImportConfig();

$arguments = getopt($usage_options, $usage_long_options);

if (isset($arguments['h']) || isset($arguments['help'])) {
    usage();
    exit(0);
}

if (! isset($arguments['p'])) {
    $project_id = null;
} else {
    $project_id = $arguments['p'];
}

if (! isset($arguments['n'])) {
    $project_name_override = null;
} else {
    $project_name_override = (string)$arguments['n'];
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

$automap = false;
if (isset($arguments['automap'])) {
    $automap = true;
    $automap_arg = trim($arguments['automap']);
    if (strpos($automap_arg, ',') !== false) {
        list($automap_strategy, $default_action) = explode(',', $automap_arg);
        if ($automap_strategy !== "no-email") {
            fwrite(STDERR, 'Unsupported automap strategy'.PHP_EOL);
            exit(1);
        }
    } else {
        fwrite(STDERR, 'When using automap, you need to specify a default action, eg: --automap=no-email,create:A'.PHP_EOL);
        exit(1);
    }
}

$is_template = false;
if (isset($arguments['type']) && trim($arguments['type']) != '') {
    if (trim($arguments['type']) === 'template') {
        $is_template = true;
    } else {
        usage();
    }
}

if (isset($arguments['m'])) {
    $mapping_path = $arguments['m'];
} elseif (! $automap) {
    usage();
}

if (isset($arguments['force']) && trim($arguments['force']) != '') {
    $configuration->setForce($arguments['force']);
}

$use_lame_password = false;
if (isset($arguments['use-lame-password'])) {
    $use_lame_password = true;
}

if(empty($project_id) && posix_geteuid() != 0) {
    fwrite(STDERR, 'Need superuser powers to be able to create a project. Try importing in an existing project using -p.'.PHP_EOL);
    exit(1);
}

$user_manager  = UserManager::instance();
$security      = new XML_Security();
$xml_validator = new XML_RNGValidator();

$transformer = new User\XML\Import\MappingFileOptimusPrimeTransformer($user_manager, $use_lame_password);
$console     = new TruncateLevelLogger(new Log_ConsoleLogger(), ForgeConfig::get('sys_logger_level'));
$logger      = new ProjectXMLImporterLogger();
$broker_log  = new BrokerLogger(array($logger, $console));
$builder     = new User\XML\Import\UsersToBeImportedCollectionBuilder(
    $user_manager,
    $broker_log,
    $security,
    $xml_validator
);

try {
    $user = $user_manager->forceLogin($username);
    if ((! $user->isSuperUser() && ! $user->isAdmin($project_id)) || ! $user->isActive()) {
        throw new RuntimeException($GLOBALS['Language']->getText('project_import', 'invalid_user', array($username)));
    }

    $absolute_archive_path = realpath($archive_path);
    if (is_dir($absolute_archive_path)) {
        $archive = new Import\DirectoryArchive($absolute_archive_path);
    } else {
        $archive = new Import\ZipArchive($absolute_archive_path, ForgeConfig::get('tmp_dir'));
    }

    $archive->extractFiles();

    if ($automap) {
        $collection_from_archive = $builder->buildWithoutEmail($archive);
        $users_collection        = $transformer->transformWithoutMap($collection_from_archive, $default_action);
    } else {
        $collection_from_archive = $builder->build($archive);
        $users_collection        = $transformer->transform($collection_from_archive, $mapping_path);
    }
    $users_collection->process($user_manager, $broker_log);

    $user_finder = new User\XML\Import\Mapping($user_manager, $users_collection, $broker_log);

    $ugroup_user_dao    = new UGroupUserDao();
    $ugroup_manager     = new UGroupManager();
    $ugroup_duplicator  = new UgroupDuplicator(
        new UGroupDao(),
        $ugroup_manager,
        new UGroupBinding($ugroup_user_dao, $ugroup_manager),
        $ugroup_user_dao,
        EventManager::instance()
    );

    $xml_importer  = new ProjectXMLImporter(
        EventManager::instance(),
        ProjectManager::instance(),
        UserManager::instance(),
        $xml_validator,
        new UGroupManager(),
        $user_finder,
        ServiceManager::instance(),
        $broker_log,
        $ugroup_duplicator,
        new FRSPermissionCreator(new FRSPermissionDao(), new UGroupDao()),
        new UserRemover(
            ProjectManager::instance(),
            EventManager::instance(),
            new ArtifactTypeFactory(false),
            new UserRemoverDao(),
            UserManager::instance(),
            new ProjectHistoryDao(),
            new UGroupManager()
        )
    );

    try {
        if (empty($project_id)) {
            $factory = new SystemEventProcessor_Factory($broker_log, SystemEventManager::instance(), EventManager::instance());
            $system_event_runner = new Tuleap\Project\SystemEventRunner($factory);
            $xml_importer->importNewFromArchive(
                $configuration,
                $archive,
                $system_event_runner,
                $is_template,
                $project_name_override
            );
        } else {
            $xml_importer->importFromArchive($configuration, $project_id, $archive);
        }
    } catch (ImportNotValidException $exception) {
        $broker_log->error("Some natures used in trackers are not created on plateform.");
    }

    $archive->cleanUp();

    exit(0);
} catch (XML_ParseException $exception) {
    foreach ($exception->getErrors() as $parse_error) {
        $broker_log->error('XML: '.$parse_error.' line:'.$exception->getSourceXMLForError($parse_error));
    }
} catch (Exception $exception) {
    $broker_log->error(get_class($exception).': '.$exception->getMessage().' in '.$exception->getFile().' L'.$exception->getLine());
}
exit(1);
