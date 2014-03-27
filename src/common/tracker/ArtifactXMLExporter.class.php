<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class ArtifactXMLExporter {

    const ARCHIVE_DATA_DIR = 'data';

    /** @var ArtifactXMLExporterDao */
    private $dao;

    /** @var ZipArchive */
    private $archive;

    /** @var DomDocument */
    private $document;

    /** @var Logger */
    private $logger;

    public function __construct(ArtifactXMLExporterDao $dao, ZipArchive $archive, DOMDocument $document, Logger $logger) {
        $this->dao      = $dao;
        $this->document = $document;
        $this->logger   = $logger;
        $this->archive  = $archive;
    }

    public function exportTrackerData($tracker_id) {
        $artifacts_node = $this->document->createElement('artifacts');
        foreach ($this->dao->searchArtifacts($tracker_id) as $row) {
            $artifact_exporter = new ArtifactXMLExporterArtifact($this->dao, $this->archive, $this->document, $this->logger);
            $artifact_node = $artifact_exporter->exportArtifact($tracker_id, $row);
            $artifacts_node->appendChild($artifact_node);
        }
        $this->document->appendChild($artifacts_node);
    }
}
