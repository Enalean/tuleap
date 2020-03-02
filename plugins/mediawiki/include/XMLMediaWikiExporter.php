<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\MediaWiki;

use DirectoryIterator;
use ForgeConfig;
use Psr\Log\LoggerInterface;
use MediawikiLanguageManager;
use MediawikiManager;
use Project;
use ProjectUGroup;
use SimpleXMLElement;
use Tuleap\Project\XML\Export\ArchiveInterface;
use UGroupManager;
use Tuleap\Mediawiki\MediawikiDataDir;
use XML_SimpleXMLCDATAFactory;

class XMLMediaWikiExporter
{

    /**
     * @var Project
     */
    private $project;

    /**
     * @var MediawikiManager
     */
    private $manager;

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var MediawikiMaintenanceWrapper
     */
    private $maintenance_wrapper;
    /**
     * @var MediawikiLanguageManager
     */
    private $language_manager;
    /**
     * @var MediawikiDataDir
     */
    private $mediawiki_data_dir;

    public function __construct(
        Project $project,
        MediawikiManager $manager,
        UGroupManager $ugroup_manager,
        LoggerInterface $logger,
        MediawikiMaintenanceWrapper $maintenance_wrapper,
        MediawikiLanguageManager $language_manager,
        MediawikiDataDir $mediawiki_data_dir
    ) {
        $this->project             = $project;
        $this->manager             = $manager;
        $this->ugroup_manager      = $ugroup_manager;
        $this->logger              = $logger;
        $this->maintenance_wrapper = $maintenance_wrapper;
        $this->language_manager    = $language_manager;
        $this->mediawiki_data_dir  = $mediawiki_data_dir;
    }

    public function exportToXml(
        SimpleXMLElement $xml_content,
        ArchiveInterface $archive,
        $export_file,
        $temporary_dump_path_on_filesystem
    ) {
        $project_name_dir = $this->mediawiki_data_dir->getMediawikiDir($this->project);

        if (! is_dir($project_name_dir)) {
            $this->logger->info('Mediawiki not instantiated, skipping');

            return;
        }

        $this->logger->info('Export mediawiki');
        $root_node = $xml_content->addChild('mediawiki');
        $root_node->addAttribute('pages-backup', 'wiki_pages.xml');
        $root_node->addAttribute('language', $this->language_manager->getUsedLanguageForProject($this->project));
        $root_node->addAttribute('files-folder-backup', 'files');

        $this->logger->info('Export mediawiki permissions');
        $this->exportMediawikiPermissions($root_node);

        $export_file = $this->getBaseDir() . $export_file;

        $this->maintenance_wrapper->dumpBackupFull($this->project, $export_file);
        $this->maintenance_wrapper->dumpUploads(
            $this->project,
            $archive,
            $temporary_dump_path_on_filesystem,
            $project_name_dir
        );

        $this->addFilesIntoArchive($archive, $export_file);
        $this->addPicturesIntoArchive($archive, $temporary_dump_path_on_filesystem);
    }

    private function getBaseDir()
    {
        return rtrim(ForgeConfig::get('codendi_cache_dir'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    private function addFilesIntoArchive(ArchiveInterface $archive, $export_file)
    {
        $archive->addFile(
            'wiki_pages.xml',
            $export_file
        );
    }

    private function addPicturesIntoArchive(ArchiveInterface $archive, $temporary_dump_path_on_filesystem)
    {
        $files_folder = $temporary_dump_path_on_filesystem . "/files";
        if (! is_dir($files_folder)) {
            return;
        }
        foreach (new DirectoryIterator($files_folder) as $picture) {
            if ($picture->isDot()) {
                continue;
            }

            if ($picture->isFile()) {
                $archive->addFile(
                    "files/" . $picture,
                    $files_folder . "/" . $picture
                );
            }
        }
    }

    private function exportMediawikiPermissions(SimpleXMLElement $xml_content)
    {
        $cdata = new XML_SimpleXMLCDATAFactory();
        $readers = $this->manager->getReadAccessControl($this->project);
        if ($readers) {
            $reader_node = $xml_content->addChild('read-access');
            foreach ($readers as $reader) {
                $ugroup = $this->ugroup_manager->getUGroup($this->project, $reader);
                if ($ugroup) {
                    $cdata->insert($reader_node, 'ugroup', $this->getLabelForUgroup($ugroup));
                }
            }
        }

        $writers = $this->manager->getWriteAccessControl($this->project);
        if ($writers) {
            $writer_node = $xml_content->addChild('write-access');
            foreach ($writers as $writer) {
                $ugroup = $this->ugroup_manager->getUGroup($this->project, $writer);
                if ($ugroup) {
                    $cdata->insert($writer_node, 'ugroup', $this->getLabelForUgroup($ugroup));
                }
            }
        }
    }

    private function getLabelForUgroup(ProjectUGroup $ugroup)
    {
        if ((int) $ugroup->getId() === ProjectUGroup::PROJECT_MEMBERS) {
            return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_project_members_name_key');
        }

        if ((int) $ugroup->getId() === ProjectUGroup::PROJECT_ADMIN) {
            return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_project_admins_name_key');
        }

        return $ugroup->getName();
    }
}
