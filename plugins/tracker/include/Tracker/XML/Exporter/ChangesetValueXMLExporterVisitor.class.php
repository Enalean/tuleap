<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\ChangesetValueComputed;
use Tuleap\Tracker\XML\Exporter\ChangesetValue\ChangesetValueComputedXMLExporter;
use Tuleap\Tracker\XML\Exporter\ChangesetValue\ExternalExporterCollector;

class Tracker_XML_Exporter_ChangesetValueXMLExporterVisitor implements Tracker_Artifact_ChangesetValueVisitor
{

    /**
     * @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueArtifactLinkXMLExporter
     */
    private $artlink_exporter;

    /**
     * @var Tracker_XML_Exporter_ChangesetValue_ChangesetValuePermissionsOnArtifactXMLExporter
     */
    private $perms_exporter;

    /**
     * @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueUnknownXMLExporter
     */
    private $unknown_exporter;

    /**
     * @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueTextXMLExporter
     */
    private $text_exporter;

    /**
     * @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueListXMLExporter
     */
    private $list_exporter;

    /**
     * @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter
     */
    private $open_list_exporter;

    /**
     * @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueStringXMLExporter
     */
    private $string_exporter;

    /**
     * @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueIntegerXMLExporter
     */
    private $integer_exporter;

    /**
     * @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueFloatXMLExporter
     */
    private $float_exporter;

    /**
     * @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueDateXMLExporter
     */
    private $date_exporter;

    /**
     * @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter
     */
    private $file_exporter;

    /**
     * @var ChangesetValueComputedXMLExporter
     */
    private $computed_exporter;
    /**
     * @var ExternalExporterCollector
     */
    private $collector;

    public function __construct(
        Tracker_XML_Exporter_ChangesetValue_ChangesetValueDateXMLExporter $date_exporter,
        Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter $file_exporter,
        Tracker_XML_Exporter_ChangesetValue_ChangesetValueFloatXMLExporter $float_exporter,
        Tracker_XML_Exporter_ChangesetValue_ChangesetValueIntegerXMLExporter $integer_exporter,
        Tracker_XML_Exporter_ChangesetValue_ChangesetValueStringXMLExporter $string_exporter,
        Tracker_XML_Exporter_ChangesetValue_ChangesetValueTextXMLExporter $text_exporter,
        Tracker_XML_Exporter_ChangesetValue_ChangesetValuePermissionsOnArtifactXMLExporter $perms_exporter,
        Tracker_XML_Exporter_ChangesetValue_ChangesetValueListXMLExporter $list_exporter,
        Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter $open_list_exporter,
        Tracker_XML_Exporter_ChangesetValue_ChangesetValueArtifactLinkXMLExporter $artlink_exporter,
        ChangesetValueComputedXMLExporter $computed_exporter,
        Tracker_XML_Exporter_ChangesetValue_ChangesetValueUnknownXMLExporter $unknown_exporter,
        ExternalExporterCollector $collector
    ) {
        $this->file_exporter      = $file_exporter;
        $this->date_exporter      = $date_exporter;
        $this->float_exporter     = $float_exporter;
        $this->integer_exporter   = $integer_exporter;
        $this->string_exporter    = $string_exporter;
        $this->text_exporter      = $text_exporter;
        $this->perms_exporter     = $perms_exporter;
        $this->list_exporter      = $list_exporter;
        $this->open_list_exporter = $open_list_exporter;
        $this->unknown_exporter   = $unknown_exporter;
        $this->artlink_exporter   = $artlink_exporter;
        $this->computed_exporter  = $computed_exporter;
        $this->collector          = $collector;
    }

    public function export(
        SimpleXMLElement $artifact_xml,
        SimpleXMLElement $changeset_xml,
        Tracker_Artifact $artifact,
        Tracker_Artifact_ChangesetValue $changeset_value
    ) {
        $exporter = $changeset_value->accept($this);
        \assert($exporter instanceof Tracker_XML_Exporter_ChangesetValue_ChangesetValueXMLExporter);
        $exporter->export($artifact_xml, $changeset_xml, $artifact, $changeset_value);
    }

    public function visitArtifactLink(Tracker_Artifact_ChangesetValue_ArtifactLink $changeset_value)
    {
        return $this->artlink_exporter;
    }

    public function visitDate(Tracker_Artifact_ChangesetValue_Date $changeset_value)
    {
        return $this->date_exporter;
    }

    public function visitFile(Tracker_Artifact_ChangesetValue_File $changeset_value)
    {
        return $this->file_exporter;
    }

    public function visitFloat(Tracker_Artifact_ChangesetValue_Float $changeset_value)
    {
        return $this->float_exporter;
    }

    public function visitInteger(Tracker_Artifact_ChangesetValue_Integer $changeset_value)
    {
        return $this->integer_exporter;
    }

    public function visitList(Tracker_Artifact_ChangesetValue_List $changeset_value)
    {
        return $this->list_exporter;
    }

    public function visitOpenList(Tracker_Artifact_ChangesetValue_OpenList $changeset_value)
    {
        return $this->open_list_exporter;
    }

    public function visitPermissionsOnArtifact(Tracker_Artifact_ChangesetValue_PermissionsOnArtifact $changeset_value)
    {
        return $this->perms_exporter;
    }

    public function visitString(Tracker_Artifact_ChangesetValue_String $changeset_value)
    {
        return $this->string_exporter;
    }

    public function visitText(Tracker_Artifact_ChangesetValue_Text $changeset_value)
    {
        return $this->text_exporter;
    }

    public function visitComputed(ChangesetValueComputed $changeset_value)
    {
        return $this->computed_exporter;
    }

    public function visitExternalField(Tracker_Artifact_ChangesetValue $changeset_value)
    {
        $external_exporter = $this->collector->collectExporter($changeset_value);

        if ($external_exporter) {
            return $external_exporter;
        }

        return $this->unknown_exporter;
    }
}
