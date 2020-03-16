<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Tuleap\Project\XML\Import\ImportConfig;

class MediaWikiXMLImporter
{
    public const SERVICE_NAME = 'mediawiki';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var MediawikiManager
     */
    private $mediawiki_manager;

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    /**
     * @var MediawikiLanguageManager
     */
    private $language_manager;

    /**
     * @var System_Command
     */
    private $sys_command;

    /**
     * @var Backend
     */
    private $backend;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        MediawikiManager $mediawiki_manager,
        MediawikiLanguageManager $language_manager,
        UGroupManager $ugroup_manager,
        EventManager $event_manager
    ) {
        $this->logger            = new WrapperLogger($logger, "MediaWikiXMLImporter");
        $this->mediawiki_manager = $mediawiki_manager;
        $this->language_manager  = $language_manager;
        $this->ugroup_manager    = $ugroup_manager;
        $this->sys_command       = new System_Command();
        $this->backend           = Backend::instance();
        $this->event_manager     = $event_manager;
    }

    /**
     * Populate the MediaWiki with the given pages.
     * Returns true in case of success, false otherwise.
     *
     * @var ImportConfig
     * @var Project
     * @var SimpleXMLElement
     * @var String
     * @return bool
     */
    public function import(ImportConfig $configuration, Project $project, PFUser $creator, SimpleXMLElement $xml_input, $extraction_path)
    {
        $xml_mediawiki = $xml_input->mediawiki;
        if (!$xml_mediawiki) {
            $this->logger->debug('No mediawiki node found into xml.');
            return true;
        }

        if ($xml_mediawiki['language']) {
            $this->importLanguage($project, (string) $xml_mediawiki['language']);
        }

        if (isset($xml_mediawiki['pages-backup'])) {
            $pages_backup_path = $extraction_path . '/' . $xml_mediawiki['pages-backup'];
            $this->importPages($project, $pages_backup_path);
        }

        if (isset($xml_mediawiki['files-folder-backup'])) {
            $files_backup_path = $extraction_path . '/' . $xml_mediawiki['files-folder-backup'];
            $this->importFiles($project, $files_backup_path);
        }

        $this->importRights($project, $xml_mediawiki);

        $mediawiki_storage_path = forge_get_config('projects_path', 'mediawiki') . "/" . $project->getID();
        $owner = ForgeConfig::get('sys_http_user');
        if ($owner) {
            $no_filter_file_extension = array();
            $this->backend->recurseChownChgrp($mediawiki_storage_path, $owner, $owner, $no_filter_file_extension);
        } else {
            $this->logger->error("Could not get sys_http_user, permission problems may occur on $mediawiki_storage_path");
        }

        $this->importReferences($configuration, $project, $xml_mediawiki->references);
    }

    private function importPages(Project $project, $backup_path)
    {
        $this->logger->info("Importing pages for {$project->getUnixName()}");
        $project_name = escapeshellarg($project->getUnixName());
        $backup_path = escapeshellarg($backup_path);
        $command = $this->getMaintenanceWrapperPath() . " $project_name importDump.php $backup_path";
        $this->sys_command->exec($command);
        return true;
    }

    private function importLanguage(Project $project, $language)
    {
        $this->logger->info("Set language to $language for {$project->getUnixName()}");
        try {
            $this->language_manager->saveLanguageOption($project, $language);
        } catch (Mediawiki_UnsupportedLanguageException $e) {
            $this->logger->warning("Could not set up the language for {$project->getUnixName()} mediawiki, $language is not sopported.");
        }
    }

    private function importFiles(Project $project, $backup_path)
    {
        $this->logger->info("Importing files for {$project->getUnixName()}");
        $project_name = escapeshellarg($project->getUnixName());
        $backup_path = escapeshellarg($backup_path);
        $command = $this->getMaintenanceWrapperPath() . " $project_name importImages.php --comment='Tuleap import' $backup_path";

        $this->sys_command->exec($command);
        return true;
    }

    private function importRights(Project $project, SimpleXMLElement $xml_mediawiki)
    {
        if ($xml_mediawiki->{'read-access'}) {
            $this->logger->info("Importing read access rights for {$project->getUnixName()}");
            $ugroups_ids = $this->getUgroupIdsForPermissions($project, $xml_mediawiki->{'read-access'});
            if (count($ugroups_ids) > 0) {
                $this->mediawiki_manager->saveReadAccessControl($project, $ugroups_ids);
            }
        }
        if ($xml_mediawiki->{'write-access'}) {
            $this->logger->info("Importing write access rights for {$project->getUnixName()}");
            $ugroups_ids = $this->getUgroupIdsForPermissions($project, $xml_mediawiki->{'write-access'});
            if (count($ugroups_ids) > 0) {
                $this->mediawiki_manager->saveWriteAccessControl($project, $ugroups_ids);
            }
        }
    }

    private function getMaintenanceWrapperPath()
    {
        return __DIR__ . "/../bin/mw-maintenance-wrapper.php";
    }

    private function getUgroupIdsForPermissions(Project $project, SimpleXMLElement $permission_xmlnode)
    {
        $ugroup_ids = array();
        foreach ($permission_xmlnode->ugroup as $ugroup) {
            $ugroup_name = (string) $ugroup;
            $ugroup = $this->ugroup_manager->getUGroupByName($project, $ugroup_name);
            if ($ugroup === null) {
                $this->logger->warning("Could not find any ugroup named $ugroup_name, skip it.");
                continue;
            }
            array_push($ugroup_ids, $ugroup->getId());
        }
        return $ugroup_ids;
    }

    private function importReferences(ImportConfig $configuration, Project $project, SimpleXMLElement $xml_references)
    {
        $this->event_manager->processEvent(
            Event::IMPORT_COMPAT_REF_XML,
            array(
                'logger'         => $this->logger,
                'created_refs'   => array(),
                'service_name'   => self::SERVICE_NAME,
                'xml_content'    => $xml_references,
                'project'        => $project,
                'configuration'  => $configuration,
            )
        );
    }
}
