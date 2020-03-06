<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\XML\Exporter;

use SimpleXMLElement;
use Tracker_Artifact;
use Tracker_FileInfo;
use Tracker_XML_Exporter_FilePathXMLExporter;
use Tuleap\Tracker\FormElement\Field\File\IdForXMLImportExportConvertor;
use XML_SimpleXMLCDATAFactory;

class FileInfoXMLExporter
{
    /**
     * @var Tracker_XML_Exporter_FilePathXMLExporter
     */
    private $path_exporter;

    /**
     * @var array<int, array<int, Tracker_FileInfo>>
     */
    private $file_infos_by_artifact_id = [];

    public function __construct(Tracker_XML_Exporter_FilePathXMLExporter $path_exporter)
    {
        $this->path_exporter = $path_exporter;
    }

    public function export(SimpleXMLElement $artifact_xml, Tracker_Artifact $artifact): void
    {
        if (! isset($this->file_infos_by_artifact_id[(int) $artifact->getId()])) {
            return;
        }

        foreach ($this->file_infos_by_artifact_id[(int) $artifact->getId()] as $file) {
            $this->appendFileToArtifactNode($artifact_xml, $file);
        }
    }

    public function add(Tracker_Artifact $artifact, Tracker_FileInfo $file): void
    {
        if (! isset($this->file_infos_by_artifact_id[(int) $artifact->getId()])) {
            $this->file_infos_by_artifact_id[(int) $artifact->getId()] = [];
        }

        $this->file_infos_by_artifact_id[(int) $artifact->getId()][(int) $file->getId()] = $file;
    }

    private function appendFileToArtifactNode(
        SimpleXMLElement $artifact_xml,
        Tracker_FileInfo $file_info
    ) {
        $cdata_factory = new XML_SimpleXMLCDATAFactory();

        $node = $artifact_xml->addChild('file');
        $node->addAttribute('id', $this->getFileInfoIdForXML($file_info));
        $cdata_factory->insert($node, 'filename', $file_info->getFilename());
        $cdata_factory->insert($node, 'path', $this->path_exporter->getPath($file_info));
        $cdata_factory->insert($node, 'filesize', $file_info->getFilesize());
        $cdata_factory->insert($node, 'filetype', $file_info->getFiletype());
        $cdata_factory->insert($node, 'description', $file_info->getDescription());
    }

    private function getFileInfoIdForXML(Tracker_FileInfo $file_info)
    {
        return IdForXMLImportExportConvertor::convertFileInfoIdToXMLId((int) $file_info->getId());
    }
}
