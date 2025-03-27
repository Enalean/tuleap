<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
use Tracker_ArtifactFactory;
use Tracker_XML_ChildrenCollector;
use Tracker_XML_Updater_TemporaryFileXMLUpdater;

readonly class ChildrenXMLExporter
{
    public function __construct(
        private ArtifactXMLExporter $artifact_xml_updater,
        private Tracker_XML_Updater_TemporaryFileXMLUpdater $file_xml_updater,
        private Tracker_ArtifactFactory $artifact_factory,
        private Tracker_XML_ChildrenCollector $children_collector,
    ) {
    }

    public function exportChildren(SimpleXMLElement $xml): void
    {
        while ($artifact_id = $this->children_collector->pop()) {
            $artifact = $this->artifact_factory->getArtifactById($artifact_id);
            if (! $artifact) {
                continue;
            }

            $last_changeset = $artifact->getLastChangeset();
            $this->artifact_xml_updater->exportSnapshotWithoutComments($xml, $last_changeset);

            $index_last_artifact = count($xml->artifact) - 1;
            $this->file_xml_updater->update($xml->artifact[$index_last_artifact]);
        }
    }
}
