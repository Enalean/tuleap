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

class Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporterTest extends TuleapTestCase {

    /** @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter */
    private $exporter;

    /** @var SimpleXMLElement */
    private $changeset_xml;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var Tracker_Artifact_ChangesetValue_File */
    private $changeset_value;

    /** @var Tracker_XML_Exporter_FilePathXMLExporter */
    private $path_exporter;

    /** @var string */
    private $id_prefix;

    /** @var Tracker_FormElement_Field */
    private $field;

    /** @var Tracker_Artifact_Changeset **/
    private $changeset;

    /** @var Tracker_Artifact **/
    private $artifact;

    public function setUp() {
        parent::setUp();
        $this->field         = aFileField()->withName('attachment')->build();
        $this->path_exporter = mock('Tracker_XML_Exporter_FilePathXMLExporter');
        $this->exporter      = new Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter($this->path_exporter);
        $this->artifact_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');
        $this->id_prefix     = Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter::ID_PREFIX;
        $this->artifact      = mock('Tracker_Artifact');
        $this->changeset     = mock('Tracker_Artifact_Changeset');

        $file1 = new Tracker_FileInfo(123, '*', '*', 'Description 123', 'file123.txt', 123, 'text/xml');
        $file2 = new Tracker_FileInfo(456, '*', '*', 'Description 456', 'file456.txt', 456, 'text/html');
        $this->changeset_value = mock('Tracker_Artifact_ChangesetValue_File');
        stub($this->changeset_value)->getFiles()->returns(array($file1, $file2));
        stub($this->changeset_value)->getField()->returns($this->field);
        stub($this->changeset_value)->getId()->returns(575);
        stub($this->changeset)->getValue()->returns($this->changeset_value);
        stub($this->artifact)->getLastChangeset()->returns($this->changeset);
    }

    public function itCreatesFileNodeInArtifactNode() {
        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $this->artifact,
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
            $this->artifact,
            $this->changeset_value
        );

        $this->assertEqual((string)$this->artifact_xml->file[0]->path, 'blah');
    }

    public function itCreatesFieldChangeNodeInChangesetNode() {
        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $this->artifact,
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertEqual(count($field_change->value), 2);
        $this->assertEqual((string)$field_change['type'], 'file');
        $this->assertEqual((string)$field_change['field_name'], $this->field->getName());
        $this->assertEqual((string)$field_change->value[0]['ref'], $this->id_prefix . 123);
        $this->assertEqual((string)$field_change->value[1]['ref'], $this->id_prefix . 456);
    }

    public function itOnlyExportsTheLastChangeset() {
        $older_changeset_value = mock('Tracker_Artifact_ChangesetValue_File');
        stub($older_changeset_value)->getId()->returns(9722);
        stub($older_changeset_value)->getField()->returns(aFileField()->build());
        stub($this->artifact)->getLastChangeset()->returns($older_changeset_value);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $this->artifact,
            $older_changeset_value
        );

        $this->assertEqual(count($this->artifact_xml->file), 0);
    }

    public function itExportsFilePathInArchiveContext() {
        $export_in_archive = new Tracker_XML_Exporter_ChangesetValue_ChangesetValueFileXMLExporter(
            new Tracker_XML_Exporter_InArchiveFilePathXMLExporter()
        );

        $export_in_archive->export(
            $this->artifact_xml,
            $this->changeset_xml,
            $this->artifact,
            $this->changeset_value
        );

        $this->assertEqual($this->artifact_xml->file[0]->path, 'data/Artifact123');
    }
}