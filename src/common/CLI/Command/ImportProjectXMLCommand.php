<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\CLI\Command;

use ArtifactTypeFactory;
use BrokerLogger;
use FRSLog;
use Log_ConsoleLogger;
use ProjectCreator;
use ProjectHistoryDao;
use ProjectManager;
use ProjectXMLImporter;
use ProjectXMLImporterLogger;
use ReferenceManager;
use ServiceManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SystemEventProcessor_Factory;
use Tuleap\CLI\ConsoleLogger;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Project\ProjectDashboardDuplicator;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\Project\ProjectDashboardSaver;
use Tuleap\Dashboard\Project\ProjectDashboardXMLImporter;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FRSPermissionDao;
use Tuleap\FRS\UploadedLinksDao;
use Tuleap\FRS\UploadedLinksUpdater;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Project\UgroupDuplicator;
use Tuleap\Project\UserRemover;
use Tuleap\Project\UserRemoverDao;
use Tuleap\Project\XML\Import;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\Import\ImportNotValidException;
use Tuleap\Service\ServiceCreator;
use Tuleap\Widget\WidgetFactory;
use UGroupDao;
use UGroupManager;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use XML_RNGValidator;
use XML_Security;
use Symfony\Component\Console\Exception\InvalidArgumentException;

class ImportProjectXMLCommand extends Command
{

    const AUTHORIZED_CONFIGURATION_AUTOMAP =
        [
            "no-email"
        ];


    public function __construct()
    {
        parent::__construct("import-project-xml");
    }

    protected function configure()
    {
        $this
            ->setDescription('Import project from XML')
            ->addOption("project", "p", InputOption::VALUE_OPTIONAL, "The id or shortname of the project to import the archive")
            ->addOption("name", "s", InputOption::VALUE_OPTIONAL, "Override project name (when -p is not specified)")
            ->addOption("user-name", "u", InputOption::VALUE_REQUIRED, "The user used to import")
            ->addOption("archive-path", "i", InputOption::VALUE_REQUIRED, "The path of the archive of the exported XML + data")
            ->addOption("mapping-path", "m", InputOption::VALUE_OPTIONAL, "The path of the user mapping file")
            ->addOption("automap", "", InputOption::VALUE_OPTIONAL, "automap strategy")
            ->addOption("force")
            ->addOption("type", "", InputOption::VALUE_OPTIONAL)
            ->addOption("use-lame-password");
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('mapping-path') === null && $input->getOption('automap') === null) {
            throw new InvalidArgumentException("Need mapping-path (--mapping-path / -m) or automap (--automap)");
        }

        $configuration = new ImportConfig();

        $project_id = $input->getOption("project");
        $project_name_override = (string)$input->getOption("name");

        $username = $input->getOption("user-name");
        if ($username === null) {
            throw new InvalidArgumentException("Username missing (option --user-name / -u)");
        }

        $archive_path = $input->getOption("archive-path");
        if ($archive_path === null) {
            throw new InvalidArgumentException("Archive path missing (option --archive-path / -i)");
        }

        $automap = false;
        if ($input->getOption("automap") !== null) {
            $automap = true;
            $automap_arg = trim($input->getOption("automap"));
            $exception =
                "Automatically map users without taking email into account\n".
                "the second argument is the default action for accounts to\n".
                "create.\n".
                "Supported strategies:\n".
                "           no-email    Map with matching ldap id or username.\n".
                "                       Email is not taken into account\n".
                "Supported actions:\n".
                "           create:A    Create account with status Active\n".
                "           create:S    Create account with status Suspended\n\n\n";
            if (strpos($automap_arg, ',') !== false) {
                list($automap_strategy, $default_action) = explode(',', $automap_arg);
                if (! in_array($automap_strategy, self::AUTHORIZED_CONFIGURATION_AUTOMAP, true)) {
                    throw new \RuntimeException($exception . "Unsupported automap strategy, eg : --automap no-email,create:A");
                }
            } else {
                throw new \RuntimeException($exception . "When using automap, you need to specify a default action, eg: --automap no-email,create:A");
            }
        }

        $is_template = false;
        if ($input->getOption("type") !== null) {
            if (trim($input->getOption("type")) === 'template') {
                $is_template = true;
            } else {
                $exception =
                    "If the project is created, then it can be defined as a template\n".
                    "Unsupported type argument, eg --type template";
                throw new \InvalidArgumentException($exception);
            }
        }

        $mapping_path = $input->getOption("mapping-path");

        if ($input->getOption("force") !== null) {
            $configuration->setForce($input->getOption("force"));
        }

        $use_lame_password = false;
        if ($input->getOption("use-lame-password") !== null) {
            $use_lame_password = true;
        }

        if (empty($project_id) && posix_geteuid() != 0) {
            throw new \InvalidArgumentException('Need superuser powers to be able to create a project. Try importing in an existing project using -p.');
        }

