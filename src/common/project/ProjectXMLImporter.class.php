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

require_once "account.php";

use Tuleap\Project\XML\Import\ArchiveInterface;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\Import\ImportNotValidException;
use Tuleap\XML\MappingsRegistry;
use Tuleap\Project\UgroupDuplicator;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\Project\UserRemover;

/**
 * This class import a project from a xml content
 */
class ProjectXMLImporter {

    /**
     * @var UgroupDuplicator
     */
    private $ugroup_duplicator;

    /** @var EventManager */
    private $event_manager;

    /** @var $project_manager */
    private $project_manager;

    /** @var UserManager */
    private $user_manager;

    /** @var XML_RNGValidator */
    private $xml_validator;

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var User\XML\Import\IFindUserFromXMLReference */
    private $user_finder;

    /** @var Logger */
    private $logger;

    /** @var ProjectCreator */
    private $project_creator;

    /** @var ServiceManager */
    private $service_manager;

    /** @var FRSPermissionCreator */
    private $frs_permissions_creator;

    /**
     * @var UserRemover
     */
    private $user_removal;

    public function __construct(
        EventManager $event_manager,
        ProjectManager $project_manager,
        UserManager $user_manager,
        XML_RNGValidator $xml_validator,
        UGroupManager $ugroup_manager,
        User\XML\Import\IFindUserFromXMLReference $user_finder,
        ServiceManager $service_manager,
        Logger $logger,
        UgroupDuplicator $ugroup_duplicator,
        FRSPermissionCreator $frs_permissions_creator,
        UserRemover $user_removal
    ) {
        $this->event_manager           = $event_manager;
        $this->project_manager         = $project_manager;
        $this->user_manager            = $user_manager;
        $this->xml_validator           = $xml_validator;
        $this->ugroup_manager          = $ugroup_manager;
        $this->user_finder             = $user_finder;
        $this->logger                  = $logger;
        $this->service_manager         = $service_manager;
        $this->ugroup_duplicator       = $ugroup_duplicator;
        $this->frs_permissions_creator = $frs_permissions_creator;
        $this->user_removal            = $user_removal;

        $send_notifications = false;
        $force_activation   = true;

        $this->project_creator = new ProjectCreator(
            $this->project_manager,
            ReferenceManager::instance(),
            $this->ugroup_duplicator,
            $send_notifications,
            $frs_permissions_creator,
            $force_activation
        );
    }

    public function importNewFromArchive(
        ImportConfig $configuration,
        ArchiveInterface $archive,
        Tuleap\Project\SystemEventRunner $event_runner,
        $is_template,
        $project_name_override = null
    ) {
        $this->logger->info('Start importing new project from archive ' . $archive->getExtractionPath());

        $xml_element = $this->getProjectXMLFromArchive($archive);

        $error                 = false;
        $params['xml_content'] = $xml_element;
        $params['error']       = &$error;
        $this->event_manager->processEvent(
            Event::IMPORT_XML_IS_PROJECT_VALID,
            $params
        );

        if ($params['error']) {
            throw new ImportNotValidException();
        }

        if (!empty($project_name_override)) {
            $xml_element['unix-name'] = $project_name_override;
        }

        $project = $this->createProject($xml_element, $event_runner, $is_template);

        $this->importContent($configuration, $project, $xml_element, $archive->getExtractionPath());
    }

    private function createProject(
        SimpleXMLElement $xml,
        Tuleap\Project\SystemEventRunner $event_runner,
        $is_template
    ) {
        $event_runner->checkPermissions();

        $this->logger->info("Create project {$xml['unix-name']}");
        $data = ProjectCreationData::buildFromXML($xml,
            100,
            $this->xml_validator,
            ServiceManager::instance(),
            $this->project_manager
        );
        if ($is_template) {
            $this->logger->info("The project will be a template");
            $data->setIsTemplate();
        }
        $project = $this->project_creator->build($data);

        $this->logger->info("Execute system events to finish creation of project {$project->getID()}, this can take a while...");
        $event_runner->runSystemEvents();
        $this->logger->info("System events success");

        return $project;
    }

    public function importFromArchive(ImportConfig $configuration, $project_id, ArchiveInterface $archive) {
        $this->logger->info('Start importing into existing project from archive ' . $archive->getExtractionPath());

        $xml_element = $this->getProjectXMLFromArchive($archive);

        $error                 = false;
        $params['xml_content'] = $xml_element;
        $params['error']       = &$error;
        $this->event_manager->processEvent(
            Event::IMPORT_XML_IS_PROJECT_VALID,
            $params
        );

        if ($params['error']) {
            throw new ImportNotValidException();
        }

        $this->importFromXMLIntoExistingProject($configuration, $project_id, $xml_element, $archive->getExtractionPath());
    }

