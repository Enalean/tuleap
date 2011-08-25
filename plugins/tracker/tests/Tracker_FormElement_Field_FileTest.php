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

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Field_File.class.php');
Mock::generatePartial(
    'Tracker_FormElement_Field_File',
    'Tracker_FormElement_Field_FileTestVersion', 
    array('getValueDao', 'getFileInfoDao', 'getSubmittedInfoFromFILES', 'getId', 'isRequired', 'getFileInfo'));

require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_Artifact_ChangesetValue_File.class.php');
Mock::generate('Tracker_Artifact_ChangesetValue_File');

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/dao/Tracker_FormElement_Field_Value_FileDao.class.php');
Mock::generate('Tracker_FormElement_Field_Value_FileDao');

require_once(dirname(__FILE__).'/../include/Tracker/dao/Tracker_FileInfoDao.class.php');
Mock::generate('Tracker_FileInfoDao');

require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');

require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_Artifact.class.php');
Mock::generate('Tracker_Artifact');

require_once(dirname(__FILE__).'/../include/Tracker/Tracker_FileInfo.class.php');
Mock::generate('Tracker_FileInfo');

require_once('common/include/Response.class.php');
Mock::generate('Response');

require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

class Tracker_FormElement_Field_FileTest extends UnitTestCase {
    function setUp() {
        Config::store();
        mkdir(dirname(__FILE__) .'/_fixtures/attachments/thumbnails/');
        $GLOBALS['Response'] = new MockResponse();
        $GLOBALS['Language'] = new MockBaseLanguage();
    }
    
    function tearDown() {
        Config::restore();
        foreach(glob(dirname(__FILE__) .'/_fixtures/attachments/thumbnails/*') as $f) {
            if ($f != '.' && $f != '..') {
                unlink($f);
            }
        }
        rmdir(dirname(__FILE__) .'/_fixtures/attachments/thumbnails');
        unset($GLOBALS['Response']);
    }
    
    function testGetChangesetValue() {
        $fileinfo_dao = new MockTracker_FileInfoDao();
        $fi_dar = new MockDataAccessResult();
        $row1 = array(
            'id'           => 101, 
            'submitted_by' => 666, 
            'description'  =>  'Short desc', 
            'filename'     =>  'Screenshot.png', 
            'filesize'     => 123456, 
            'filetype'     => 'image/png');
        $row2 = array(
            'id'           => 102, 
            'submitted_by' => 666, 
            'description'  =>  'Short desc', 
            'filename'     =>  'Screenshot1.png', 
            'filesize'     => 123456, 
            'filetype'     => 'image/png');
        $row3 = array(
            'id'           => 103, 
            'submitted_by' => 666, 
            'description'  =>  'Short desc', 
            'filename'     =>  'Screenshot2.png', 
            'filesize'     => 123456, 
            'filetype'     => 'image/png');
        $fi_dar->setReturnValueAt(0, 'getRow', $row1);
        $fi_dar->setReturnValueAt(1, 'getRow', $row2);
        $fi_dar->setReturnValueAt(2, 'getRow', $row3);
        $fi_dar->setReturnValue('getRow', false);
        
        $f1 = new MockTracker_FileInfo();
        $f2 = new MockTracker_FileInfo();
        $f3 = new MockTracker_FileInfo();
        
        $fileinfo_dao->setReturnReference('searchById', $fi_dar);
        
        $value_dao = new MockTracker_FormElement_Field_Value_FileDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValueAt(0, 'current', array('changesetvalue_id' => 123, 'fileinfo_id' => 101));
        $dar->setReturnValueAt(1, 'current', array('changesetvalue_id' => 123, 'fileinfo_id' => 102));
        $dar->setReturnValueAt(2, 'current', array('changesetvalue_id' => 123, 'fileinfo_id' => 103));
        $dar->setReturnValue('valid', true);
        $dar->setReturnValueAt(3, 'valid', false);
        
        $value_dao->setReturnReference('searchById', $dar);
        
        $file_field = new Tracker_FormElement_Field_FileTestVersion();
        $file_field->setReturnReference('getValueDao', $value_dao);
        $file_field->setReturnReference('getFileInfoDao', $fileinfo_dao);
        $file_field->setReturnReference('getFileInfo', $f1, array(101, $row1));
        $file_field->setReturnReference('getFileInfo', $f2, array(102, $row2));
        $file_field->setReturnReference('getFileInfo', $f3, array(103, $row3));
        
        $changeset_value = $file_field->getChangesetValue(null, 123, false);
        $this->assertIsA($changeset_value, 'Tracker_Artifact_ChangesetValue_File');
        $this->assertEqual(count($changeset_value->getFiles()), 3);
    }
    
