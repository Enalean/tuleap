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
use Tracker_Artifact_ChangesetValue_ArtifactLink;
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
use Tuleap\Tracker\Artifact\ChangesetValueComputed;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueComputedXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ExternalExporterCollector;
use Tuleap\Tracker\Artifact\XML\Exporter\ChangesetValue\ChangesetValueArtifactLinkXMLExporter;
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

class ChangesetValueXMLExporterVisitor implements Tracker_Artifact_ChangesetValueVisitor
{
    /**
     * @var ChangesetValueArtifactLinkXMLExporter
     */
    private $artlink_exporter;

    /**
     * @var ChangesetValuePermissionsOnArtifactXMLExporter
     */
    private $perms_exporter;

    /**
     * @var ChangesetValueUnknownXMLExporter
     */
    private $unknown_exporter;

    /**
     * @var ChangesetValueTextXMLExporter
     */
    private $text_exporter;

    /**
     * @var ChangesetValueListXMLExporter
     */
    private $list_exporter;

    /**
     * @var ChangesetValueOpenListXMLExporter
     */
    private $open_list_exporter;

    /**
     * @var ChangesetValueStringXMLExporter
     */
    private $string_exporter;

    /**
     * @var ChangesetValueIntegerXMLExporter
     */
    private $integer_exporter;

    /**
     * @var ChangesetValueFloatXMLExporter
     */
    private $float_exporter;

    /**
     * @var ChangesetValueDateXMLExporter
     */
    private $date_exporter;

    /**
     * @var ChangesetValueFileXMLExporter
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
        ChangesetValueDateXMLExporter $date_exporter,
        ChangesetValueFileXMLExporter $file_exporter,
        ChangesetValueFloatXMLExporter $float_exporter,
        ChangesetValueIntegerXMLExporter $integer_exporter,
        ChangesetValueStringXMLExporter $string_exporter,
        ChangesetValueTextXMLExporter $text_exporter,
        ChangesetValuePermissionsOnArtifactXMLExporter $perms_exporter,
        ChangesetValueListXMLExporter $list_exporter,
        ChangesetValueOpenListXMLExporter $open_list_exporter,
        ChangesetValueArtifactLinkXMLExporter $artlink_exporter,
        ChangesetValueComputedXMLExporter $computed_exporter,
        ChangesetValueUnknownXMLExporter $unknown_exporter,
        ExternalExporterCollector $collector,
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
        Artifact $artifact,
        Tracker_Artifact_ChangesetValue $changeset_value,
    ) {
        $exporter = $changeset_value->accept($this);
        \assert($exporter instanceof ChangesetValueXMLExporter);
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
