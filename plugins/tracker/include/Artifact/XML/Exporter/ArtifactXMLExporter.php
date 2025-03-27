<?php
/*
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\XML\Exporter;

use SimpleXMLElement;
use Tracker_Artifact_Changeset;
use Tuleap\Tracker\Artifact\Artifact;

readonly class ArtifactXMLExporter
{
    public function __construct(
        private ChangesetXMLExporter $changeset_exporter,
        private FileInfoXMLExporter $file_info_xml_exporter,
    ) {
    }

    /**
     * Same as exportFullHistory() but only the current state of the artifact
     */
    public function exportSnapshotWithoutComments(
        SimpleXMLElement $artifacts_xml,
        Tracker_Artifact_Changeset $changeset,
    ): SimpleXMLElement {
        $artifact_xml = $artifacts_xml->addChild('artifact');
        $artifact_xml->addAttribute('id', $changeset->getArtifact()->getId());
        $artifact_xml->addAttribute('tracker_id', $changeset->getArtifact()->getTrackerId());

        $this->changeset_exporter->exportWithoutComments($artifact_xml, $changeset);
        $this->file_info_xml_exporter->export($artifact_xml, $changeset->getArtifact());

        return $artifacts_xml;
    }

    /**
     * Add to $artifacts_xml the xml structure of an artifact
     */
    public function exportFullHistory(
        SimpleXMLElement $artifacts_xml,
        Artifact $artifact,
    ): void {
        $artifact_xml = $artifacts_xml->addChild('artifact');
        $artifact_xml->addAttribute('id', $artifact->getId());

        foreach ($artifact->getChangesets() as $changeset) {
            $this->changeset_exporter->exportFullHistory($artifact_xml, $changeset);
        }
        $this->file_info_xml_exporter->export($artifact_xml, $artifact);
    }
}
