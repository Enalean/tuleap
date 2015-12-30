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

use Tuleap\Project\XML\Export\ArchiveInterface;

/**
 * This class export a project to xml format
 */
class ProjectXMLExporter {

    /** @var EventManager */
    private $event_manager;

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var XML_RNGValidator */
    private $xml_validator;

    /** @var UserXMLExporter */
    private $user_xml_exporter;

    /** @var Logger */
    private $logger;

    public function __construct(
        EventManager $event_manager,
        UGroupManager $ugroup_manager,
        XML_RNGValidator $xml_validator,
        UserXMLExporter $user_xml_exporter,
        Logger $logger
    ) {
        $this->event_manager     = $event_manager;
        $this->ugroup_manager    = $ugroup_manager;
        $this->xml_validator     = $xml_validator;
        $this->user_xml_exporter = $user_xml_exporter;
        $this->logger            = $logger;
    }

    private function exportProjectInfo(Project $project, SimpleXMLElement $project_node) {
        $access_value = $project->isPublic() ? Project::ACCESS_PUBLIC : Project::ACCESS_PRIVATE;

        $project_node->addAttribute('unix-name', $project->getUnixName());
        $project_node->addAttribute('full-name', $project->getPublicName());
        $project_node->addAttribute('description', $project->getDescription());
        $project_node->addAttribute('access', $access_value);

        $project_node->addChild('long-description', '');

        $services_node = $project_node->addChild('services');
        foreach ($project->getServices() as $service) {
            $service_node = $services_node->addChild('service');
            $service_node->addAttribute('shortname', $service->getShortName());
            $service_node->addAttribute('enabled', $service->isUsed());
        }
    }

    private function exportProjectUgroups(Project $project, SimpleXMLElement $into_xml) {
        $this->logger->debug("Exporting project's static ugroups");

        $ugroups = $this->ugroup_manager->getStaticUGroups($project);

        if (empty($ugroups)) {
            return;
        }

        $ugroups_node = $into_xml->addChild('ugroups');

        foreach ($ugroups as $ugroup) {
            $this->logger->debug("Current static ugroups: " . $ugroup->getName());

            $ugroup_node = $ugroups_node->addChild('ugroup');
            $ugroup_node->addAttribute('name', $ugroup->getNormalizedName());
            $ugroup_node->addAttribute('description', $ugroup->getDescription());

            $members_node = $ugroup_node->addChild('members');

            foreach ($ugroup->getMembers() as $member) {
                $this->user_xml_exporter->exportUser($member, $members_node, 'member');
            }
        }

        $rng_path = realpath(dirname(__FILE__).'/../xml/resources/ugroups.rng');
        $this->xml_validator->validate($ugroups_node, $rng_path);
    }

    private function exportPlugins(
        Project $project,
        SimpleXMLElement $into_xml,
        array $options,
        PFUser $user,
        ArchiveInterface $archive
    ) {
        $this->logger->info("Export plugins");

        $params = array(
            'project'           => $project,
            'options'           => $options,
            'into_xml'          => $into_xml,
            'user'              => $user,
            'user_xml_exporter' => $this->user_xml_exporter,
            'archive'           => $archive,
        );

        $this->event_manager->processEvent(
            Event::EXPORT_XML_PROJECT,
            $params
        );
    }

    public function export(Project $project, array $options, PFUser $user, ArchiveInterface $archive) {
        $this->logger->info("Start exporting project " . $project->getPublicName());

        $xml_element = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                             <project />');

        $this->exportProjectInfo($project, $xml_element);
        $this->exportProjectUgroups($project, $xml_element);
        $this->exportPlugins($project, $xml_element, $options, $user, $archive);

        $this->logger->info("Finish exporting project " . $project->getPublicName());

        return $this->convertToXml($xml_element);
    }

    /**
     * @param SimpleXMLElement $xml_element
     *
     * @return String
     */
    private function convertToXml(SimpleXMLElement $xml_element) {
        $dom = dom_import_simplexml($xml_element)->ownerDocument;
        $dom->formatOutput = true;

        return $dom->saveXML();
    }
}