    function testGetChangesetValue_doesnt_exist() {
        $value_dao = new MockTracker_FormElement_Field_Value_FileDao();
        $dar = new MockDataAccessResult();
        $dar->setReturnValue('getRow', false);
        $value_dao->setReturnReference('searchById', $dar);
        
        $file_field = new Tracker_FormElement_Field_FileTestVersion();
        $file_field->setReturnReference('getValueDao', $value_dao);
        
        $changeset_value = $file_field->getChangesetValue(null, 123, false);
        $this->assertIsA($changeset_value, 'Tracker_Artifact_ChangesetValue_File');
        $this->assertEqual(count($changeset_value->getFiles()), 0);
    }
    
    function testSoapAvailableValues() {
        $f = new Tracker_FormElement_Field_FileTestVersion();
        $this->assertNull($f->getSoapAvailableValues());
    }
    
    function testGetFieldData() {
        $file_field = new Tracker_FormElement_Field_FileTestVersion();
        $this->assertNotNull($file_field->getFieldData('/var/lib/codendi/ftp/users/my_file.iso'));
        $this->assertIdentical(array(), $file_field->getFieldData('/usr/share/codendi/VERSION'));
        $this->assertNotNull($file_field->getFieldData(null));
        $this->assertIdentical(array(), $file_field->getFieldData(null));
    }
    
    function test_augmentDataFromRequest_null() {
        $f = new Tracker_FormElement_Field_FileTestVersion();
        $f->setReturnValue('getSubmittedInfoFromFILES', null);
        $f->setReturnValue('getId', 66);
        
        $fields_data = array(
            '102' => '123'
        );
        $this->assertNull($f->augmentDataFromRequest($fields_data));
        $this->assertIdentical($fields_data[66], array());
    }
    
    function test_augmentDataFromRequest_emptyarray() {
        $f = new Tracker_FormElement_Field_FileTestVersion();
        $f->setReturnValue('getSubmittedInfoFromFILES', array());
        $f->setReturnValue('getId', 66);
        
        $fields_data = array(
            '102' => '123'
        );
        $this->assertNull($f->augmentDataFromRequest($fields_data));
        $this->assertIdentical($fields_data[66], array());
    }
    
    function test_augmentDataFromRequest_one_file_belonging_to_field() {
        $f = new Tracker_FormElement_Field_FileTestVersion();
        $f->setReturnValue(
            'getSubmittedInfoFromFILES', 
            array(
                'name' => array(
                    66 => array(
                        0 => array('file' => 'toto.gif'),
                    ),
                ),
                'type' => array(
                    66 => array(
                        0 => array('file' => 'image/gif'),
                    ),
                ),
                'error' => array(
                    66 => array(
                        0 => array('file' => 0),
                    ),
                ),
                'tmp_name' => array(
                    66 => array(
                        0 => array('file' => 'dtgjio'),
                    ),
                ),
            )
        );
        $f->setReturnValue('getId', 66);
        
        $fields_data = array(
            '102' => '123'
        );
        $this->assertNull($f->augmentDataFromRequest($fields_data));
        $this->assertIdentical($fields_data[66], array(
            0 => array(
                'name' => 'toto.gif',
                'type' => 'image/gif',
                'error' => 0,
                'tmp_name' => 'dtgjio',
            ),
        ));
    }
    
    function test_augmentDataFromRequest_two_files_belonging_to_field() {
        $f = new Tracker_FormElement_Field_FileTestVersion();
        $f->setReturnValue(
            'getSubmittedInfoFromFILES', 
            array(
                'name' => array(
                    66 => array(
                        0 => array('file' => 'toto.gif'),
                        1 => array('file' => 'Spec.doc'),
                    ),
                ),
                'type' => array(
                    66 => array(
                        0 => array('file' => 'image/gif'),
                        1 => array('file' => 'application/word'),
                    ),
                ),
                'error' => array(
                    66 => array(
                        0 => array('file' => 0),
                        1 => array('file' => 1),
                    ),
                ),
                'tmp_name' => array(
                    66 => array(
                        0 => array('file' => 'dtgjio'),
                        1 => array('file' => ''),
                    ),
                ),
            )
        );
        $f->setReturnValue('getId', 66);
        
        $fields_data = array(
            '102' => '123'
        );
        $this->assertNull($f->augmentDataFromRequest($fields_data));
        $this->assertIdentical($fields_data[66], array(
            0 => array(
                'name' => 'toto.gif',
                'type' => 'image/gif',
                'error' => 0,
                'tmp_name' => 'dtgjio',
            ),
            1 => array(
                'name' => 'Spec.doc',
                'type' => 'application/word',
                'error' => 1,
                'tmp_name' => '',
            ),
        ));
    }
    
