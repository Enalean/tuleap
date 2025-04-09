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
use Tracker_Artifact_ChangesetValue_Date;
use Tracker_Artifact_ChangesetValue_File;
use Tracker_Artifact_ChangesetValue_Float;
use Tracker_Artifact_ChangesetValue_Integer;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_Artifact_ChangesetValue_OpenList;
use Tracker_Artifact_ChangesetValue_PermissionsOnArtifact;
use Tracker_Artifact_ChangesetValue_String;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_Artifact_ChangesetValueVisitor;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\ArtifactLink\ArtifactLinkChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValueComputed;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueArtifactLinkXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueComputedXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueDateXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueFileXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueFloatXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueIntegerXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueListXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueOpenListXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValuePermissionsOnArtifactXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueStringXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueTextXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueUnknownXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ExternalExporterCollector;

readonly class ChangesetValueXMLExporterVisitor implements Tracker_Artifact_ChangesetValueVisitor
{
    public function __construct(
        private ChangesetValueDateXMLExporter $date_exporter,
        private ChangesetValueFileXMLExporter $file_exporter,
        private ChangesetValueFloatXMLExporter $float_exporter,
        private ChangesetValueIntegerXMLExporter $integer_exporter,
        private ChangesetValueStringXMLExporter $string_exporter,
        private ChangesetValueTextXMLExporter $text_exporter,
        private ChangesetValuePermissionsOnArtifactXMLExporter $perms_exporter,
        private ChangesetValueListXMLExporter $list_exporter,
        private ChangesetValueOpenListXMLExporter $open_list_exporter,
        private ChangesetValueArtifactLinkXMLExporter $artlink_exporter,
        private ChangesetValueComputedXMLExporter $computed_exporter,
        private ChangesetValueUnknownXMLExporter $unknown_exporter,
        private ExternalExporterCollector $collector,
    ) {
    }

    public function export(
        SimpleXMLElement $artifact_xml,
        SimpleXMLElement $changeset_xml,
        Artifact $artifact,
        Tracker_Artifact_ChangesetValue $changeset_value,
        array $mapping,
    ): void {
        $exporter = $changeset_value->accept($this);
        \assert($exporter instanceof ChangesetValueXMLExporter);
        $exporter->export($artifact_xml, $changeset_xml, $artifact, $changeset_value, $mapping['values']);
    }

    public function visitArtifactLink(ArtifactLinkChangesetValue $changeset_value): ChangesetValueArtifactLinkXMLExporter
    {
        return $this->artlink_exporter;
    }

    public function visitDate(Tracker_Artifact_ChangesetValue_Date $changeset_value): ChangesetValueDateXMLExporter
    {
        return $this->date_exporter;
    }

    public function visitFile(Tracker_Artifact_ChangesetValue_File $changeset_value): ChangesetValueFileXMLExporter
    {
        return $this->file_exporter;
    }

    public function visitFloat(Tracker_Artifact_ChangesetValue_Float $changeset_value): ChangesetValueFloatXMLExporter
    {
        return $this->float_exporter;
    }

    public function visitInteger(Tracker_Artifact_ChangesetValue_Integer $changeset_value): ChangesetValueIntegerXMLExporter
    {
        return $this->integer_exporter;
    }

    public function visitList(Tracker_Artifact_ChangesetValue_List $changeset_value): ChangesetValueListXMLExporter
    {
        return $this->list_exporter;
    }

    public function visitOpenList(Tracker_Artifact_ChangesetValue_OpenList $changeset_value): ChangesetValueOpenListXMLExporter
    {
        return $this->open_list_exporter;
    }

    public function visitPermissionsOnArtifact(Tracker_Artifact_ChangesetValue_PermissionsOnArtifact $changeset_value): ChangesetValuePermissionsOnArtifactXMLExporter
    {
        return $this->perms_exporter;
    }

    public function visitString(Tracker_Artifact_ChangesetValue_String $changeset_value): ChangesetValueStringXMLExporter
    {
        return $this->string_exporter;
    }

    public function visitText(Tracker_Artifact_ChangesetValue_Text $changeset_value): ChangesetValueTextXMLExporter
    {
        return $this->text_exporter;
    }

    public function visitComputed(ChangesetValueComputed $changeset_value): ChangesetValueComputedXMLExporter
    {
        return $this->computed_exporter;
    }

    public function visitExternalField(Tracker_Artifact_ChangesetValue $changeset_value): ChangesetValueXMLExporter
    {
        $external_exporter = $this->collector->collectExporter($changeset_value);

        if ($external_exporter) {
            return $external_exporter;
        }

        return $this->unknown_exporter;
    }
}
