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

    /** @var UserManager */
    private $user_manager;

    /** @var XMLImportHelper */
    private $xml_helper;

    /** @var Logger */
    private $logger;

    public function __construct(
        EventManager $event_manager,
        ProjectManager $project_manager,
        XML_RNGValidator $xml_validator,
        UGroupManager $ugroup_manager,
        UserManager $user_manager,
        XMLImportHelper $xml_helper,
        Logger $logger
    ) {
        $this->event_manager   = $event_manager;
        $this->project_manager = $project_manager;
        $this->xml_validator   = $xml_validator;
        $this->ugroup_manager  = $ugroup_manager;
        $this->user_manager    = $user_manager;
        $this->xml_helper      = $xml_helper;
        $this->logger          = $logger;
    }

    public function importFromArchive($project_id, ZipArchive $archive) {
        $this->logger->info('Start importing from archive ' . $archive->filename);

        $project         = $this->getProject($project_id);
        $project_archive = $this->getProjectZipArchive($project, $archive);

        $xml_content = $project_archive->getXML();

        if (! $xml_content) {
            $this->logger->error('No content available in archive for file ' . self::PROJECT_XML_FILENAME);
            return;
        }

        $project_archive->extractFiles();

        $this->importContent($project, $xml_content, $project_archive->getExtractionPath());

        return $project_archive->cleanUp();
    }

    /**
     * @return ProjectXMLImporter_XMLImportZipArchive
     */
    private function getProjectZipArchive(Project $project, ZipArchive $archive) {
        return new ProjectXMLImporter_XMLImportZipArchive($project, $archive, ForgeConfig::get('tmp_dir'));
    }

    public function import($project_id, $xml_file_path) {
        $this->logger->info('Start importing from file ' . $xml_file_path);

        $xml_contents = file_get_contents($xml_file_path, 'r');
        $project      = $this->getProject($project_id);

        return $this->importContent($project, $xml_contents, '');
    }

    private function importContent(Project $project, $xml_contents, $extraction_path) {
        $project_id = $project->getID();

        $this->logger->info("Importing project in project $project_id");

        $this->checkFileIsValidXML($xml_contents);

        $xml_element = simplexml_load_string($xml_contents);

        $this->importUgroups($project, $xml_element);

        $this->logger->info("Ask to plugin to import data from XML");
        $this->event_manager->processEvent(
            Event::IMPORT_XML_PROJECT,
            array(
                'project'         => $project,
                'xml_content'     => $xml_element,
                'extraction_path' => $extraction_path
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
            $identifier = $this->xml_helper->getUserFormat($xml_member);
            $user       = $this->user_manager->getUserByIdentifier($identifier);

            if (! $user) {
                $this->logger->debug("User ($identifier) does not exist -> skipped");
                continue;
            }

            $ugroup_members[] = $user;
        }

        return $ugroup_members;
    }

    private function checkFileIsValidXML($file_contents) {
        libxml_use_internal_errors(true);
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