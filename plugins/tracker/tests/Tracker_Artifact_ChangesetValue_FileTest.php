<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once('bootstrap.php');
Mock::generate('Tracker_Artifact');


Mock::generate('Tracker_FormElement_Field_File');

Mock::generate('Tracker_FileInfo');

class Tracker_Artifact_ChangesetValue_FileTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->user = mock('PFUser');

        $this->artifact  = mock('Tracker_Artifact');
        $this->changeset = stub('Tracker_Artifact_Changeset')->getArtifact()->returns($this->artifact);
        stub($this->artifact)->getLastChangeset()->returns($this->changeset);
    }

    public function testNoDiff() {
        $info   = new MockTracker_FileInfo();
        $info->setReturnValue('getFilename', 'Screenshot.png');
        $field  = new MockTracker_FormElement_Field_File();
        $file_1 = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, array($info));
        $file_2 = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, array($info));
        $this->assertFalse($file_1->diff($file_2));
        $this->assertFalse($file_2->diff($file_1));
    }

    public function testDiff() {
        $info   = new MockTracker_FileInfo();
        $info->setReturnValue('__toString', '#1 Screenshot.png');
        $info->setReturnValue('getFilename', 'Screenshot.png');
        $field  = new MockTracker_FormElement_Field_File();
        $file_1 = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, array());
        $file_2 = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, array($info));
        stub($this->changeset)->getValue()->returns($file_2);

        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'added', array('plugin_tracker_artifact','added'));
        $GLOBALS['Language']->setReturnValue('getText', 'removed', array('plugin_tracker_artifact','removed'));
        $this->assertEqual($file_1->diff($file_2, 'text'), 'Screenshot.png removed');
        $this->assertEqual($file_2->diff($file_1, 'text'), 'Screenshot.png added');
    }

    public function testDiff_with_lot_of_files() {
        $info1   = new MockTracker_FileInfo();
        $info1->setReturnValue('__toString', '#1 Screenshot1.png');
        $info1->setReturnValue('getFilename', 'Screenshot1.png');
        $info2   = new MockTracker_FileInfo();
        $info2->setReturnValue('__toString', '#2 Screenshot2.png');
        $info2->setReturnValue('getFilename', 'Screenshot2.png');
        $info3   = new MockTracker_FileInfo();
        $info3->setReturnValue('__toString', '#3 Screenshot3.png');
        $info3->setReturnValue('getFilename', 'Screenshot3.png');
        $info4   = new MockTracker_FileInfo();
        $info4->setReturnValue('__toString', '#4 Screenshot4.png');
        $info4->setReturnValue('getFilename', 'Screenshot4.png');
        $field  = new MockTracker_FormElement_Field_File();
        $file_1 = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, array($info1, $info3, $info4));
        $file_2 = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, array($info1, $info2));
        stub($this->changeset)->getValue()->returns($file_2);

        $GLOBALS['Language'] = new MockBaseLanguage($this);
        $GLOBALS['Language']->setReturnValue('getText', 'added', array('plugin_tracker_artifact','added'));
        $GLOBALS['Language']->setReturnValue('getText', 'removed', array('plugin_tracker_artifact','removed'));
        $this->assertEqual($file_1->diff($file_2, 'text'), 'Screenshot2.png removed'. PHP_EOL .'Screenshot3.png, Screenshot4.png added');
        $this->assertEqual($file_2->diff($file_1, 'text'), 'Screenshot3.png, Screenshot4.png removed'. PHP_EOL .'Screenshot2.png added');
    }
}
