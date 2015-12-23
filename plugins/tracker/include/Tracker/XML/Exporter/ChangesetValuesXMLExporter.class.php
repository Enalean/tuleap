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

class Tracker_XML_Exporter_ChangesetValuesXMLExporter {

    const ARTIFACT_XML_KEY  = 'artifact_xml';
    const CHANGESET_XML_KEY = 'changeset_xml';
    const ARTIFACT_KEY      = 'artifact';
    const EXPORT_MODE_KEY   = 'export_mode';
    const EXPORT_SNAPSHOT   = true;
    const EXPORT_CHANGES    = false;

    /**
     * @var Tracker_XML_Exporter_ChangesetValueXMLExporterVisitor
     */
    private $visitor;

    public function __construct(Tracker_XML_Exporter_ChangesetValueXMLExporterVisitor $visitor) {
        $this->visitor = $visitor;
    }

    /**
     *
     * @param Tracker_FormElement_Field $field
     * @param SimpleXMLElement $artifact_xml
     * @param SimpleXMLElement $changeset_xml
     * @param Tracker_Artifact_ChangesetValue[] $changeset_values
     */
    public function exportSnapshot(
        SimpleXMLElement $artifact_xml,
        SimpleXMLElement $changeset_xml,
        Tracker_Artifact $artifact,
        array $changeset_values
    ) {
        $params = array(
            self::ARTIFACT_KEY      => $artifact,
            self::ARTIFACT_XML_KEY  => $artifact_xml,
            self::CHANGESET_XML_KEY => $changeset_xml,
            self::EXPORT_MODE_KEY   => self::EXPORT_SNAPSHOT
        );

        foreach ($changeset_values as $changeset_value) {
            $this->exportValue($changeset_value, $params);
        }
    }

    public function exportChangedFields(
        SimpleXMLElement $artifact_xml,
        SimpleXMLElement $changeset_xml,
        Tracker_Artifact $artifact,
        array $changeset_values
    ) {
        $params = array(
            self::ARTIFACT_KEY      => $artifact,
            self::ARTIFACT_XML_KEY  => $artifact_xml,
            self::CHANGESET_XML_KEY => $changeset_xml,
            self::EXPORT_MODE_KEY   => self::EXPORT_CHANGES
        );

        foreach ($changeset_values as $changeset_value) {
            $this->exportValue($changeset_value, $params);
        }
    }

    private function exportValue(Tracker_Artifact_ChangesetValue $changeset_value,
        array $params
    ) {
        if ($this->isFieldChangeExportable($params[self::EXPORT_MODE_KEY], $changeset_value)) {
            $this->visitor->export(
                $params[self::ARTIFACT_XML_KEY],
                $params[self::CHANGESET_XML_KEY],
                $params[self::ARTIFACT_KEY],
                $changeset_value
            );
        }
    }

    private function isFieldChangeExportable($export_mode, Tracker_Artifact_ChangesetValue $changeset_value) {
        if ($export_mode === self::EXPORT_SNAPSHOT) {
            return true;
        }

        if ($changeset_value->hasChanged()) {
            return true;
        }

        if ($this->isFileField($changeset_value)) {
            return true;
        }

        return false;
    }

    private function isFileField(Tracker_Artifact_ChangesetValue $changeset_value) {
        $field = $changeset_value->getField();

        return is_a($field, 'Tracker_FormElement_Field_File');
    }
}
