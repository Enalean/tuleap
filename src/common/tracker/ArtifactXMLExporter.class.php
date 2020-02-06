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

class ArtifactXMLExporter
{

    public const ARCHIVE_DATA_DIR = 'data';

    /** @var ArtifactXMLExporterDao */
    private $dao;

    /** @var ArtifactAttachmentXMLExporter */
    private $attachment_exporter;

    /** @var ArtifactXMLNodeHelper */
    private $node_helper;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    public function __construct(ArtifactXMLExporterDao $dao, ArtifactAttachmentXMLExporter $attachment_exporter, ArtifactXMLNodeHelper $node_helper, \Psr\Log\LoggerInterface $logger)
    {
        $this->dao                  = $dao;
        $this->node_helper          = $node_helper;
        $this->logger               = $logger;
        $this->attachment_exporter  = $attachment_exporter;
    }

    public function exportTrackerData($tracker_id)
    {
        $artifacts_node = $this->node_helper->createElement('artifacts');
        foreach ($this->dao->searchArtifacts($tracker_id) as $row) {
            $artifact_exporter = new ArtifactXMLExporterArtifact($this->dao, $this->attachment_exporter, $this->node_helper, $this->logger);
            $artifact_node = $artifact_exporter->exportArtifact($tracker_id, $row);
            $artifacts_node->appendChild($artifact_node);
        }
        $this->node_helper->appendChild($artifacts_node);
    }
}
