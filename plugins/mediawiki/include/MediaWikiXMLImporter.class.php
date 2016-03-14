<?php
/**
 * Copyright (c) Sogilis, 2016. All Rights Reserved.
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

class MediaWikiXMLImporter {

    /**
     * @var Logger
     */
    private $logger;

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

    public function __construct(Logger $logger, MediawikiLanguageManager $language_manager) {
        $this->logger           = new WrapperLogger($logger, "MediaWikiXMLImporter");
        $this->language_manager = $language_manager;
        $this->sys_command      = new System_Command();
        $this->backend          = Backend::instance();
    }

    /**
     * Populate the MediaWiki with the given pages.
     * Returns true in case of success, false otherwise.
     * @var Project
     * @var SimpleXMLElement
     * @var String
     * @return boolean
     */
    public function import(Project $project, PFUser $creator, SimpleXMLElement $xml_input, $extraction_path) {
        $xml_mediawiki = $xml_input->mediawiki;
        if(!$xml_mediawiki) {
            $this->logger->debug('No mediawiki node found into xml.');
            return true;
        }

        if($xml_mediawiki['language']) {
            $this->importLanguage($project, (string) $xml_mediawiki['language']);
        }

        if(isset($xml_mediawiki['pages-backup'])) {
            $pages_backup_path = $extraction_path . '/' . $xml_mediawiki['pages-backup'];
            $this->importPages($project, $pages_backup_path);
        }

        if(isset($xml_mediawiki['files-folder-backup'])) {
            $files_backup_path = $extraction_path . '/' . $xml_mediawiki['files-folder-backup'];
            $this->importFiles($project, $files_backup_path);
        }

        $mediawiki_storage_path = forge_get_config('projects_path', 'mediawiki') . "/". $project->getID();
        $owner = ForgeConfig::get('sys_http_user');
        if($owner) {
            $this->backend->recurseChownChgrp($mediawiki_storage_path, $owner, $owner);
        } else {
            $this->logger->error("Could not get sys_http_user, permission problems may occur on $mediawiki_storage_path");
        }
    }

    private function importPages(Project $project, $backup_path) {
        $this->logger->info("Importing pages for {$project->getUnixName()}");
        $project_name = escapeshellarg($project->getUnixName());
        $backup_path = escapeshellarg($backup_path);
        $command = $this->getMaintenanceWrapperPath() . " $project_name importDump.php $backup_path";
        $this->sys_command->exec($command);
        return true;
    }

    private function importLanguage(Project $project, $language) {
        $this->logger->info("Set language to $language for {$project->getUnixName()}");
        try {
            $this->language_manager->saveLanguageOption($project, $language);
        } catch (Mediawiki_UnsupportedLanguageException $e) {
            $this->logger->warn("Could not set up the language for {$project->getUnixName()} mediawiki, $language is not sopported.");
        }
    }

    private function importFiles(Project $project, $backup_path) {
        $this->logger->info("Importing files for {$project->getUnixName()}");
        $project_name = escapeshellarg($project->getUnixName());
        $backup_path = escapeshellarg($backup_path);
        $command = $this->getMaintenanceWrapperPath() . " $project_name importImages.php --comment='Tuleap import' $backup_path";

        $this->sys_command->exec($command);
        return true;

    }

    private function getMaintenanceWrapperPath() {
        return __DIR__ . "/../bin/mw-maintenance-wrapper.php";
    }
}
