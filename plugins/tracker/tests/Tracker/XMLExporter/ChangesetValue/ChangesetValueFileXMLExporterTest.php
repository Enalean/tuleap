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

class Tracker_XMLExporter_ChangesetValue_ChangesetValueFileXMLExporterTest extends TuleapTestCase {

    /** @var Tracker_XMLExporter_ChangesetValue_ChangesetValueFileXMLExporter */
    private $exporter;

    /** @var SimpleXMLElement */
    private $changeset_xml;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var Tracker_Artifact_ChangesetValue_File */
    private $changeset_value;

    /** @var Tracker_XMLExporter_FilePathXMLExporter */
    private $path_exporter;

    /** @var string */
    private $id_prefix;

    /** @var Tracker_FormElement_Field */
    private $field;

    public function setUp() {
        parent::setUp();
        $this->field         = aFileField()->withName('attachment')->build();
        $this->path_exporter = mock('Tracker_XMLExporter_FilePathXMLExporter');
        $this->exporter      = new Tracker_XMLExporter_ChangesetValue_ChangesetValueFileXMLExporter($this->path_exporter);
        $this->artifact_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');
        $this->id_prefix     = Tracker_XMLExporter_ChangesetValue_ChangesetValueFileXMLExporter::ID_PREFIX;

        $file1 = new Tracker_FileInfo(123, '*', '*', 'Description 123', 'file123.txt', 123, 'text/xml');
        $file2 = new Tracker_FileInfo(456, '*', '*', 'Description 456', 'file456.txt', 456, 'text/html');
        $this->changeset_value = mock('Tracker_Artifact_ChangesetValue_File');
        stub($this->changeset_value)->getFiles()->returns(array($file1, $file2));
        stub($this->changeset_value)->getField()->returns($this->field);
    }

    public function itCreatesFileNodeInArtifactNode() {
        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            mock('Tracker_Artifact'),
            $this->changeset_value
        );

        $this->assertEqual(count($this->artifact_xml->file), 2);
        $this->assertEqual((string)$this->artifact_xml->file[0]['id'], $this->id_prefix . 123);
        $this->assertEqual((string)$this->artifact_xml->file[0]->filename, 'file123.txt');
        $this->assertEqual((string)$this->artifact_xml->file[0]->filesize, 123);
        $this->assertEqual((string)$this->artifact_xml->file[0]->filetype, 'text/xml');
        $this->assertEqual((string)$this->artifact_xml->file[0]->description, 'Description 123');

        $this->assertEqual((string)$this->artifact_xml->file[1]['id'], $this->id_prefix . 456);
    }

    public function itDelegatesComputationOfPathToDedicatedObject() {
        stub($this->path_exporter)->getPath()->returns('blah');

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            mock('Tracker_Artifact'),
            $this->changeset_value
        );

        $this->assertEqual((string)$this->artifact_xml->file[0]->path, 'blah');
    }

    public function itCreatesFieldChangeNodeInChangesetNode() {
        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            mock('Tracker_Artifact'),
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertEqual(count($field_change->value), 2);
        $this->assertEqual((string)$field_change['type'], 'file');
        $this->assertEqual((string)$field_change['field_name'], $this->field->getName());
        $this->assertEqual((string)$field_change->value[0]['ref'], $this->id_prefix . 123);
        $this->assertEqual((string)$field_change->value[1]['ref'], $this->id_prefix . 456);
    }
}