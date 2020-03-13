<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

require_once('bootstrap.php');

class Tracker_FileInfo_CommonTest extends TuleapTestCase
{
    protected $fixture_data_dir;
    protected $working_directory;
    /** @var Tracker_FormElement_Field_File */
    protected $field;
    /** @var Tracker_FileInfo */
    protected $file_info_1;
    /** @var Tracker_FileInfo */
    protected $file_info_2;

    public function setUp()
    {
        parent::setUp();
        $field_id = 123;
        $this->fixture_data_dir  = dirname(__FILE__) . '/_fixtures/attachments';
        $this->working_directory = '/var/tmp/' . $field_id;
        $this->field = mock('Tracker_FormElement_Field_File');
        stub($this->field)->getId()->returns($field_id);
        stub($this->field)->getRootPath()->returns($this->working_directory);

        $id           = 1;
        $submitted_by = 103;
        $description  = 'Screenshot of the issue';
        $filename     = 'screenshot.png';
        $filesize     = 285078;
        $filetype     = 'image/png';
        $this->file_info_1 = new Tracker_FileInfo($id, $this->field, $submitted_by, $description, $filename, $filesize, $filetype);

        $filetype     = 'image/tiff';
        $this->file_info_2 = new Tracker_FileInfo($id, $this->field, $submitted_by, $description, $filename, $filesize, $filetype);

        mkdir($this->working_directory);
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->recurseDeleteInDir($this->working_directory);
        rmdir($this->working_directory);
    }
}

class Tracker_FileInfoTest extends Tracker_FileInfo_CommonTest
{

    public function testProperties()
    {
        $this->assertEqual($this->file_info_1->getDescription(), 'Screenshot of the issue');
        $this->assertEqual($this->file_info_1->getSubmittedBy(), 103);
        $this->assertEqual($this->file_info_1->getFilename(), 'screenshot.png');
        $this->assertEqual($this->file_info_1->getFilesize(), 285078);
        $this->assertEqual($this->file_info_1->getFiletype(), 'image/png');
        $this->assertEqual($this->file_info_1->getId(), 1);
    }

    public function testGetPath()
    {
        $this->assertEqual($this->file_info_1->getPath(), $this->working_directory . '/1');
        $this->assertEqual($this->file_info_1->getThumbnailPath(), $this->working_directory . '/thumbnails/1');
        $this->assertNull($this->file_info_2->getThumbnailPath(), "A file that is not an image doesn't have any thumbnail (for now)");
    }

    public function testIsImage()
    {
        $fi = partial_mock(Tracker_FileInfo::class, array('getFiletype'));
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

    public function testHumanReadableFilesize()
    {
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
        foreach ($sizes as $s) {
            $id = $field = $submitted_by = $description = $filename = $filetype = '';
            $f = new Tracker_FileInfo($id, $field, $submitted_by, $description, $filename, $s['filesize'], $filetype);
            $this->assertEqual($f->getHumanReadableFilesize(), $s['human']);
        }
    }
}

class Tracker_FileInfo_PostUploadActionsTest extends Tracker_FileInfo_CommonTest
{

    private $thumbnails_dir;

    public function setUp()
    {
        parent::setUp();

        $this->thumbnails_dir = $this->working_directory . '/thumbnails';
        mkdir($this->thumbnails_dir);

        $this->backend = mock(Backend::class);
        Backend::setInstance(Backend::BACKEND, $this->backend);

        ForgeConfig::store();
    }

    public function tearDown()
    {
        Backend::clearInstances();
        ForgeConfig::restore();

        parent::tearDown();
    }

    public function itCreatesThumbnailForPng()
    {
        copy($this->fixture_data_dir . '/logo.png', $this->working_directory . '/66');

        $file_info_1 = new Tracker_FileInfo(66, $this->field, 0, '', '', '', 'image/png');
        $this->assertFalse(file_exists($file_info_1->getThumbnailPath()));
        $file_info_1->postUploadActions();

        $this->assertTrue(file_exists($file_info_1->getThumbnailPath()));
        $this->assertEqual(getimagesize($file_info_1->getThumbnailPath()), array(
            150,
            55,
            IMAGETYPE_PNG,
            'width="150" height="55"',
            'bits' => 8,
            'mime' => 'image/png'
        ));
    }

    public function itCreatesThumbnailForGif()
    {
        copy($this->fixture_data_dir . '/logo.gif', $this->working_directory . '/111');

        $file_info_1 = new Tracker_FileInfo(111, $this->field, 0, '', '', '', 'image/gif');
        $this->assertFalse(file_exists($file_info_1->getThumbnailPath()));
        $file_info_1->postUploadActions();

        $this->assertTrue(file_exists($file_info_1->getThumbnailPath()));
        $this->assertEqual(getimagesize($file_info_1->getThumbnailPath()), array(
            150,
            55,
            IMAGETYPE_GIF,
            'width="150" height="55"',
            'bits' => 8,
            'channels' => 3,
            'mime' => 'image/gif'
        ));
    }

    public function itCreatesThumbnailForJpeg()
    {
        copy($this->fixture_data_dir . '/logo.jpg', $this->working_directory . '/421');

        $file_info_1 = new Tracker_FileInfo(421, $this->field, 0, '', '', '', 'image/jpg');
        $this->assertFalse(file_exists($file_info_1->getThumbnailPath()));
        $file_info_1->postUploadActions();

        $this->assertTrue(file_exists($file_info_1->getThumbnailPath()));
        $this->assertEqual(getimagesize($file_info_1->getThumbnailPath()), array(
           150,
           55,
           IMAGETYPE_JPEG,
           'width="150" height="55"',
           'bits' => 8,
           'channels' => 3,
           'mime' => 'image/jpeg'
        ));
    }

    public function itEnsuresFilesIsOwnedByHttpUser()
    {
        copy($this->fixture_data_dir . '/logo.jpg', $this->working_directory . '/421');

        $file_info_1 = new Tracker_FileInfo(421, $this->field, 0, '', '', '', 'image/jpg');
        ForgeConfig::set('sys_http_user', 'user');

        expect($this->backend)->changeOwnerGroupMode($this->working_directory . '/421', 'user', 'user', 0644)->once();

        $file_info_1->postUploadActions();
    }
}
