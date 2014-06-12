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
require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_XMLExporter_ChangesetValueXMLExporterVisitorTest extends TuleapTestCase {

    /** @var Tracker_XMLExporter_ChangesetValueXMLExporterVisitor */
    private $visitor;

    /** @var SimpleXMLElement */
    private $changeset_xml;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var Tracker_XMLExporter_ChangesetValue_ChangesetValueXMLExporter */
    private $int_exporter;

    /** @var Tracker_XMLExporter_ChangesetValue_ChangesetValueXMLExporter */
    private $float_exporter;

    public function setUp() {
        parent::setUp();
        $this->int_exporter   = mock('Tracker_XMLExporter_ChangesetValue_ChangesetValueIntegerXMLExporter');
        $this->float_exporter = mock('Tracker_XMLExporter_ChangesetValue_ChangesetValueFloatXMLExporter');
        $this->artifact_xml   = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');
        $this->visitor        = new Tracker_XMLExporter_ChangesetValueXMLExporterVisitor(
            mock('Tracker_XMLExporter_ChangesetValue_ChangesetValueDateXMLExporter'),
            mock('Tracker_XMLExporter_ChangesetValue_ChangesetValueFileXMLExporter'),
            $this->float_exporter,
            $this->int_exporter,
            mock('Tracker_XMLExporter_ChangesetValue_ChangesetValueStringXMLExporter'),
            mock('Tracker_XMLExporter_ChangesetValue_ChangesetValueTextXMLExporter'),
            mock('Tracker_XMLExporter_ChangesetValue_ChangesetValuePermissionsOnArtifactXMLExporter'),
            mock('Tracker_XMLExporter_ChangesetValue_ChangesetValueListXMLExporter'),
            mock('Tracker_XMLExporter_ChangesetValue_ChangesetValueOpenListXMLExporter'),
            mock('Tracker_XMLExporter_ChangesetValue_ChangesetValueUnknownXMLExporter')
        );

        $this->int_changeset_value   = new Tracker_Artifact_ChangesetValue_Integer('*', '*', '*', '*');
        $this->float_changeset_value = new Tracker_Artifact_ChangesetValue_Float('*', '*', '*', '*');
    }

    public function itCallsTheIntegerExporterAccordinglyToTheTypeOfTheChangesetValue() {
        expect($this->int_exporter)->export()->once();
        expect($this->float_exporter)->export()->never();

        $this->visitor->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $this->int_changeset_value
        );
    }

    public function itCallsTheFloatExporterAccordinglyToTheTypeOfTheChangesetValue() {
        expect($this->int_exporter)->export()->never();
        expect($this->float_exporter)->export()->Once();

        $this->visitor->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $this->float_changeset_value
        );
    }
}