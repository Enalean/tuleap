<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class ArtifactAttachmentExporterTest extends TuleapTestCase {

    /** @var ZipArchive */
    private $archive;

    /** @var string */
    private $archive_path;

    /** @var string */
    private $file01_path;

    /** @var string */
    private $extraction_path;

    public function setUp() {
        parent::setUp();

        $this->archive_path    = dirname(__FILE__) . '/_fixtures/test.zip';
        $this->file01_path     = dirname(__FILE__) . '/_fixtures/file01.txt';
        $this->extraction_path = dirname(__FILE__) . '/_fixtures/extracted/';

        $this->initArchive();
    }

    public function tearDown() {
        exec('rm -rf ' . $this->extraction_path);
        unlink($this->archive_path);

        parent::tearDown();
    }

    public function itAddsFileIntoArchive() {
        $tracker    = aTracker()->build();
        $file_field = mock('Tracker_FormElement_Field_File');
        $file_info  = stub('Tracker_FileInfo')->getPath()->returns($this->file01_path);
        stub($file_info)->getId()->returns(1);

        $files      = array($file_info);
        $file_value = new Tracker_Artifact_ChangesetValue_File(1, $file_field, 1, $files);

        $changeset = stub('Tracker_Artifact_Changeset')->getValue($file_field)->returns($file_value);

        $artifact = stub('Tracker_Artifact')->getTracker()->returns($tracker);
        stub($artifact)->getLastChangeset()->returns($changeset);

        $form_element_factory = stub('Tracker_FormElementFactory')->getUsedFileFields($tracker)->returns(
            array($file_field)
        );

        $exporter = new Tracker_XML_Exporter_ArtifactAttachmentExporter($form_element_factory);

        $exporter->exportAttachmentsInArchive($artifact, $this->archive);

        $this->extractArchive();

        $this->assertEqual(file_get_contents($this->extraction_path . '/data/Artifact1'), 'file01');
    }

    private function initArchive() {
        $this->archive = new ZipArchive();

        $this->archive->open($this->archive_path, ZipArchive::CREATE);
    }

    private function extractArchive() {
        $this->archive->close();

        $this->archive->open($this->archive_path);
        $this->archive->extractTo($this->extraction_path);
        $this->archive->close();
    }
}