    public function import(ImportConfig $configuration, $project_id, $xml_file_path) {
        $this->logger->info('Start importing from file ' . $xml_file_path);

        $xml_element = $this->getSimpleXMLElementFromFilePath($xml_file_path);

        $this->importFromXMLIntoExistingProject($configuration, $project_id, $xml_element, '');
    }

    private function importFromXMLIntoExistingProject(ImportConfig $configuration, $project_id, SimpleXMLElement $xml_element, $extraction_path) {
        $project = $this->project_manager->getValidProjectByShortNameOrId($project_id);
        $this->activateServices($project, $xml_element);

        $this->importContent($configuration, $project, $xml_element, $extraction_path);
    }

    private function activateServices(Project $project, SimpleXMLElement $xml_element) {
        if ($xml_element->services) {
            foreach ($xml_element->services->service as $service) {
                $short_name = (string) $service['shortname'];
                $enabled    = \Tuleap\XML\PHPCast::toBoolean($service['enabled']);
                $this->service_manager->toggleServiceUsage($project, $short_name, $enabled);
            }
        }
    }

    /**
     * @return string
     */
    public function collectBlockingErrorsWithoutImporting($project_id, $xml_file_path)
    {
        $this->logger->info('Start collecting errors from file ' . $xml_file_path);

        $xml_element = $this->getSimpleXMLElementFromFilePath($xml_file_path);
        $project = $this->project_manager->getValidProjectByShortNameOrId($project_id);

        return $this->collectBlockingErrorsWithoutImportingContent($project, $xml_element);
    }

    private function importContent(ImportConfig $configuration, Project $project, SimpleXMLElement $xml_element, $extraction_path) {
        $this->logger->info("Importing project in project ".$project->getUnixName());

        $user_creator = $this->user_manager->getCurrentUser();

        $this->importUgroups($project, $xml_element, $user_creator);

        $frs = new FRSXMLImporter($this->logger,
            $this->xml_validator,
            new FRSPackageFactory(),
            new FRSReleaseFactory(),
            new FRSFileFactory($this->logger),
            $this->user_finder,
            $this->ugroup_manager,
            new XMLImportHelper($this->user_manager),
            $this->frs_permissions_creator
        );

        $frs_release_mapping = array();
        $frs->import($configuration, $project, $xml_element, $extraction_path, $frs_release_mapping);

        $mappings_registery = new MappingsRegistry();
        $mappings_registery->add($frs_release_mapping, FRSXMLImporter::MAPPING_KEY);

        $this->logger->info("Ask to plugin to import data from XML");
        $this->event_manager->processEvent(
            Event::IMPORT_XML_PROJECT,
            array(
                'logger'              => $this->logger,
                'project'             => $project,
                'xml_content'         => $xml_element,
                'extraction_path'     => $extraction_path,
                'user_finder'         => $this->user_finder,
                'mappings_registery'  => $mappings_registery,
                'configuration'       => $configuration,
            )
        );

        $this->logger->info("Finish importing project in project ".$project->getUnixName() . " id " . $project->getID());
    }

    /**
     * @return string
     */
    private function collectBlockingErrorsWithoutImportingContent(Project $project, SimpleXMLElement $xml_element)
    {
        $errors = '';

        $this->logger->info("Ask plugins to check if errors might be raised from importing the XML");
        $this->event_manager->processEvent(
            Event::COLLECT_ERRORS_WITHOUT_IMPORTING_XML_PROJECT,
            array(
                'logger'      => $this->logger,
                'project'     => $project,
                'xml_content' => $xml_element,
                'user_finder' => $this->user_finder,
                'errors'      => &$errors
            )
        );
        return $errors;
    }

