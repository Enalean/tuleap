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

require_once(dirname(__FILE__).'/../include/Tracker_FileInfo.class.php');
Mock::generatePartial('Tracker_FileInfo', 'Tracker_FileInfoTestVersion', array('getFiletype'));

require_once(dirname(__FILE__).'/../include/Tracker_FormElement_Field_File.class.php');
Mock::generate('Tracker_FormElement_Field_File');

class Tracker_FileInfoTest extends UnitTestCase {
    
    function setUp() {
        $GLOBALS['sys_data_dir'] = dirname(__FILE__) .'/_fixtures/data';
        
        $field = new MockTracker_FormElement_Field_File();
        $field->setReturnValue('getId', 123);
        
        $id           = 1;
        $submitted_by = 103;
        $description  = 'Screenshot of the issue';
        $filename     = 'screenshot.png';
        $filesize     = 285078;
        $filetype     = 'image/png';
        $this->file_info_1 = new Tracker_FileInfo($id, $field, $submitted_by, $description, $filename, $filesize, $filetype);
        
        $filetype     = 'image/tiff';
        $this->file_info_2 = new Tracker_FileInfo($id, $field, $submitted_by, $description, $filename, $filesize, $filetype);
    }
    function tearDown() {
        unset($GLOBALS['sys_data_dir']);
        unset($this->file_info_1);
        unset($this->file_info_2);
    }
    
    function testProperties() {
        $this->assertEqual($this->file_info_1->getDescription(), 'Screenshot of the issue');
        $this->assertEqual($this->file_info_1->getSubmittedBy(), 103);
        $this->assertEqual($this->file_info_1->getFilename(), 'screenshot.png');
        $this->assertEqual($this->file_info_1->getFilesize(), 285078);
        $this->assertEqual($this->file_info_1->getFiletype(), 'image/png');
        $this->assertEqual($this->file_info_1->getId(), 1);
    }
    
    function testGetPath() {
        $this->assertEqual($this->file_info_1->getPath(), $GLOBALS['sys_data_dir'] .'/tracker/123/1');
        $this->assertEqual($this->file_info_1->getThumbnailPath(), $GLOBALS['sys_data_dir'] .'/tracker/123/thumbnails/1');
        $this->assertNull($this->file_info_2->getThumbnailPath(), "A file that is not an image doesn't have any thumbnail (for now)");
    }
    
    function testIsImage() {
        $fi = new Tracker_FileInfoTestVersion();
        $fi->setReturnValueAt(0, 'getFiletype', 'image/png');
        $fi->setReturnValueAt(1, 'getFiletype', 'image/gif');
        $fi->setReturnValueAt(2, 'getFiletype', 'image/jpg');
        $fi->setReturnValueAt(3, 'getFiletype', 'image/jpeg');
        $fi->setReturnValueAt(4, 'getFiletype', 'image/tiff');
        $fi->setReturnValueAt(5, 'getFiletype', 'text/plain');
        $fi->setReturnValueAt(6, 'getFiletype', 'text/gif');
        $this->assertTrue($fi->isImage(), 'image/png should be detected as an image');
        $this->assertTrue($fi->isImage(), 'image/gif should be detected as an image');
        $this->assertTrue($fi->isImage(), 'image/jpg should be detected as an image');
        $this->assertTrue($fi->isImage(), 'image/jpeg should be detected as an image');
        $this->assertFalse($fi->isImage(), 'image/tiff should not be detected as an image');
        $this->assertFalse($fi->isImage(), 'text/plain should not be detected as an image');
        $this->assertFalse($fi->isImage(), 'text/gif should not be detected as an image');
    }
    
    function testHumanReadableFilesize() {
        $sizes = array(
            array(
                'filesize' => 0,
                'human'    => '0 B',
            ),
            array(
                'filesize' => 100,
                'human'    => '100 B',
            ),
            array(
                'filesize' => 1000,
                'human'    => '1000 B',
            ),
            array(
                'filesize' => 1024,
                'human'    => '1 kB',
            ),
            array(
                'filesize' => 10240,
                'human'    => '10 kB',
            ),
            array(
                'filesize' => 1000000,
                'human'    => '977 kB',
            ),
            array(
                'filesize' => 1024 * 100,
                'human'    => '100 kB',
            ),
            array(
                'filesize' => 1024 * 1000,
                'human'    => '1000 kB',
            ),
            array(
                'filesize' => 1024 * 1000 * 10,
                'human'    => '10 MB',
            ),
            array(
                'filesize' => 1024 * 1000 * 100,
                'human'    => '98 MB',
            ),
            array(
                'filesize' => 1024 * 1000 * 1000,
                'human'    => '977 MB',
            ),
        );
        foreach($sizes as $s) {
            $id = $field = $submitted_by = $description = $filename = $filetype = '';
            $f = new Tracker_FileInfo($id, $field, $submitted_by, $description, $filename, $s['filesize'], $filetype);
            $this->assertEqual($f->getHumanReadableFilesize(), $s['human']);
        }
    }
}
?>
