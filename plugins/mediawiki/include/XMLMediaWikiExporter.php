<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Mediawiki;

use DirectoryIterator;
use ForgeConfig;
use MediawikiLanguageManager;
use MediawikiManager;
use Project;
use ProjectUGroup;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Event\Events\ExportXmlProject;
use Tuleap\Mediawiki\XML\CheckXMLMediawikiExportability;
use Tuleap\NeverThrow\Fault;
use Tuleap\Project\XML\Export\ArchiveInterface;
use UGroupManager;
use XML_SimpleXMLCDATAFactory;

final class XMLMediaWikiExporter
{
    private const EXPORT_FILE_PREFIX = "export_mw_";

    public function __construct(
        private readonly MediawikiManager $manager,
        private readonly UGroupManager $ugroup_manager,
        private readonly LoggerInterface $logger,
        private readonly MediawikiMaintenanceWrapper $maintenance_wrapper,
        private readonly MediawikiLanguageManager $language_manager,
        private readonly MediawikiDataDir $mediawiki_data_dir,
        private readonly CheckXMLMediawikiExportability $check_xml_mediawiki_exportability,
    ) {
    }

    public function exportToXml(ExportXmlProject $event): void
    {
        $this->check_xml_mediawiki_exportability->checkMediawikiCanBeExportedToXML($event, $this->mediawiki_data_dir)->match(
            function () use ($event) {
                $this->processExport($event);
            },
            function (Fault $fault) {
                $this->logger->info($fault);
            },
        );
    }

    private function processExport(ExportXmlProject $event): void
    {
        $project                           = $event->getProject();
        $archive                           = $event->getArchive();
        $temporary_dump_path_on_filesystem = $event->getTemporaryDumpPathOnFilesystem();
        $project_name_dir                  = $this->mediawiki_data_dir->getMediawikiDir($project);
        $export_file                       = self::EXPORT_FILE_PREFIX . $project->getID() . time() . '.xml';

        $this->logger->info('Export mediawiki');
        $root_node = $event->getIntoXml()->addChild('mediawiki');
        if ($root_node === null) {
            $this->logger->debug("Failed to create a root node for mediawiki XML");
            return;
        }

        $root_node->addAttribute('pages-backup', 'wiki_pages.xml');
        $root_node->addAttribute('language', $this->language_manager->getUsedLanguageForProject($project) ?? '');
        $root_node->addAttribute('files-folder-backup', 'files');

        $this->logger->info('Export mediawiki permissions');
        $this->exportMediawikiPermissions($root_node, $project);

        $export_file = $this->getBaseDir() . $export_file;

        $this->maintenance_wrapper->dumpBackupFull($project, $export_file);
        $this->maintenance_wrapper->dumpUploads(
            $project,
            $archive,
            $temporary_dump_path_on_filesystem,
            $project_name_dir
        );

        $this->addFilesIntoArchive($archive, $export_file);
        $this->addPicturesIntoArchive($archive, $temporary_dump_path_on_filesystem);
    }

    private function getBaseDir(): string
    {
        return rtrim(ForgeConfig::get('codendi_cache_dir'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    private function addFilesIntoArchive(ArchiveInterface $archive, $export_file): void
    {
        $archive->addFile(
            'wiki_pages.xml',
            $export_file
        );
    }

    private function addPicturesIntoArchive(ArchiveInterface $archive, $temporary_dump_path_on_filesystem): void
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

    private function exportMediawikiPermissions(SimpleXMLElement $xml_content, Project $project): void
    {
        $cdata   = new XML_SimpleXMLCDATAFactory();
        $readers = $this->manager->getReadAccessControl($project);
        if ($readers) {
            $reader_node = $xml_content->addChild('read-access');
            foreach ($readers as $reader) {
                $ugroup = $this->ugroup_manager->getUGroup($project, $reader);
                if ($ugroup) {
                    $cdata->insert($reader_node, 'ugroup', $this->getLabelForUgroup($ugroup));
                }
            }
        }

        $writers = $this->manager->getWriteAccessControl($project);
        if ($writers) {
            $writer_node = $xml_content->addChild('write-access');
            foreach ($writers as $writer) {
                $ugroup = $this->ugroup_manager->getUGroup($project, $writer);
                if ($ugroup) {
                    $cdata->insert($writer_node, 'ugroup', $this->getLabelForUgroup($ugroup));
                }
            }
        }
    }

    private function getLabelForUgroup(ProjectUGroup $ugroup): string
    {
        if ($ugroup->getId() === ProjectUGroup::PROJECT_MEMBERS) {
            return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_project_members_name_key');
        }

        if ($ugroup->getId() === ProjectUGroup::PROJECT_ADMIN) {
            return $GLOBALS['Language']->getText('project_ugroup', 'ugroup_project_admins_name_key');
        }

        return $ugroup->getName();
    }
}
