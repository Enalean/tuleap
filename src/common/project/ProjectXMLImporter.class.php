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

/**
 * This class import a project from a xml content
 */
class ProjectXMLImporter {

    /** @var EventManager */
    private $event_manager;

    /** @var $project_manager */
    private $project_manager;

    /** @var XML_RNGValidator */
    private $xml_validator;

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var User\XML\Import\IFindUserFromXMLReference */
    private $user_finder;

    /** @var Logger */
    private $logger;

    public function __construct(
        EventManager $event_manager,
        ProjectManager $project_manager,
        XML_RNGValidator $xml_validator,
        UGroupManager $ugroup_manager,
        User\XML\Import\IFindUserFromXMLReference $user_finder,
        Logger $logger
    ) {
        $this->event_manager   = $event_manager;
        $this->project_manager = $project_manager;
        $this->xml_validator   = $xml_validator;
        $this->ugroup_manager  = $ugroup_manager;
        $this->user_finder     = $user_finder;
        $this->logger          = $logger;

        $this->project_creator = new ProjectCreator($this->project_manager, ReferenceManager::instance(), true);
    }

    public function importNewFromArchive(ZipArchive $archive) {
        return $this->importFromArchive(basename($archive->filename), $archive);
    }

    public function importFromArchive($project_id, ZipArchive $archive, $project_name_override = null) {
        $this->logger->info('Start importing from archive ' . $archive->filename);

        $project_archive = $this->getProjectZipArchive($archive, $project_id);
        $xml_content = $project_archive->getXML();

        if (! $xml_content) {
            $this->logger->error('No content available in archive for file ' . ProjectXMLImporter_XMLImportZipArchive::PROJECT_XML_FILENAME);
            return;
        }

        $project_archive->extractFiles();

        $this->importContent($project_id, $xml_content, $project_archive->getExtractionPath(), $project_name_override);

        return $project_archive->cleanUp();
    }

    /**
     * @return ProjectXMLImporter_XMLImportZipArchive
     */
    private function getProjectZipArchive(ZipArchive $archive, $project_identifier) {
        return new ProjectXMLImporter_XMLImportZipArchive($project_identifier, $archive, ForgeConfig::get('tmp_dir'));
    }

    public function import($project_id, $xml_file_path, $project_name_override = null) {
        $this->logger->info('Start importing from file ' . $xml_file_path);

        $xml_contents    = file_get_contents($xml_file_path, 'r');
        $extraction_path = '';

        return $this->importContent($project_id, $xml_contents, $extraction_path, $project_name_override);
    }

    private function createProject(SimpleXMLElement $xml) {
        $data = ProjectCreationData::buildFromXML($xml,
            100,
            $this->xml_validator,
            ServiceManager::instance(),
            $project_manager = $this->project_manager);
        return $this->project_creator->build($data);
    }

    private function importContent($project_id, $xml_contents, $extraction_path, $project_name_override) {
        $this->checkFileIsValidXML($xml_contents);

        $xml_element = simplexml_load_string($xml_contents);

        if(!empty($project_name_override)) {
            $xml_element['unix-name'] = $project_name_override;
        }

        if(empty($project_id)){
            $project = $this->createProject($xml_element);
            $project_id = $project->getID();
        } else {
            $project = $this->getProject($project_id);
        }

        $this->logger->info("Importing project in project $project_id");

        $this->importUgroups($project, $xml_element);

        $svn = new SVNXMLImporter($this->logger, $this->xml_validator);
        $svn->import($project, $xml_element, $extraction_path);

        $this->logger->info("Ask to plugin to import data from XML");
        $this->event_manager->processEvent(
            Event::IMPORT_XML_PROJECT,
            array(
                'logger'          => $this->logger,
                'project'         => $project,
                'xml_content'     => $xml_element,
                'extraction_path' => $extraction_path,
                'user_finder'     => $this->user_finder,
            )
        );

        $this->logger->info("Finish importing project in project $project_id");
    }

    private function importUgroups(Project $project, SimpleXMLElement $xml_element) {
        $this->logger->info("Check if there are ugroups to add");

        if ($xml_element->ugroups) {
            $this->logger->info("Some ugroups are defined in the XML");

            $ugroup_in_xml = $this->getUgroupsFromXMLToAdd($project, $xml_element->ugroups);

            foreach ($ugroup_in_xml as $ugroup) {
                $this->logger->debug("Creating empty ugroup " . $ugroup['name']);
                $new_ugroup_id = $this->ugroup_manager->createEmptyUgroup(
                    $project->getID(),
                    $ugroup['name'],
                    $ugroup['description']
                );

                if (empty($ugroup['users'])) {
                    $this->logger->debug("No user to add in ugroup " . $ugroup['name']);
                } else {
                    $this->logger->debug("Adding users to ugroup " . $ugroup['name']);
                }

                foreach ($ugroup['users'] as $user) {
                    $this->logger->debug("Adding user " . $user->getUserName());
                    $this->ugroup_manager->addUserToUgroup(
                        $project->getID(),
                        $new_ugroup_id,
                        $user->getId()
                    );
                }
            }
        }
    }

    /**
     * @param SimpleXMLElement $xml_element_ugroups
     *
     * @return array
     */
    private function getUgroupsFromXMLToAdd(Project $project, SimpleXMLElement $xml_element_ugroups) {
        $ugroups = array();

        $rng_path = realpath(dirname(__FILE__).'/../xml/resources/ugroups.rng');
        $this->xml_validator->validate($xml_element_ugroups, $rng_path);
        $this->logger->debug("XML Ugroups is valid");

        foreach ($xml_element_ugroups->ugroup as $ugroup) {
            $ugroup_name        = (string) $ugroup['name'];
            $ugroup_description = (string) $ugroup['description'];

            if ($this->ugroup_manager->getUGroupByName($project, $ugroup_name)) {
                $this->logger->debug("Ugroup $ugroup_name already exists in project -> skipped");
                continue;
            }

            $ugroups[$ugroup_name]['name']        = $ugroup_name;
            $ugroups[$ugroup_name]['description'] = $ugroup_description;
            $ugroups[$ugroup_name]['users']       = $this->getListOfUgroupMember($ugroup);
        }

        return $ugroups;
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

    private function checkFileIsValidXML($file_contents) {
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        $xml = new DOMDocument();
        $xml->loadXML($file_contents);
        $errors = libxml_get_errors();

        if (! empty($errors)){
            throw new RuntimeException($GLOBALS['Language']->getText('project_import', 'invalid_xml'));
        }
    }

    /**
     * @throws RuntimeException
     * @return Project
     */
    private function getProject($project_id) {
        $project = $this->project_manager->getProject($project_id);
        if (! $project || ($project && ($project->isError() || $project->isDeleted()))) {
            throw new RuntimeException('Invalid project_id '.$project_id);
        }
        return $project;
    }
}