    function test_augmentDataFromRequest_two_files_belonging_to_field_and_one_file_not() {
        $f = new Tracker_FormElement_Field_FileTestVersion();
        $f->setReturnValue(
            'getSubmittedInfoFromFILES', 
            array(
                'name' => array(
                    66 => array(
                        0 => array('file' => 'toto.gif'),
                        1 => array('file' => 'Spec.doc'),
                    ),
                    111 => array(
                        0 => array('file' => 'Screenshot.png'),
                    ),
                ),
                'type' => array(
                    66 => array(
                        0 => array('file' => 'image/gif'),
                        1 => array('file' => 'application/word'),
                    ),
                    111 => array(
                        0 => array('file' => 'image/png'),
                    ),
                ),
                'error' => array(
                    66 => array(
                        0 => array('file' => 0),
                        1 => array('file' => 1),
                    ),
                    111 => array(
                        0 => array('file' => 0),
                    ),
                ),
                'tmp_name' => array(
                    66 => array(
                        0 => array('file' => 'dtgjio'),
                        1 => array('file' => ''),
                    ),
                    111 => array(
                        0 => array('file' => 'aoeeg'),
                    ),
                ),
            )
        );
        $f->setReturnValue('getId', 66);
        
        $fields_data = array(
            '102' => '123'
        );
        $this->assertNull($f->augmentDataFromRequest($fields_data));
        $this->assertIdentical($fields_data[66], array(
            0 => array(
                'name' => 'toto.gif',
                'type' => 'image/gif',
                'error' => 0,
                'tmp_name' => 'dtgjio',
            ),
            1 => array(
                'name' => 'Spec.doc',
                'type' => 'application/word',
                'error' => 1,
                'tmp_name' => '',
            ),
        ));
        $this->assertFalse(isset($fields_data[111]));
    }
    
    function test_augmentDataFromRequest_one_file_does_not_belong_to_field() {
        $f = new Tracker_FormElement_Field_FileTestVersion();
        $f->setReturnValue(
            'getSubmittedInfoFromFILES', 
            array(
                'name' => array(
                    111 => array(
                        0 => array('file' => 'toto.gif'),
                    ),
                ),
                'type' => array(
                    111 => array(
                        0 => array('file' => 'image/gif'),
                    ),
                ),
                'error' => array(
                    111 => array(
                        0 => array('file' => 0),
                    ),
                ),
                'tmp_name' => array(
                    111 => array(
                        0 => array('file' => 'dtgjio'),
                    ),
                ),
            )
        );
        $f->setReturnValue('getId', 66);
        
        $fields_data = array(
            '102' => '123'
        );
        $this->assertNull($f->augmentDataFromRequest($fields_data));
        $this->assertIdentical($fields_data[66], array());
    }
    
    function test_augmentDataFromRequest_dont_override_description() {
        $f = new Tracker_FormElement_Field_FileTestVersion();
        $f->setReturnValue(
            'getSubmittedInfoFromFILES', 
            array(
                'name' => array(
                    66 => array(
                        0 => array('file' => 'toto.gif'),
                    ),
                ),
                'type' => array(
                    66 => array(
                        0 => array('file' => 'image/gif'),
                    ),
                ),
                'error' => array(
                    66 => array(
                        0 => array('file' => 0),
                    ),
                ),
                'tmp_name' => array(
                    66 => array(
                        0 => array('file' => 'dtgjio'),
                    ),
                ),
            )
        );
        $f->setReturnValue('getId', 66);
        
        $fields_data = array(
            '102' => '123',
            '66'  => array(
                '0' => array(
                    'description' => 'The description of the file',
                ),
            ),
        );
        $this->assertNull($f->augmentDataFromRequest($fields_data));
        $this->assertEqual($fields_data[66][0]['description'], 'The description of the file');
    }
    
