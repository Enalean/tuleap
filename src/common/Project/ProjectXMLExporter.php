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

namespace Tuleap\Project;

use BackendLogger;
use EventManager;
use PFUser;
use Project;
use ProjectUGroup;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Dashboard\Project\DashboardXMLExporter;
use Tuleap\Event\Events\ExportXmlProject;
use Tuleap\Project\Banner\BannerRetriever;
use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Project\Service\ProjectDefinedService;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;
use Tuleap\Project\XML\Export\ArchiveInterface;
use Tuleap\Project\XML\Export\ExportOptions;
use UGroupManager;
use UserXMLExporter;
use XML_RNGValidator;
use XML_SimpleXMLCDATAFactory;

final readonly class ProjectXMLExporter // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const string UGROUPS_MODE_SYNCHRONIZED = 'synchronized';

    public const string LOG_IDENTIFIER = 'project_xml_export_syslog';

    public function __construct(
        private EventManager $event_manager,
        private UGroupManager $ugroup_manager,
        private XML_RNGValidator $xml_validator,
        private UserXMLExporter $user_xml_exporter,
        private DashboardXMLExporter $dashboard_xml_exporter,
        private SynchronizedProjectMembershipDetector $synchronized_project_membership_detector,
        private LoggerInterface $logger,
        private BannerRetriever $banner_retriever,
    ) {
    }

    public static function getLogger(): LoggerInterface
    {
        return BackendLogger::getDefaultLogger('project_xml_export_syslog');
    }

    private function exportProjectInfo(Project $project, SimpleXMLElement $project_node): void
    {
        $project_node->addAttribute('unix-name', $project->getUnixName());
        $project_node->addAttribute('full-name', $project->getPublicName());
        $project_node->addAttribute('description', $project->getDescription());
        $project_node->addAttribute('access', $project->getAccess());
        if ($project->getIconUnicodeCodepoint()) {
            $project_node->addAttribute(
                'icon-codepoint',
                EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat($project->getIconUnicodeCodepoint())
            );
        }

        $project_node->addChild('long-description', '');

        $banner = $this->banner_retriever->getBannerForProject($project);
        if ($banner !== null) {
            $cdata_factory = new XML_SimpleXMLCDATAFactory();
            $cdata_factory->insert($project_node, 'banner', $banner->getMessage());
        }

        $services_node = $project_node->addChild('services');
        foreach ($project->getServices() as $service) {
            $project_defined_service = $service instanceof ProjectDefinedService;

            if ($project_defined_service) {
                $service_node = $services_node->addChild('project-defined-service');
                $service_node->addAttribute('label', $service->getLabel());
                $service_node->addAttribute('description', $service->getDescription());
                $service_node->addAttribute('link', $service->getUrl());
                $service_node->addAttribute('is_in_new_tab', $service->isOpenedInNewTab() ? '1' : '0');
            } else {
                $service_node = $services_node->addChild('service');
            }

            $service_node->addAttribute('shortname', $service->getShortName());
            if ($service->isUsed()) {
                $service_node->addAttribute('enabled', '1');
            } else {
                $service_node->addAttribute('enabled', '0');
            }
        }
    }

    private function exportProjectUgroups(Project $project, ExportOptions $options, SimpleXMLElement $into_xml): void
    {
        $ugroups_node = $into_xml->addChild('ugroups');
        if ($this->synchronized_project_membership_detector->isSynchronizedWithProjectMembers($project)) {
            $ugroups_node->addAttribute('mode', self::UGROUPS_MODE_SYNCHRONIZED);
        }

        $this->logger->debug('Exporting project_admins ugroup');
        $project_admins_ugroup = $this->ugroup_manager->getProjectAdminsUGroup($project);
        $this->exportProjectUgroup($ugroups_node, $options, $project_admins_ugroup);

        $this->logger->debug('Exporting project_admins ugroup');
        $project_members_ugroup = $this->ugroup_manager->getProjectMembersUGroup($project);
        $this->exportProjectUgroup($ugroups_node, $options, $project_members_ugroup);

        $this->logger->debug("Exporting project's static ugroups");
        $static_ugroups = $this->ugroup_manager->getStaticUGroups($project);
        foreach ($static_ugroups as $ugroup) {
            $this->exportProjectUgroup($ugroups_node, $options, $ugroup);
        }

        $rng_path = realpath(dirname(__FILE__) . '/../xml/resources/ugroups.rng');
        $this->xml_validator->validate($ugroups_node, $rng_path);
    }

    private function exportProjectUgroup(SimpleXMLElement $ugroups_node, ExportOptions $options, ProjectUGroup $ugroup): void
    {
        $this->logger->debug('Current ugroup: ' . $ugroup->getName());

        $ugroup_node = $ugroups_node->addChild('ugroup');
        $ugroup_node->addAttribute('name', $ugroup->getNormalizedName());
        $ugroup_node->addAttribute('description', $ugroup->getTranslatedDescription());

        $members_node = $ugroup_node->addChild('members');

        if ($options->shouldExportStructureOnly()) {
            return;
        }

        foreach ($ugroup->getMembers() as $member) {
            $this->user_xml_exporter->exportUser($member, $members_node, 'member');
        }
    }

    private function exportPlugins(
        Project $project,
        SimpleXMLElement $into_xml,
        ExportOptions $options,
        PFUser $user,
        ArchiveInterface $archive,
        $temporary_dump_path_on_filesystem,
    ): void {
        $this->logger->info('Export plugins');

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

    private function exportDashboards(Project $project, SimpleXMLElement $xml_element): void
    {
        $this->dashboard_xml_exporter->exportDashboards($project, $xml_element);
    }

    public function export(Project $project, ExportOptions $options, PFUser $user, ArchiveInterface $archive, $temporary_dump_path_on_filesystem)
    {
        if (! $project->isActive()) {
            throw new ProjectIsInactiveException();
        }
        $this->logger->info('Start exporting project ' . $project->getPublicName());

        $xml_element = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                             <project />');

        $this->exportProjectInfo($project, $xml_element);
        $this->exportProjectUgroups($project, $options, $xml_element);
        $this->exportDashboards($project, $xml_element);
        $this->exportPlugins($project, $xml_element, $options, $user, $archive, $temporary_dump_path_on_filesystem);

        $this->logger->info('Finish exporting project ' . $project->getPublicName());

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
