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

class Tracker_XML_Exporter_ArtifactXMLExporter {

    /**
     * @var Tracker_XML_Exporter_ChangesetXMLExporter
     */
    private $changeset_exporter;

    public function __construct(Tracker_XML_Exporter_ChangesetXMLExporter $changeset_exporter) {
        $this->changeset_exporter = $changeset_exporter;
    }

    public function exportSnapshotWithoutComments(
        SimpleXMLElement $artifacts_xml,
        Tracker_Artifact_Changeset $changeset
    ) {
        $artifact_xml = $artifacts_xml->addChild('artifact');
        $artifact_xml->addAttribute('id', $changeset->getArtifact()->getId());
        $artifact_xml->addAttribute('tracker_id', $changeset->getArtifact()->getTrackerId());

        $this->changeset_exporter->exportWithoutComments($artifact_xml, $changeset);
    }
}