    function test_createThumbnail() {
        $f = new Tracker_FormElement_Field_FileTestVersion();
        $thumb_png = dirname(__FILE__) .'/_fixtures/attachments/thumbnails/66';
        $this->assertFalse(file_exists($thumb_png));
        $f->createThumbnail(66, dirname(__FILE__) .'/_fixtures/attachments/', dirname(__FILE__) .'/_fixtures/attachments/logo.png');
        $this->assertTrue(file_exists($thumb_png));
        $this->assertEqual(getimagesize($thumb_png), array(
            150,
            55,
            IMAGETYPE_PNG,
            'width="150" height="55"',
            'bits' => 8,
            'mime' => 'image/png'
        ));
        
        $thumb_gif = dirname(__FILE__) .'/_fixtures/attachments/thumbnails/111';
        $this->assertFalse(file_exists($thumb_gif));
        $f->createThumbnail(111, dirname(__FILE__) .'/_fixtures/attachments/', dirname(__FILE__) .'/_fixtures/attachments/logo.gif');
        $this->assertTrue(file_exists($thumb_gif));
        $this->assertEqual(getimagesize($thumb_gif), array(
            150,
            55,
            IMAGETYPE_GIF,
            'width="150" height="55"',
            'bits' => 8,
            'channels' => 3,
            'mime' => 'image/gif'
        ));
        
        /* TODO: add suport for jpeg 
        
        $thumb_jpg = dirname(__FILE__) .'/_fixtures/attachments/thumbnails/421';
        $this->assertFalse(file_exists($thumb_jpg));
        $f->createThumbnail(421, dirname(__FILE__) .'/_fixtures/attachments/', dirname(__FILE__) .'/_fixtures/attachments/logo.jpg');
        $this->assertTrue(file_exists($thumb_jpg));
        $this->assertEqual(getimagesize($thumb_jpg), array(
            150,
            55,
            IMAGETYPE_JPEG,
            'width="150" height="55"',
            'bits' => 8,
            'channels' => 3,
            'mime' => 'image/jpg'
        ));
        /**/
    }
    
    function test_isValid_not_filled() {
        $artifact = new MockTracker_Artifact();
        $value = array(
            array(
                'description' =>  '',
                'name'        =>  '',
                'type'        =>  '',
                'tmp_name'    =>  '',
                'error'       =>  UPLOAD_ERR_NO_FILE,
                'size'        =>  0,
            )
        );
        
        $required_file = new Tracker_FormElement_Field_FileTestVersion();
        $required_file->setReturnValue('isRequired', true);
        $this->assertFalse($required_file->isValid($artifact, $value));
        
        $not_required_file = new Tracker_FormElement_Field_FileTestVersion();
        $not_required_file->setReturnValue('isRequired', false);
        $this->assertTrue($not_required_file->isValid($artifact, $value));
    }
    
    function test_isValid_two_not_filled() {
        $artifact = new MockTracker_Artifact();
        $value = array(
            array(
                'description' =>  '',
                'name'        =>  '',
                'type'        =>  '',
                'tmp_name'    =>  '',
                'error'       =>  UPLOAD_ERR_NO_FILE,
                'size'        =>  0,
            ),
            array(
                'description' =>  '',
                'name'        =>  '',
                'type'        =>  '',
                'tmp_name'    =>  '',
                'error'       =>  UPLOAD_ERR_NO_FILE,
                'size'        =>  0,
            )
        );
        
        $required_file = new Tracker_FormElement_Field_FileTestVersion();
        $required_file->setReturnValue('isRequired', true);
        $this->assertFalse($required_file->isValid($artifact, $value));
        
        $not_required_file = new Tracker_FormElement_Field_FileTestVersion();
        $not_required_file->setReturnValue('isRequired', false);
        $this->assertTrue($not_required_file->isValid($artifact, $value));
    }
    
    function test_isValid_only_description_filled() {
        $artifact = new MockTracker_Artifact();
        $value = array(
            array(
                'description' =>  'User sets the description but the file is not submitted (missing, ... ?)',
                'name'        =>  '',
                'type'        =>  '',
                'tmp_name'    =>  '',
                'error'       =>  UPLOAD_ERR_NO_FILE,
                'size'        =>  0,
            )
        );
        
        $required_file = new Tracker_FormElement_Field_FileTestVersion();
        $required_file->setReturnValue('isRequired', true);
        $this->assertFalse($required_file->isValid($artifact, $value));
        
        $not_required_file = new Tracker_FormElement_Field_FileTestVersion();
        $not_required_file->setReturnValue('isRequired', false);
        $this->assertFalse($not_required_file->isValid($artifact, $value));
    }
    
    function test_isValid_description_filled_but_error_with_file_upload() {
        $artifact = new MockTracker_Artifact();
        $value = array(
            array(
                'description' =>  'User sets the description but the file has error (network, ... ?)',
                'name'        =>  '',
                'type'        =>  '',
                'tmp_name'    =>  '',
                'error'       =>  UPLOAD_ERR_PARTIAL,
                'size'        =>  0,
            )
        );
        
        $required_file = new Tracker_FormElement_Field_FileTestVersion();
        $required_file->setReturnValue('isRequired', true);
        $this->assertFalse($required_file->isValid($artifact, $value));
        
        $not_required_file = new Tracker_FormElement_Field_FileTestVersion();
        $not_required_file->setReturnValue('isRequired', false);
        $this->assertFalse($not_required_file->isValid($artifact, $value));
    }
    
