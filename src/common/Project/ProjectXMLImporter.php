<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../../www/include/account.php';

use Psr\Log\LoggerInterface;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsChecker;
use Tuleap\Dashboard\Project\DisabledProjectWidgetsDao;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Project\ProjectDashboardSaver;
use Tuleap\Dashboard\Project\ProjectDashboardXMLImporter;
use Tuleap\Dashboard\Project\RecentlyVisitedProjectDashboardDao;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\DB\ReconnectAfterALongRunningProcess;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FRSPermissionDao;
use Tuleap\FRS\UploadedLinksDao;
use Tuleap\FRS\UploadedLinksUpdater;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\Admin\DescriptionFields\ProjectRegistrationSubmittedFieldsCollection;
use Tuleap\Project\Admin\ProjectUGroup\CannotCreateUGroupException;
use Tuleap\Project\Admin\ProjectUGroup\ProjectImportCleanupUserCreatorFromAdministrators;
use Tuleap\Project\Banner\BannerCreator;
use Tuleap\Project\Banner\BannerDao;
use Tuleap\Project\Banner\UserCanEditBannerPermission;
use Tuleap\Project\DescriptionFieldsDao;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\Event\ProjectXMLImportPreChecksEvent;
use Tuleap\Project\ImportFromArchive;
use Tuleap\Project\ProjectCreationData;
use Tuleap\Project\ProjectXMLExporter;
use Tuleap\Project\Registration\Template\Upload\CheckArchiveContent;
use Tuleap\Project\Service\ProjectDefinedService;
use Tuleap\Project\Service\ServiceDao;
use Tuleap\Project\Service\ServiceLinkDataBuilder;
use Tuleap\Project\SystemEventRunnerInterface;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdder;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithoutStatusCheckAndNotifications;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Project\UserPermissionsDao;
use Tuleap\Project\UserRemover;
use Tuleap\Project\UserRemoverDao;
use Tuleap\Project\XML\Import\ArchiveInterface;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\Import\ImportNotValidException;
use Tuleap\Project\XML\XMLFileContentRetriever;
use Tuleap\Widget\WidgetFactory;
use Tuleap\XML\MappingsRegistry;
use Tuleap\XML\PHPCast;
use User\XML\Import\IFindUserFromXMLReference;