        $user_manager  = \UserManager::instance();
        $event_manager = \EventManager::instance();
        $security      = new XML_Security();
        $xml_validator = new XML_RNGValidator();

        $transformer = new \User\XML\Import\MappingFileOptimusPrimeTransformer($user_manager, $use_lame_password);
        $console     = new \TruncateLevelLogger(new Log_ConsoleLogger(), \ForgeConfig::get('sys_logger_level'));
        $logger      = new ProjectXMLImporterLogger();
        $broker_log  = new BrokerLogger(array($logger, $console));
        $builder     = new \User\XML\Import\UsersToBeImportedCollectionBuilder(
            $user_manager,
            $broker_log,
            $security,
            $xml_validator
        );

        try {
            $user = $user_manager->forceLogin($username);
            if ((! $user->isSuperUser() && ! $user->isAdmin($project_id)) || ! $user->isActive()) {
                throw new \RuntimeException($GLOBALS['Language']->getText('project_import', 'invalid_user', array($username)));
            }

            $absolute_archive_path = realpath($archive_path);
            if (is_dir($absolute_archive_path)) {
                $archive = new Import\DirectoryArchive($absolute_archive_path);
            } else {
                $archive = new Import\ZipArchive($absolute_archive_path, \ForgeConfig::get('tmp_dir'));
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

            $user_finder = new \User\XML\Import\Mapping($user_manager, $users_collection, $broker_log);

            $ugroup_user_dao   = new \UGroupUserDao();
            $ugroup_manager    = new \UGroupManager();
            $ugroup_duplicator = new UgroupDuplicator(
                new UGroupDao(),
                $ugroup_manager,
                new \UGroupBinding($ugroup_user_dao, $ugroup_manager),
                $ugroup_user_dao,
                $event_manager
            );

            $send_notifications = false;
            $force_activation   = true;

            $frs_permissions_creator = new FRSPermissionCreator(
                new FRSPermissionDao(),
                new UGroupDao()
            );

            $widget_factory = new WidgetFactory(
                $user_manager,
                new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
                $event_manager
            );

            $widget_dao        = new DashboardWidgetDao($widget_factory);
            $project_dao       = new ProjectDashboardDao($widget_dao);
            $project_retriever = new ProjectDashboardRetriever($project_dao);
            $widget_retriever  = new DashboardWidgetRetriever($widget_dao);
            $duplicator        = new ProjectDashboardDuplicator(
                $project_dao,
                $project_retriever,
                $widget_dao,
                $widget_retriever,
                $widget_factory
            );

            $project_creator = new ProjectCreator(
                ProjectManager::instance(),
                ReferenceManager::instance(),
                $user_manager,
                $ugroup_duplicator,
                $send_notifications,
                $frs_permissions_creator,
                $duplicator,
                new ServiceCreator(),
                new LabelDao(),
                $force_activation
            );

            $xml_importer  = new ProjectXMLImporter(
                $event_manager,
                ProjectManager::instance(),
                $user_manager,
                $xml_validator,
                new UGroupManager(),
                $user_finder,
                ServiceManager::instance(),
                $broker_log,
                $ugroup_duplicator,
                $frs_permissions_creator,
                new UserRemover(
                    ProjectManager::instance(),
                    $event_manager,
                    new ArtifactTypeFactory(false),
                    new UserRemoverDao(),
                    $user_manager,
                    new ProjectHistoryDao(),
                    new UGroupManager()
                ),
                $project_creator,
                new UploadedLinksUpdater(new UploadedLinksDao(), FRSLog::instance()),
                new ProjectDashboardXMLImporter(
                    new ProjectDashboardSaver(
                        new ProjectDashboardDao(
                            $widget_dao
                        )
                    ),
                    $widget_factory,
                    $widget_dao,
                    $broker_log,
                    $event_manager
                )
            );

            if (empty($project_id)) {
                $factory = new SystemEventProcessor_Factory(
                    $broker_log,
                    \SystemEventManager::instance(),
                    $event_manager
                );
                $system_event_runner = new \Tuleap\Project\SystemEventRunner($factory);
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
        } catch (\XML_ParseException $exception) {
            foreach ($exception->getErrors() as $parse_error) {
                (new ConsoleLogger($output))->error('XML: '.$parse_error.' line:'.$exception->getSourceXMLForError($parse_error));
            }
        } catch (ImportNotValidException $exception) {
            (new ConsoleLogger($output))->error("Some natures used in trackers are not created on plateform.");
        } catch (\Exception $exception) {
            (new ConsoleLogger($output))->error(get_class($exception).': '.$exception->getMessage().' in '.$exception->getFile().' L'.$exception->getLine());
        } finally {
            if ($archive) {
                $archive->cleanUp();
            }
        }
    }
}
