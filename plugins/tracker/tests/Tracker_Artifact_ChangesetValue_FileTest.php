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

    public function testFiles() {
        $attachment_id = 12;
        $description   = 'struff';
        $submitted_by  = 112;
        $filename = 'MyScreenshot.png';
        $filesize = 69874;
        $filetype = 'image/png';

        $field = new MockTracker_FormElement_Field_File();
        $info  = new Tracker_FileInfo($attachment_id, $field, $submitted_by, $description, $filename, $filesize, $filetype);
        $value_file = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, array($info));
        $this->assertEqual(count($value_file), 1);
        $this->assertEqual($value_file[0], $info);
        $this->assertEqual(
            $value_file->getSoapValue($this->user),
            array(
                'file_info' => array(
                    array(
                        'id' => $attachment_id,
                        'description' => $description,
                        'submitted_by' => $submitted_by,
                        'filename' => $filename,
                        'filesize' => $filesize,
                        'filetype' => $filetype,
                        'action'   => '',
                    )
                )
            )
        );
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

    public function testSoapValue_with_lot_of_files() {
        $description   = 'struff';
        $submitted_by  = 112;
        $filesize = 69874;
        $filetype = 'image/png';

        $attachment1_id = 12;
        $filename1 = 'Screenshot1.png';
        $attachment2_id = 13;
        $filename2 = 'Screenshot2.png';
        $attachment3_id = 14;
        $filename3 = 'Screenshot3.png';

        $field = new MockTracker_FormElement_Field_File();
        $info1  = new Tracker_FileInfo($attachment1_id, $field, $submitted_by, $description, $filename1, $filesize, $filetype);
        $info2  = new Tracker_FileInfo($attachment2_id, $field, $submitted_by, $description, $filename2, $filesize, $filetype);
        $info3  = new Tracker_FileInfo($attachment3_id, $field, $submitted_by, $description, $filename3, $filesize, $filetype);

        $value_file = new Tracker_Artifact_ChangesetValue_File(111, $this->changeset, $field, false, array($info1, $info2, $info3));
        $this->assertEqual(
            $value_file->getSoapValue($this->user),
            array(
                'file_info' => array(
                    array(
                        'id' => $attachment1_id,
                        'description' => $description,
                        'submitted_by' => $submitted_by,
                        'filename' => $filename1,
                        'filesize' => $filesize,
                        'filetype' => $filetype,
                        'action'   => '',
                    ),
                    array(
                        'id' => $attachment2_id,
                        'description' => $description,
                        'submitted_by' => $submitted_by,
                        'filename' => $filename2,
                        'filesize' => $filesize,
                        'filetype' => $filetype,
                        'action'   => '',
                    ),
                    array(
                        'id' => $attachment3_id,
                        'description' => $description,
                        'submitted_by' => $submitted_by,
                        'filename' => $filename3,
                        'filesize' => $filesize,
                        'filetype' => $filetype,
                        'action'   => '',
                    )
                )
            )
        );
    }

}