    function test_isValid_description_filled_and_file_ok() {
        $artifact = new MockTracker_Artifact();
        $value = array(
            array(
                'description' =>  "Capture d'ecran",
                'name'        =>  'Screenshot.png',
                'type'        =>  'image/png',
                'tmp_name'    =>  'erfe',
                'error'       =>  UPLOAD_ERR_OK,
                'size'        =>  0,
            )
        );
        
        $required_file = new Tracker_FormElement_Field_FileTestVersion();
        $required_file->setReturnValue('isRequired', true);
        $this->assertTrue($required_file->isValid($artifact, $value));
        
        $not_required_file = new Tracker_FormElement_Field_FileTestVersion();
        $not_required_file->setReturnValue('isRequired', false);
        $this->assertTrue($not_required_file->isValid($artifact, $value));
    }
    
    function test_isValid_two_files_ok() {
        $artifact = new MockTracker_Artifact();
        $value = array(
            array(
                'description' =>  "Capture d'ecran",
                'name'        =>  'Screenshot.png',
                'type'        =>  'image/png',
                'tmp_name'    =>  'erfe',
                'error'       =>  UPLOAD_ERR_OK,
                'size'        =>  123,
            ),
            array(
                'description' =>  'Specs',
                'name'        =>  'Specifications.doc',
                'type'        =>  'application/msword',
                'tmp_name'    =>  'ertgrthg',
                'error'       =>  UPLOAD_ERR_OK,
                'size'        =>  456,
            )
        );
        
        $required_file = new Tracker_FormElement_Field_FileTestVersion();
        $required_file->setReturnValue('isRequired', true);
        $this->assertTrue($required_file->isValid($artifact, $value));
        
        $not_required_file = new Tracker_FormElement_Field_FileTestVersion();
        $not_required_file->setReturnValue('isRequired', false);
        $this->assertTrue($not_required_file->isValid($artifact, $value));
    }
    
    function test_isValid_one_file_ok_among_two() {
        $artifact = new MockTracker_Artifact();
        $value = array(
            array(
                'description' =>  "Capture d'ecran",
                'name'        =>  'Screenshot.png',
                'type'        =>  'image/png',
                'tmp_name'    =>  'erfe',
                'error'       =>  UPLOAD_ERR_OK,
                'size'        =>  123,
            ),
            array(
                'description' =>  'Specs',
                'name'        =>  'Specifications.doc',
                'type'        =>  'application/msword',
                'tmp_name'    =>  'ertgrthg',
                'error'       =>  UPLOAD_ERR_PARTIAL,
                'size'        =>  0,
            )
        );
        
        $required_file = new Tracker_FormElement_Field_FileTestVersion();
        $required_file->setReturnValue('isRequired', true);
        $this->assertFalse($required_file->isValid($artifact, $value));
        
        $not_required_file = new Tracker_FormElement_Field_FileTestVersion();
        $not_required_file->setReturnValue('isRequired', false);
        $this->assertFalse($not_required_file->isValid($artifact, $value));
    }
    
    function test_isValid_one_file_ok_and_one_empty() {
        $artifact = new MockTracker_Artifact();
        $value = array(
            array(
                'description' =>  "Capture d'ecran",
                'name'        =>  'Screenshot.png',
                'type'        =>  'image/png',
                'tmp_name'    =>  'erfe',
                'error'       =>  UPLOAD_ERR_OK,
                'size'        =>  123,
            ),
            array(
                'description' =>  '',
                'name'        =>  '',
                'type'        =>  '',
                'tmp_name'    =>  '',
                'error'       =>  UPLOAD_ERR_NO_FILE,
                'size'        =>  0,
            )
        );
        
        $required_file = new Tracker_FormElement_Field_FileTestVersion();
        $required_file->setReturnValue('isRequired', true);
        $this->assertTrue($required_file->isValid($artifact, $value));
        
        $not_required_file = new Tracker_FormElement_Field_FileTestVersion();
        $not_required_file->setReturnValue('isRequired', false);
        $this->assertTrue($not_required_file->isValid($artifact, $value));
    }
    
    function testGetRootPath() {
        Config::load(dirname(__FILE__).'/_fixtures/local.inc');
        $f = new Tracker_FormElement_Field_FileTestVersion();
        $f->setReturnValue('getId', 123);
        $this->assertEqual($f->getRootPath(), Config::get('sys_data_dir') .'/tracker/123');
    }
}
?>