class ProjectXMLImporter implements ImportFromArchive //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public function __construct(
        private readonly EventManager $event_manager,
        private readonly ProjectManager $project_manager,
        private readonly UserManager $user_manager,
        private readonly XML_RNGValidator $xml_validator,
        private readonly UGroupManager $ugroup_manager,
        private readonly IFindUserFromXMLReference $user_finder,
        private readonly ServiceManager $service_manager,
        private readonly LoggerInterface $logger,
        private readonly FRSPermissionCreator $frs_permissions_creator,
        private readonly UserRemover $user_remover,
        private readonly ProjectMemberAdder $project_member_adder,
        private readonly ProjectCreator $project_creator,
        private readonly UploadedLinksUpdater $uploaded_links_updater,
        private readonly ProjectDashboardXMLImporter $dashboard_importer,
        private readonly SynchronizedProjectMembershipDao $synchronized_project_membership_dao,
        private readonly XMLFileContentRetriever $XML_file_content_retriever,
        private readonly DescriptionFieldsFactory $description_fields_factory,
        private readonly ReconnectAfterALongRunningProcess $db_connection,
        private readonly ServiceDao $service_dao,
        private readonly ServiceLinkDataBuilder $link_data_builder,
        private readonly BannerCreator $banner_creator,
    ) {
    }

    public static function build(IFindUserFromXMLReference $user_finder, ProjectCreator $project_creator, ?LoggerInterface $logger = null): self
    {
        $event_manager  = EventManager::instance();
        $user_manager   = UserManager::instance();
        $ugroup_manager = new UGroupManager();
        if ($logger) {
            $logger = new BrokerLogger([$logger, self::getLogger()]);
        } else {
            $logger = self::getLogger();
        }
        $frs_permissions_creator = new FRSPermissionCreator(
            new FRSPermissionDao(),
            new UGroupDao(),
            new ProjectHistoryDao()
        );

        $widget_factory                      = new WidgetFactory(
            $user_manager,
            new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
            $event_manager
        );
        $widget_dao                          = new DashboardWidgetDao($widget_factory);
        $project_dao                         = new ProjectDashboardDao($widget_dao);
        $synchronized_project_membership_dao = new SynchronizedProjectMembershipDao();

        return new self(
            $event_manager,
            ProjectManager::instance(),
            $user_manager,
            new XML_RNGValidator(),
            $ugroup_manager,
            $user_finder,
            ServiceManager::instance(),
            $logger,
            $frs_permissions_creator,
            new UserRemover(
                ProjectManager::instance(),
                $event_manager,
                new ArtifactTypeFactory(false),
                new UserRemoverDao(),
                $user_manager,
                new ProjectHistoryDao(),
                new UGroupManager(),
                new UserPermissionsDao(),
            ),
            ProjectMemberAdderWithoutStatusCheckAndNotifications::build(),
            $project_creator,
            new UploadedLinksUpdater(new UploadedLinksDao(), FRSLog::instance()),
            new ProjectDashboardXMLImporter(
                new ProjectDashboardSaver(
                    $project_dao,
                    new RecentlyVisitedProjectDashboardDao(),
                    new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
                ),
                $widget_factory,
                $widget_dao,
                $logger,
                $event_manager,
                new DisabledProjectWidgetsChecker(new DisabledProjectWidgetsDao())
            ),
            $synchronized_project_membership_dao,
            new XMLFileContentRetriever(),
            new DescriptionFieldsFactory(
                new DescriptionFieldsDao()
            ),
            DBFactory::getMainTuleapDBConnection(),
            new ServiceDao(),
            new ServiceLinkDataBuilder(),
            new BannerCreator(new BannerDao()),
        );
    }

    public static function getLogger(): LoggerInterface
    {
        return BackendLogger::getDefaultLogger('project_xml_import_syslog');
    }

    /**
     * @return Ok<Project>|Err<Fault>
     * @throws ImportNotValidException
     */
    public function importWithProjectData(
        ImportConfig $configuration,
        ArchiveInterface $archive,
        SystemEventRunnerInterface $event_runner,
        ProjectCreationData $project_creation_data,
    ): Ok|Err {
        $this->logger->info('Start creating new project from archive ' . $archive->getExtractionPath());

        return $this->getProjectXMLFromArchive($archive)
            ->andThen(function (SimpleXMLElement $xml_element) use ($configuration, $archive, $event_runner, $project_creation_data) {
                $this->assertXMLisValid($xml_element);

                $project = $this->createProject($event_runner, $project_creation_data);

                $this->importContent($configuration, $project, $xml_element, $archive->getExtractionPath());

                return Result::ok($project);
            });
    }

    /**
     * @return Ok<true>|Err<Fault>
     * @throws ImportNotValidException
     */
    public function importNewFromArchive(
        ImportConfig $configuration,
        ArchiveInterface $archive,
        Tuleap\Project\SystemEventRunnerInterface $event_runner,
        $is_template,
        $project_name_override = null,
    ): Ok|Err {
        $this->logger->info('Start importing new project from archive ' . $archive->getExtractionPath());

        return $this->getProjectXMLFromArchive($archive)
            ->andThen(function (SimpleXMLElement $xml_element) use ($configuration, $archive, $event_runner, $is_template, $project_name_override) {
                $this->assertXMLisValid($xml_element);

                $project_creation_data = $this->getProjectCreationDataFromXml($xml_element, $is_template, $project_name_override);

                $project = $this->createProject($event_runner, $project_creation_data);

                $this->importContent($configuration, $project, $xml_element, $archive->getExtractionPath());

                return Result::ok(true);
            });
    }

    private function getProjectCreationDataFromXml(SimpleXMLElement $xml, bool $is_template, ?string $project_name_override): ProjectCreationData
    {
        if (! empty($project_name_override)) {
            $xml['unix-name'] = $project_name_override;
        }

        $project_creation_data = ProjectCreationData::buildFromXML(
            $xml,
            $this->xml_validator,
            ServiceManager::instance(),
            $this->logger
        );

        if ($is_template) {
            $this->logger->info('The project will be a template');
            $project_creation_data->setIsTemplate();
        }

        if ($this->description_fields_factory->isLegacyLongDescriptionFieldExisting()) {
            $long_description_tagname = 'long-description';
            $project_creation_data->setDataFields(
                ProjectRegistrationSubmittedFieldsCollection::buildFromArray([
                    101 => (string) $xml->$long_description_tagname,
                ])
            );
        }

        $this->logger->debug('ProjectMetadata extracted from XML, now create in DB');

        return $project_creation_data;
    }

    private function createProject(
        Tuleap\Project\SystemEventRunnerInterface $event_runner,
        ProjectCreationData $project_creation_data,
    ) {
        $event_runner->checkPermissions();

        $this->logger->info(sprintf('Create project %s', $project_creation_data->getUnixName()));

        $project = $this->project_creator->processProjectCreation($project_creation_data);

        $this->logger->info("Execute system events to finish creation of project {$project->getID()}, this can take a while...");
        $event_runner->runSystemEvents();
        $this->logger->info('System events success');

        return $project;
    }

    #[\Override]
    public function importFromArchive(
        ImportConfig $configuration,
        int $project_id,
        ArchiveInterface $archive,
        CheckArchiveContent $check_archive_content,
        Closure $pre_check_xml_is_valid_event,
    ): Ok|Err {
        $this->logger->info('Start importing into existing project from archive ' . $archive->getExtractionPath());

        return $this->getProjectXMLFromArchive($archive)
            ->andThen(
                function (SimpleXMLElement $xml_element) use ($pre_check_xml_is_valid_event) {
                    try {
                        $this->event_manager->processEvent($pre_check_xml_is_valid_event($xml_element));
                        return Result::ok($xml_element);
                    } catch (ImportNotValidException $exception) {
                        return Result::err(Fault::fromThrowable($exception));
                    }
                }
            )->andThen(fn (SimpleXMLElement $xml_element) => $check_archive_content->checkArchiveContent($xml_element))
            ->andThen(function (SimpleXMLElement $xml_element) use ($configuration, $project_id, $archive) {
                $this->importFromXMLIntoExistingProject($configuration, $project_id, $xml_element, $archive->getExtractionPath());

                return Result::ok(true);
            });
    }

    /**
     * @return Ok<true>|Err<Fault>
     */
    public function import(ImportConfig $configuration, $project_id, $xml_file_path): Ok|Err
    {
        $this->logger->info('Start importing from file ' . $xml_file_path);

        return $this->XML_file_content_retriever->getSimpleXMLElementFromFilePath($xml_file_path)
            ->andThen(function (SimpleXMLElement $xml_element) use ($configuration, $project_id) {
                $this->importFromXMLIntoExistingProject($configuration, $project_id, $xml_element, '');

                return Result::ok(true);
            });
    }

    private function importFromXMLIntoExistingProject(ImportConfig $configuration, $project_id, SimpleXMLElement $xml_element, $extraction_path): void
    {
        $project = $this->project_manager->getValidProjectByShortNameOrId($project_id);
        $this->toggleServices($project, $xml_element);
        $this->addCustomServices($project, $xml_element);

        $this->importContent($configuration, $project, $xml_element, $extraction_path);
    }

    private function toggleServices(Project $project, SimpleXMLElement $xml_element): void
    {
        if ($xml_element->services) {
            $services = [];
            foreach ($project->getServices() as $service) {
                $short_name            = $service->getShortName();
                $services[$short_name] = false;
            }
            foreach ($xml_element->services->service as $service) {
                $short_name            = (string) $service['shortname'];
                $services[$short_name] = \Tuleap\XML\PHPCast::toBoolean($service['enabled']);
            }
            $services[Service::SUMMARY] = true;
            $services[Service::ADMIN]   = true;
            foreach ($services as $short_name => $enabled) {
                $this->service_manager->toggleServiceUsage($project, $short_name, $enabled);
            }
        }
    }

    private function addCustomServices(Project $project, SimpleXMLElement $xml_element): void
    {
        if ($xml_element->services) {
            foreach ($xml_element->services->{'project-defined-service'} as $service_info) {
                $service                        = ProjectDefinedService::forProjectServiceCreation($project);
                $service->data['label']         = (string) $service_info['label'];
                $service->data['description']   = (string) $service_info['description'];
                $service->data['link']          = (string) $service_info['link'];
                $service->data['is_in_new_tab'] = isset($service_info['is_in_new_tab']) && PHPCast::toBoolean($service_info['is_in_new_tab']);

                $this->service_dao->create(
                    (int) $project->getID(),
                    $service->getLabel(),
                    $service->getIcon(),
                    $service->getDescription(),
                    $service->getShortName(),
                    $this->link_data_builder->substituteVariablesInLink($project, $service->getUrl()),
                    true,
                    true,
                    $service->getScope(),
                    $service->getRank(),
                    $service->isOpenedInNewTab()
                );
            }
        }
    }

    /**
     * @return Ok<true>|Err<Fault>
     */
    public function collectBlockingErrorsWithoutImporting($project_id, $xml_file_path)
    {
        $this->logger->info('Start collecting errors from file ' . $xml_file_path);

        return $this->XML_file_content_retriever->getSimpleXMLElementFromFilePath($xml_file_path)
            ->andThen(function (SimpleXMLElement $xml_element) use ($project_id) {
                $project = $this->project_manager->getValidProjectByShortNameOrId($project_id);

                $errors = $this->collectBlockingErrorsWithoutImportingContent($project, $xml_element);
                if ($errors) {
                    return Result::err(Fault::fromMessage($errors));
                }

                return Result::ok(true);
            });
    }

    private function importContent(ImportConfig $configuration, Project $project, SimpleXMLElement $xml_element, $extraction_path)
    {
        $this->logger->info('Importing project in project ' . $project->getUnixName());

        $user_creator = $this->user_manager->getCurrentUser();

        $this->importUgroups($project, $xml_element, $user_creator);

        if (isset($xml_element->banner)) {
            $this->banner_creator->addBanner(
                new UserCanEditBannerPermission($project),
                (string) $xml_element->banner,
            );
        }

        $frs = new FRSXMLImporter(
            $this->logger,
            new FRSPackageFactory(),
            new FRSReleaseFactory(),
            new FRSFileFactory($this->logger),
            $this->user_finder,
            $this->ugroup_manager,
            $this->frs_permissions_creator,
            $this->uploaded_links_updater
        );

        $frs_release_mapping = [];
        $frs->import($configuration, $project, $xml_element, $extraction_path, $frs_release_mapping);

        $mappings_registery = new MappingsRegistry();
        $mappings_registery->add($frs_release_mapping, FRSXMLImporter::MAPPING_KEY);

        $this->logger->info('Ask to plugin to import data from XML');
        $this->event_manager->processEvent(
            Event::IMPORT_XML_PROJECT,
            [
                'logger'              => $this->logger,
                'project'             => $project,
                'xml_content'         => $xml_element,
                'extraction_path'     => $extraction_path,
                'user_finder'         => $this->user_finder,
                'mappings_registery'  => $mappings_registery,
                'configuration'       => $configuration,
            ]
        );

        $this->importDashboards($xml_element, $user_creator, $project, $mappings_registery);

        $this->logger->info('Finish importing project in project ' . $project->getUnixName() . ' id ' . $project->getID());
    }

    /**
     * @return string
     */
    private function collectBlockingErrorsWithoutImportingContent(Project $project, SimpleXMLElement $xml_element)
    {
        $errors = '';

        $this->logger->info('Ask plugins to check if errors might be raised from importing the XML');
        $this->event_manager->processEvent(
            Event::COLLECT_ERRORS_WITHOUT_IMPORTING_XML_PROJECT,
            [
                'logger'      => $this->logger,
                'project'     => $project,
                'xml_content' => $xml_element,
                'user_finder' => $this->user_finder,
                'errors'      => &$errors,
            ]
        );
        return $errors;
    }

    private function importUgroups(Project $project, SimpleXMLElement $xml_element, PFUser $user_creator)
    {
        $this->logger->info('Check if there are ugroups to add');

        if ($xml_element->ugroups) {
            $this->logger->info('Some ugroups are defined in the XML');

            if ((string) $xml_element->ugroups['mode'] === ProjectXMLExporter::UGROUPS_MODE_SYNCHRONIZED) {
                $this->synchronized_project_membership_dao->enable($project);
            }

            [$ugroups_in_xml, $project_members] = $this->getUgroupsFromXMLToAdd($project, $xml_element->ugroups);

            foreach ($project_members as $user) {
                $this->addProjectMember($project, $user, $user_creator);
            }

            foreach ($ugroups_in_xml as $ugroup_def) {
                $ugroup = $this->ugroup_manager->getDynamicUGoupByName($project, $ugroup_def['name']);

                if (empty($ugroup)) {
                    $this->logger->debug('Creating empty ugroup ' . $ugroup_def['name']);
                    try {
                        $new_ugroup_id = $this->ugroup_manager->createEmptyUgroup(
                            $project->getID(),
                            $ugroup_def['name'],
                            $ugroup_def['description']
                        );
                    } catch (CannotCreateUGroupException $e) {
                        $this->logger->error($e->getMessage());
                        continue;
                    }
                    $ugroup = $this->ugroup_manager->getById($new_ugroup_id);
                }

                if (empty($ugroup_def['users'])) {
                    $this->logger->debug('No user to add in ugroup ' . $ugroup_def['name']);
                } else {
                    $this->logger->debug('Adding users to ugroup ' . $ugroup_def['name']);
                }

                foreach ($ugroup_def['users'] as $user) {
                    $this->logger->debug('Adding user ' . $user->getUserName() . ' to ' . $ugroup_def['name']);
                    $ugroup->addUser($user, $user_creator);
                }

                if ($ugroup->getId() === ProjectUGroup::PROJECT_ADMIN) {
                    $this->cleanProjectAdminsFromUserCreator($ugroup, $ugroup_def['users'], $user_creator);
                }
            }

            $this->cleanProjectMembersFromUserCreator($project, $project_members, $user_creator);
            $this->logger->debug('Import of ugroups completed');
        }
    }

    private function addProjectMember(Project $project, PFUser $user, PFUser $user_creator)
    {
        $this->logger->info("Add user {$user->getUserName()} to project.");

        $this->project_member_adder->addProjectMemberWithFeedback($user, $project, $user_creator);
    }

    private function cleanProjectMembersFromUserCreator(Project $project, array $users, PFUser $user_creator)
    {
        if (! empty($users) && ! in_array($user_creator, $users)) {
            $this->user_remover->removeUserFromProject($project->getID(), $user_creator->getId());
        }
    }

    private function cleanProjectAdminsFromUserCreator(ProjectUGroup $ugroup, array $users, PFUser $user_creator): void
    {
        if (! empty($users) && ! in_array($user_creator, $users)) {
            $this->event_manager->processEvent(
                new ProjectImportCleanupUserCreatorFromAdministrators($user_creator, $ugroup)
            );
            $ugroup->removeUser($user_creator, $user_creator);
        }
    }

    /**
     *
     * @return array
     */
    private function getUgroupsFromXMLToAdd(Project $project, SimpleXMLElement $xml_element_ugroups)
    {
        $ugroups         = [];
        $project_members = [];

        $rng_path = realpath(dirname(__FILE__) . '/../xml/resources/ugroups.rng');
        $this->xml_validator->validate($xml_element_ugroups, $rng_path);
        $this->logger->debug('XML Ugroups is valid');

        foreach ($xml_element_ugroups->ugroup as $ugroup) {
            $ugroup_name        = (string) $ugroup['name'];
            $ugroup_description = (string) $ugroup['description'];

            $dynamic_ugroup_id = $this->ugroup_manager->getDynamicUGoupIdByName($ugroup_name);
            if ($this->ugroup_manager->getUGroupByName($project, $ugroup_name) && empty($dynamic_ugroup_id)) {
                $this->logger->debug("Ugroup $ugroup_name already exists in project -> skipped");
                continue;
            }

            $users = $this->getListOfUgroupMember($ugroup);

            if ($dynamic_ugroup_id === ProjectUGroup::PROJECT_MEMBERS) {
                $project_members = $users;
            } else {
                $ugroups[$ugroup_name]['name']        = $ugroup_name;
                $ugroups[$ugroup_name]['description'] = $ugroup_description;
                $ugroups[$ugroup_name]['users']       = $users;
            }
        }

        return [$ugroups, $project_members];
    }

    /**
     *
     * @return PFUser[]
     */
    private function getListOfUgroupMember(SimpleXMLElement $ugroup)
    {
        $ugroup_members = [];

        foreach ($ugroup->members->member as $xml_member) {
            $ugroup_members[] = $this->user_finder->getUser($xml_member);
        }

        return $ugroup_members;
    }

    /**
     * @return Ok<SimpleXMLElement>|Err<Fault>
     */
    private function getProjectXMLFromArchive(ArchiveInterface $archive): Ok|Err
    {
        $xml_contents = $archive->getProjectXML();

        if (! $xml_contents) {
            return Result::err(Fault::fromMessage('No content available in archive for file ' . ArchiveInterface::PROJECT_FILE));
        }

        return $this->XML_file_content_retriever
            ->getSimpleXMLElementFromString($xml_contents)
            ->andThen(function (SimpleXMLElement $xml) {
                $this->db_connection->reconnectAfterALongRunningProcess();

                return Result::ok($xml);
            });
    }

    private function importDashboards(SimpleXMLElement $xml_element, PFUser $user, Project $project, MappingsRegistry $mapping_registry)
    {
        $this->dashboard_importer->import($xml_element, $user, $project, $mapping_registry);
    }

    /**
     * @throw ImportNotValidException
     */
    private function assertXMLisValid(SimpleXMLElement $xml_element): void
    {
        $event = new ProjectXMLImportPreChecksEvent($xml_element);
        $this->event_manager->processEvent($event);
    }
}
