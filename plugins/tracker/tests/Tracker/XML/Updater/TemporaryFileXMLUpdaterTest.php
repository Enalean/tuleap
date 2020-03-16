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
require_once __DIR__ . '/../../../bootstrap.php';

class Tracker_XML_Updater_TemporaryFileXMLUpdaterTest extends TuleapTestCase
{

    /** @var Tracker_XML_Updater_TemporaryFileXMLUpdater */
    private $updater;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    public function setUp()
    {
        parent::setUp();
        $this->artifact_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'
                . '<artifact>'
                . '  <file id="fileinfo_1">'
                . '    <filename>toto.txt</filename>'
                . '    <path>/path/to/toto.txt</path>'
                . '  </file>'
                . '  <file id="fileinfo_2">'
                . '    <filename>Spec.doc</filename>'
                . '    <path>/path/to/Spec.doc</path>'
                . '  </file>'
                . '</artifact>');

        $temporary_file_creator = mock('Tracker_XML_Updater_TemporaryFileCreator');
        stub($temporary_file_creator)->createTemporaryFile('/path/to/toto.txt')->returns('/tmp/toto.txt');
        stub($temporary_file_creator)->createTemporaryFile('/path/to/Spec.doc')->returns('/tmp/Spec.doc');

        $this->updater = new Tracker_XML_Updater_TemporaryFileXMLUpdater($temporary_file_creator);
    }

    public function itReplacesThePathWithTheNewTempraryPath()
    {
        $this->updater->update($this->artifact_xml);

        $this->assertEqual((string) $this->artifact_xml->file[0]->path, '/tmp/toto.txt');
        $this->assertEqual((string) $this->artifact_xml->file[1]->path, '/tmp/Spec.doc');
    }
}
