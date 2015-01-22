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

class Tracker_XML_Exporter_ChangesetXMLExporter {

    /**
     * @var Tracker_XML_Exporter_ChangesetValuesXMLExporter
     */
    private $values_exporter;

    public function __construct(Tracker_XML_Exporter_ChangesetValuesXMLExporter $values_exporter) {
        $this->values_exporter = $values_exporter;
    }

    public function exportWithoutComments(
        SimpleXMLElement $artifact_xml,
        Tracker_Artifact_Changeset $changeset
    ) {
        $changeset_xml = $artifact_xml->addChild('changeset');

        $submitted_by = $changeset_xml->addChild('submitted_by', $changeset->getSubmittedBy());
        $submitted_by->addAttribute('format', 'id');

        $submitted_on = $changeset_xml->addChild('submitted_on', date('c', $changeset->getSubmittedOn()));
        $submitted_on->addAttribute('format', 'ISO8601');

        $this->values_exporter->export($artifact_xml, $changeset_xml, $changeset->getArtifact(), $changeset->getValues());
    }
}