    private function importUgroups(Project $project, SimpleXMLElement $xml_element, PFUser $user_creator) {
        $this->logger->info("Check if there are ugroups to add");

        if ($xml_element->ugroups) {
            $this->logger->info("Some ugroups are defined in the XML");

            list($ugroups_in_xml, $project_members) = $this->getUgroupsFromXMLToAdd($project, $xml_element->ugroups);

            foreach($project_members as $user) {
                $this->addProjectMember($project, $user);
            }

            foreach ($ugroups_in_xml as $ugroup_def) {
                $ugroup = $this->ugroup_manager->getDynamicUGoupByName($project, $ugroup_def['name']);

                if(empty($ugroup)) {
                    $this->logger->debug("Creating empty ugroup " . $ugroup_def['name']);
                    $new_ugroup_id = $this->ugroup_manager->createEmptyUgroup(
                        $project->getID(),
                        $ugroup_def['name'],
                        $ugroup_def['description']
                    );
                    $ugroup = $this->ugroup_manager->getById($new_ugroup_id);
                }

                if (empty($ugroup_def['users'])) {
                    $this->logger->debug("No user to add in ugroup " . $ugroup_def['name']);
                } else {
                    $this->logger->debug("Adding users to ugroup " . $ugroup_def['name']);
                }

                foreach ($ugroup_def['users'] as $user) {
                    $this->logger->debug("Adding user " . $user->getUserName() . " to " . $ugroup_def['name']);
                    $ugroup->addUser($user);
                }

                if ($ugroup->getId() === ProjectUGroup::PROJECT_ADMIN) {
                    $this->cleanProjectAdminsFromUserCreator($ugroup, $ugroup_def['users'], $user_creator);
                }
            }

            $this->cleanProjectMembersFromUserCreator($project, $project_members, $user_creator);
            $this->logger->debug("Import of ugroups completed");
        }
    }

    private function addProjectMember(Project $project, PFUser $user)
    {
        $this->logger->info("Add user {$user->getUserName()} to project.");

        $check_user_status  = false;
        $send_notifications = false;
        if (! account_add_user_obj_to_group($project->getID(), $user, $check_user_status, $send_notifications)) {
            $this->logger->info("User is already member");
        }
    }

    private function cleanProjectMembersFromUserCreator(Project $project, array $users, PFUser $user_creator)
    {
        if (! empty($users) && ! in_array($user_creator, $users)) {
            $this->user_removal->removeUserFromProject($project->getID(), $user_creator->getId());
        }
    }

    private function cleanProjectAdminsFromUserCreator(ProjectUGroup $ugroup, array $users, PFUser $user_creator)
    {
        if (! empty($users) && ! in_array($user_creator, $users)) {
            $ugroup->removeUser($user_creator);
        }
    }

    /**
     * @param SimpleXMLElement $xml_element_ugroups
     *
     * @return array
     */
    private function getUgroupsFromXMLToAdd(Project $project, SimpleXMLElement $xml_element_ugroups) {
        $ugroups = array();
        $project_members = array();

        $rng_path = realpath(dirname(__FILE__).'/../xml/resources/ugroups.rng');
        $this->xml_validator->validate($xml_element_ugroups, $rng_path);
        $this->logger->debug("XML Ugroups is valid");

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

        return array($ugroups, $project_members);
    }

    /**
     * @param SimpleXMLElement $ugroup
     *
     * @return PFUser[]
     */
    private function getListOfUgroupMember(SimpleXMLElement $ugroup) {
        $ugroup_members = array();

        foreach ($ugroup->members->member as $xml_member) {
            $ugroup_members[] = $this->user_finder->getUser($xml_member);
        }

        return $ugroup_members;
    }

    private function getProjectXMLFromArchive(ArchiveInterface $archive) {
        $xml_contents = $archive->getProjectXML();

        if (! $xml_contents) {
            throw new RuntimeException('No content available in archive for file ' . ArchiveInterface::PROJECT_FILE);
        }

        return $this->getSimpleXMLElementFromString($xml_contents);
    }

    /**
     * @return SimpleXMLElement
     */
    private function getSimpleXMLElementFromFilePath($file_path)
    {
        $xml_contents = file_get_contents($file_path, 'r');
        return $this->getSimpleXMLElementFromString($xml_contents);
    }

    private function getSimpleXMLElementFromString($file_contents) {
        $this->checkFileIsValidXML($file_contents);

        return simplexml_load_string($file_contents, 'SimpleXMLElement', $this->getLibXMLOptions());
    }

    private function checkFileIsValidXML($file_contents) {
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        $xml = new DOMDocument();
        $xml->loadXML($file_contents, $this->getLibXMLOptions());
        $errors = libxml_get_errors();

        if (! empty($errors)){
            throw new RuntimeException($GLOBALS['Language']->getText('project_import', 'invalid_xml'));
        }
    }

    private function getLibXMLOptions() {
        if ($this->isAllowedToLoadHugeFiles()) {
            return LIBXML_PARSEHUGE;
        }

        return 0;
    }

    private function isAllowedToLoadHugeFiles() {
        return defined('IS_SCRIPT') && IS_SCRIPT;
    }
}
