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
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElement_Field_Computed;
use Tuleap\Tracker\Artifact\Artifact;

class ChangesetValuesXMLExporter
{
    public const ARTIFACT_XML_KEY  = 'artifact_xml';
    public const CHANGESET_XML_KEY = 'changeset_xml';
    public const ARTIFACT_KEY      = 'artifact';
    public const EXPORT_MODE_KEY   = 'export_mode';
    public const EXPORT_SNAPSHOT   = true;
    public const EXPORT_CHANGES    = false;

    public function __construct(
        private readonly ChangesetValueXMLExporterVisitor $visitor,
        private readonly bool $is_in_archive_context,
    ) {
    }

    /**
     *
     * @param Tracker_Artifact_ChangesetValue[] $changeset_values
     */
    public function exportSnapshot(
        SimpleXMLElement $artifact_xml,
        SimpleXMLElement $changeset_xml,
        Artifact $artifact,
        array $changeset_values,
    ): void {
        $this->exportValues($artifact_xml, $changeset_xml, $artifact, $changeset_values, self::EXPORT_SNAPSHOT);
    }

    public function exportChangedFields(
        SimpleXMLElement $artifact_xml,
        SimpleXMLElement $changeset_xml,
        Artifact $artifact,
        array $changeset_values,
    ): void {
        $this->exportValues($artifact_xml, $changeset_xml, $artifact, $changeset_values, self::EXPORT_CHANGES);
    }

    private function exportValues(
        SimpleXMLElement $artifact_xml,
        SimpleXMLElement $changeset_xml,
        Artifact $artifact,
        array $changeset_values,
        $export_mode,
    ) {
        $params = [
            self::ARTIFACT_KEY => $artifact,
            self::ARTIFACT_XML_KEY => $artifact_xml,
            self::CHANGESET_XML_KEY => $changeset_xml,
            self::EXPORT_MODE_KEY => $export_mode,
        ];

        foreach ($changeset_values as $changeset_value) {
            if ($changeset_value === null) {
                continue;
            }
            $this->exportValue($changeset_value, $params);
        }
    }

    private function exportValue(
        Tracker_Artifact_ChangesetValue $changeset_value,
        array $params,
    ): void {
        if ($this->isFieldChangeExportable($changeset_value, $params[self::EXPORT_MODE_KEY])) {
            $this->visitor->export(
                $params[self::ARTIFACT_XML_KEY],
                $params[self::CHANGESET_XML_KEY],
                $params[self::ARTIFACT_KEY],
                $changeset_value
            );
        }
    }

    private function isFieldChangeExportable(
        Tracker_Artifact_ChangesetValue $changeset_value,
        $export_mode,
    ): bool {
        if ($export_mode === self::EXPORT_SNAPSHOT) {
            return true;
        }

        if (
            $this->is_in_archive_context &&
            $this->isComputedField($changeset_value) &&
            $changeset_value->getChangeset()->isLastChangesetOfArtifact()
        ) {
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

    private function isFileField(Tracker_Artifact_ChangesetValue $changeset_value): bool
    {
        $field = $changeset_value->getField();

        return $field instanceof \Tracker_FormElement_Field_File;
    }

    private function isComputedField(Tracker_Artifact_ChangesetValue $changeset_value): bool
    {
        $field = $changeset_value->getField();

        return $field instanceof Tracker_FormElement_Field_Computed;
    }
}
