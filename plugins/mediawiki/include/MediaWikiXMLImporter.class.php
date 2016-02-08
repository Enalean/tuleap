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
     * @var MediawikiDao
     */
    private $dao;

    public function __construct(Logger $logger, MediawikiDao $dao) {
        $this->logger = new WrapperLogger($logger, "MediaWikiXMLImporter");
        $this->dao = $dao;
        $this->sys_command = new System_Command();
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
        $pages_backup_path = $extraction_path . '/' . $xml_mediawiki['pages-backup'];
        return $this->importPages($project, $pages_backup_path);
    }

    private function importPages(Project $project, $backup_path) {
        $this->logger->info("Importing pages for {$project->getUnixName()}");
        $bin_path = dirname(__FILE__) . '/../bin';
        $project_name = escapeshellarg($project->getUnixName());
        $backup_path = escapeshellarg($backup_path);
        $command = "$bin_path/mw-maintenance-wrapper.php $project_name importDump.php $backup_path";
        $res = $this->sys_command->exec($command);
        return true;
    }
}
