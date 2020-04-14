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
 *
 * phpcs:disable PSR1.Classes.ClassDeclaration
 */

use Tuleap\Event\Events\ExportXmlProject;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;
use Tuleap\Project\XML\Export\ArchiveInterface;

class ProjectXMLExporter
{
    public const UGROUPS_MODE_SYNCHRONIZED = 'synchronized';

    public const LOG_IDENTIFIER = 'project_xml_export_syslog';

    /** @var EventManager */
    private $event_manager;

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var XML_RNGValidator */
    private $xml_validator;

    /** @var UserXMLExporter */
    private $user_xml_exporter;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;
    /**
     * @var SynchronizedProjectMembershipDetector
     */
    private $synchronized_project_membership_detector;

    public function __construct(
        EventManager $event_manager,
        UGroupManager $ugroup_manager,
        XML_RNGValidator $xml_validator,
        UserXMLExporter $user_xml_exporter,
        SynchronizedProjectMembershipDetector $synchronized_project_membership_detector,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->event_manager                            = $event_manager;
        $this->ugroup_manager                           = $ugroup_manager;
        $this->xml_validator                            = $xml_validator;
        $this->user_xml_exporter                        = $user_xml_exporter;
        $this->synchronized_project_membership_detector = $synchronized_project_membership_detector;
        $this->logger                                   = $logger;
    }

    public static function getLogger(): \Psr\Log\LoggerInterface
    {
        return BackendLogger::getDefaultLogger('project_xml_export_syslog');
    }

    private function exportProjectInfo(Project $project, SimpleXMLElement $project_node)
    {
        $project_node->addAttribute('unix-name', $project->getUnixName());
        $project_node->addAttribute('full-name', $project->getPublicName());
        $project_node->addAttribute('description', $project->getDescription());
        $project_node->addAttribute('access', $project->getAccess());

        $project_node->addChild('long-description', '');

        $services_node = $project_node->addChild('services');
        foreach ($project->getServices() as $service) {
            $service_node = $services_node->addChild('service');
            $service_node->addAttribute('shortname', $service->getShortName());
            if ($service->isUsed()) {
                $service_node->addAttribute('enabled', '1');
            } else {
                $service_node->addAttribute('enabled', '0');
            }
        }
    }

    private function exportProjectUgroups(Project $project, SimpleXMLElement $into_xml)
    {
        $ugroups_node = $into_xml->addChild('ugroups');
        if ($this->synchronized_project_membership_detector->isSynchronizedWithProjectMembers($project)) {
            $ugroups_node->addAttribute('mode', self::UGROUPS_MODE_SYNCHRONIZED);
        }

        $this->logger->debug('Exporting project_admins ugroup');
        $project_admins_ugroup = $this->ugroup_manager->getProjectAdminsUGroup($project);
        $this->exportProjectUgroup($ugroups_node, $project_admins_ugroup);

        $this->logger->debug('Exporting project_admins ugroup');
        $project_members_ugroup = $this->ugroup_manager->getProjectMembersUGroup($project);
        $this->exportProjectUgroup($ugroups_node, $project_members_ugroup);

        $this->logger->debug("Exporting project's static ugroups");
        $static_ugroups = $this->ugroup_manager->getStaticUGroups($project);
        foreach ($static_ugroups as $ugroup) {
            $this->exportProjectUgroup($ugroups_node, $ugroup);
        }

        $rng_path = realpath(dirname(__FILE__) . '/../xml/resources/ugroups.rng');
        $this->xml_validator->validate($ugroups_node, $rng_path);
    }

    private function exportProjectUgroup(SimpleXMLElement $ugroups_node, ProjectUGroup $ugroup)
    {
        $this->logger->debug("Current ugroup: " . $ugroup->getName());

        $ugroup_node = $ugroups_node->addChild('ugroup');
        $ugroup_node->addAttribute('name', $ugroup->getNormalizedName());
        $ugroup_node->addAttribute('description', $ugroup->getTranslatedDescription());

        $members_node = $ugroup_node->addChild('members');

        foreach ($ugroup->getMembers() as $member) {
            $this->user_xml_exporter->exportUser($member, $members_node, 'member');
        }
    }

    private function exportPlugins(
        Project $project,
        SimpleXMLElement $into_xml,
        array $options,
        PFUser $user,
        ArchiveInterface $archive,
        $temporary_dump_path_on_filesystem
    ) {
        $this->logger->info("Export plugins");

        $event = new ExportXmlProject(
            $project,
            $options,
            $into_xml,
            $user,
            $this->user_xml_exporter,
            $archive,
            $temporary_dump_path_on_filesystem,
            $this->logger
        );

        $this->event_manager->processEvent($event);
    }

    public function export(Project $project, array $options, PFUser $user, ArchiveInterface $archive, $temporary_dump_path_on_filesystem)
    {
        $this->logger->info("Start exporting project " . $project->getPublicName());

        $xml_element = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                             <project />');

        $this->exportProjectInfo($project, $xml_element);
        $this->exportProjectUgroups($project, $xml_element);
        $this->exportPlugins($project, $xml_element, $options, $user, $archive, $temporary_dump_path_on_filesystem);

        $this->logger->info("Finish exporting project " . $project->getPublicName());

        return $this->convertToXml($xml_element);
    }

    /**
     *
     * @return String
     */
    private function convertToXml(SimpleXMLElement $xml_element)
    {
        $dom = dom_import_simplexml($xml_element)->ownerDocument;
        if ($dom === null) {
            return '';
        }
        $dom->formatOutput = true;

        return $dom->saveXML();
    }
}
