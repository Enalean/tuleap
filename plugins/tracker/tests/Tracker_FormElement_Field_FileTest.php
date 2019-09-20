<?php
/**
 * Copyright (c) Enalean, 2015-2019. All Rights Reserved.
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

abstract class Tracker_FormElement_Field_File_BaseTest extends TuleapTestCase
{
    protected $fixture_dir;
    protected $attachment_dir;
    protected $thumbnails_dir;
    protected $tmp_name;
    protected $another_tmp_name;
    protected $file_info_factory;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        ForgeConfig::store();
        $this->fixture_dir    = '/var/tmp'.'/_fixtures';
        if (!is_dir($this->fixture_dir)) {
            mkdir($this->fixture_dir);
        }

        $this->attachment_dir = $this->fixture_dir.'/attachments';
        if (!is_dir($this->attachment_dir)) {
            mkdir($this->attachment_dir);
        }

        $this->thumbnails_dir = $this->attachment_dir.'/thumbnails';
        if (!is_dir($this->thumbnails_dir)) {
            mkdir($this->thumbnails_dir);
        }

        $this->tmp_name         = $this->fixture_dir.'/uploaded_file.txt';
        $this->another_tmp_name = $this->fixture_dir.'/another_uploaded_file.txt';

        $this->file_info_factory = \Mockery::spy(\Tracker_FileInfoFactory::class);

        ForgeConfig::set('sys_http_user', 'user');

        $backend = \Mockery::spy(\Backend::class);
        Backend::setInstance('Backend', $backend);
    }

    public function tearDown()
    {
        foreach (glob($this->thumbnails_dir.'/*') as $f) {
            if ($f != '.' && $f != '..') {
                unlink($f);
            }
        }
        rmdir($this->thumbnails_dir);
        ForgeConfig::restore();
        Backend::clearInstances();

        parent::tearDown();
    }
}

class Tracker_FormElement_Field_FileTest extends Tracker_FormElement_Field_File_BaseTest
{
    function testGetChangesetValue()
    {
        $value_dao = \Mockery::spy(\Tracker_FormElement_Field_Value_FileDao::class);

        stub($value_dao)->searchById()->returnsDarFromArray(
            [
                array('changesetvalue_id' => 123, 'fileinfo_id' => 101),
                array('changesetvalue_id' => 123, 'fileinfo_id' => 102),
                array('changesetvalue_id' => 123, 'fileinfo_id' => 103),
            ]
        );

        $tracker_file_info_factory = \Mockery::spy(\Tracker_FileInfoFactory::class);
        stub($tracker_file_info_factory)->getById()->returns(\Mockery::spy(\Tracker_FileInfo::class));

        $file_field = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $file_field->shouldReceive('getValueDao')->andReturns($value_dao);
        $file_field->shouldReceive('getTrackerFileInfoFactory')->andReturns($tracker_file_info_factory);

        $changeset_value = $file_field->getChangesetValue(\Mockery::spy(\Tracker_Artifact_Changeset::class), 123, false);
        $this->assertIsA($changeset_value, 'Tracker_Artifact_ChangesetValue_File');
        $this->assertEqual(count($changeset_value->getFiles()), 3);
    }

    function testGetChangesetValue_doesnt_exist()
    {
        $value_dao = \Mockery::spy(\Tracker_FormElement_Field_Value_FileDao::class);
        $dar = Mockery::spy(DataAccessResult::class);
        $dar->shouldReceive('getRow')->andReturn(false);
        $value_dao->shouldReceive('searchById')->andReturn($dar);

        $file_field = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $file_field->shouldReceive('getValueDao')->andReturn($value_dao);
        $file_field->shouldReceive('getTrackerFileInfoFactory')->andReturn(Mockery::spy(Tracker_FileInfoFactory::class));

        $changeset_value = $file_field->getChangesetValue(\Mockery::spy(\Tracker_Artifact_Changeset::class), 123, false);
        $this->assertIsA($changeset_value, 'Tracker_Artifact_ChangesetValue_File');
        $this->assertEqual(count($changeset_value->getFiles()), 0);
    }

    function test_augmentDataFromRequest_null()
    {
        $f = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('getSubmittedInfoFromFILES')->andReturns(null);
        $f->shouldReceive('getId')->andReturns(66);

        $fields_data = array(
            '102' => '123'
        );
        $this->assertNull($f->augmentDataFromRequest($fields_data));
        $this->assertIdentical($fields_data[66], array());
    }

    function test_augmentDataFromRequest_emptyarray()
    {
        $f = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('getSubmittedInfoFromFILES')->andReturns(array());
        $f->shouldReceive('getId')->andReturns(66);

        $fields_data = array(
            '102' => '123'
        );
        $this->assertNull($f->augmentDataFromRequest($fields_data));
        $this->assertIdentical($fields_data[66], array());
    }

    function test_augmentDataFromRequest_one_file_belonging_to_field()
    {
        $f = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('getSubmittedInfoFromFILES')->andReturns(array(
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
        ));
        $f->shouldReceive('getId')->andReturns(66);

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

    function test_augmentDataFromRequest_two_files_belonging_to_field()
    {
        $f = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('getSubmittedInfoFromFILES')->andReturns(array(
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
        ));
        $f->shouldReceive('getId')->andReturns(66);

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

    function test_augmentDataFromRequest_two_files_belonging_to_field_and_one_file_not()
    {
        $f = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('getSubmittedInfoFromFILES')->andReturns(array(
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
        ));
        $f->shouldReceive('getId')->andReturns(66);

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

    function test_augmentDataFromRequest_one_file_does_not_belong_to_field()
    {
        $f = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('getSubmittedInfoFromFILES')->andReturns(array(
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
        ));
        $f->shouldReceive('getId')->andReturns(66);

        $fields_data = array(
            '102' => '123'
        );
        $this->assertNull($f->augmentDataFromRequest($fields_data));
        $this->assertIdentical($fields_data[66], array());
    }

    function test_augmentDataFromRequest_dont_override_description()
    {
        $f = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('getSubmittedInfoFromFILES')->andReturns(array(
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
        ));
        $f->shouldReceive('getId')->andReturns(66);

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

    function test_isValid_not_filled()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
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

        $required_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $required_file->shouldReceive('isPreviousChangesetEmpty')->andReturns(true);
        $this->assertFalse($required_file->isValidRegardingRequiredProperty($artifact, $value));

        $not_required_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $not_required_file->shouldReceive('isRequired')->andReturns(false);
        $this->assertTrue($not_required_file->isValidRegardingRequiredProperty($artifact, $value));
    }

    function test_isValid_two_not_filled()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
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

        $required_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $required_file->shouldReceive('isPreviousChangesetEmpty')->andReturns(true);
        $this->assertFalse($required_file->isValidRegardingRequiredProperty($artifact, $value));

        $not_required_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $not_required_file->shouldReceive('isRequired')->andReturns(false);
        $this->assertTrue($not_required_file->isValidRegardingRequiredProperty($artifact, $value));
    }

    function test_isValid_only_description_filled()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
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

        $required_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $this->assertFalse($required_file->isValid($artifact, $value));

        $not_required_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $not_required_file->shouldReceive('isRequired')->andReturns(false);
        $this->assertFalse($not_required_file->isValid($artifact, $value));
    }

    function test_isValid_description_filled_but_error_with_file_upload()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
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

        $required_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $this->assertFalse($required_file->isValid($artifact, $value));

        $not_required_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $not_required_file->shouldReceive('isRequired')->andReturns(false);
        $this->assertFalse($not_required_file->isValid($artifact, $value));
    }

    function test_isValid_description_filled_and_file_ok()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $value = array(
            array(
                'description' =>  "Capture d'ecran",
                'name'        =>  'Screenshot.png',
                'type'        =>  'image/png',
                'tmp_name'    =>  $this->tmp_name,
                'error'       =>  UPLOAD_ERR_OK,
                'size'        =>  0,
            )
        );

        $required_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $this->assertTrue($required_file->isValid($artifact, $value));

        $not_required_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $not_required_file->shouldReceive('isRequired')->andReturns(false);
        $this->assertTrue($not_required_file->isValid($artifact, $value));
    }

    function itIsValidWhenFieldIsRequiredButHasAFileFromPreviousChangeset()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $value = array(
            array(
                'description' =>  "Capture d'ecran",
                'name'        =>  'Screenshot.png',
                'type'        =>  'image/png',
                'tmp_name'    =>  $this->tmp_name,
                'error'       =>  UPLOAD_ERR_OK,
                'size'        =>  0,
            )
        );

        $required_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $required_file->shouldReceive('isPreviousChangesetEmpty')->andReturns(false);
        $this->assertTrue($required_file->isValid($artifact, $value));
    }

    function test_isValid_two_files_ok()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $value = array(
            array(
                'description' =>  "Capture d'ecran",
                'name'        =>  'Screenshot.png',
                'type'        =>  'image/png',
                'tmp_name'    =>  $this->tmp_name,
                'error'       =>  UPLOAD_ERR_OK,
                'size'        =>  123,
            ),
            array(
                'description' =>  'Specs',
                'name'        =>  'Specifications.doc',
                'type'        =>  'application/msword',
                'tmp_name'    =>  $this->another_tmp_name,
                'error'       =>  UPLOAD_ERR_OK,
                'size'        =>  456,
            )
        );

        $required_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $this->assertTrue($required_file->isValid($artifact, $value));

        $not_required_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $not_required_file->shouldReceive('isRequired')->andReturns(false);
        $this->assertTrue($not_required_file->isValid($artifact, $value));
    }

    function test_isValid_one_file_ok_among_two()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $value = array(
            array(
                'description' =>  "Capture d'ecran",
                'name'        =>  'Screenshot.png',
                'type'        =>  'image/png',
                'tmp_name'    =>  $this->tmp_name,
                'error'       =>  UPLOAD_ERR_OK,
                'size'        =>  123,
            ),
            array(
                'description' =>  'Specs',
                'name'        =>  'Specifications.doc',
                'type'        =>  'application/msword',
                'tmp_name'    =>  $this->another_tmp_name,
                'error'       =>  UPLOAD_ERR_PARTIAL,
                'size'        =>  0,
            )
        );

        $required_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $this->assertFalse($required_file->isValid($artifact, $value));

        $not_required_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $not_required_file->shouldReceive('isRequired')->andReturns(false);
        $this->assertFalse($not_required_file->isValid($artifact, $value));
    }

    function test_isValid_one_file_ok_and_one_empty()
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        $value = array(
            array(
                'description' =>  "Capture d'ecran",
                'name'        =>  'Screenshot.png',
                'type'        =>  'image/png',
                'tmp_name'    =>  $this->tmp_name,
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

        $required_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $this->assertTrue($required_file->isValid($artifact, $value));

        $not_required_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $not_required_file->shouldReceive('isRequired')->andReturns(false);
        $this->assertTrue($not_required_file->isValid($artifact, $value));
    }

    function testGetRootPath()
    {
        ForgeConfig::set('sys_data_dir', dirname(__FILE__) .'/data');
        $f = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('getId')->andReturns(123);
        $this->assertEqual($f->getRootPath(), ForgeConfig::get('sys_data_dir') .'/tracker/123');
    }

    public function itReturnsTrueWhenTheFieldIsEmptyAtFieldUpdateAndHasAnEmptyPreviousChangeset()
    {
        $formelement_field_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $submitted_value = array(
            array(
                'description' =>  '',
                'name'        =>  '',
                'type'        =>  '',
                'tmp_name'    =>  '',
                'error'       =>  UPLOAD_ERR_NO_FILE,
                'size'        =>  0,
            )
        );

        $artifact = Mockery::spy(Tracker_Artifact::class);

        $formelement_field_file->shouldReceive('checkThatAtLeastOneFileIsUploaded')->andReturn(false);
        $formelement_field_file->shouldReceive('isPreviousChangesetEmpty')->andReturn(true);

        $this->assertTrue($formelement_field_file->isEmpty($submitted_value, $artifact));
    }

    public function itReturnsFalseWhenTheFieldIsEmptyAtFieldUpdateAndHasAPreviousChangeset()
    {
        $formelement_field_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $submitted_value = array(
            array(
                'description' =>  '',
                'name'        =>  '',
                'type'        =>  '',
                'tmp_name'    =>  '',
                'error'       =>  UPLOAD_ERR_NO_FILE,
                'size'        =>  0,
            )
        );

        $file = new Tracker_FileInfo(123, '*', '*', 'Description 123', 'file123.txt', 123, 'text/xml');
        $changesets = \Mockery::spy(\Tracker_Artifact_ChangesetValue_File::class);
        stub($changesets)->getFiles()->returns(array($file));
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        stub($artifact)->getLastChangeset()->returns($changesets);

        $formelement_field_file->shouldReceive('checkThatAtLeastOneFileIsUploaded')->andReturns(false);
        $formelement_field_file->shouldReceive('isPreviousChangesetEmpty')->andReturns(false);

        $this->assertFalse($formelement_field_file->isEmpty($submitted_value, $artifact));
    }

    public function itReturnsTrueWhenTheFieldIsEmptyAtFieldUpdateAndHasAPreviousChangesetWhichIsDeleted()
    {
        $formelement_field_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $submitted_value = array(
            'delete' => array(123),
            array(
                'description' =>  '',
                'name'        =>  '',
                'type'        =>  '',
                'tmp_name'    =>  '',
                'error'       =>  UPLOAD_ERR_NO_FILE,
                'size'        =>  0,
            )
        );

        $file = new Tracker_FileInfo(123, '*', '*', 'Description 123', 'file123.txt', 123, 'text/xml');
        $changesets = \Mockery::spy(\Tracker_Artifact_ChangesetValue_File::class);
        stub($changesets)->getFiles()->returns(array($file));
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        stub($artifact)->getLastChangeset()->returns($changesets);

        $formelement_field_file->shouldReceive('checkThatAtLeastOneFileIsUploaded')->andReturns(false);
        $formelement_field_file->shouldReceive('isPreviousChangesetEmpty')->andReturns(true);

        $this->assertTrue($formelement_field_file->isEmpty($submitted_value, $artifact));
    }

    public function itReturnsTrueWhenTheFieldIsEmptyAtArtifactCreation()
    {
        $formelement_field_file = \Mockery::mock(\Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $submitted_value = [];

        $no_changeset = \Mockery::spy(\Tracker_Artifact_Changeset_Null::class);
        $artifact = \Mockery::spy(\Tracker_Artifact::class);
        stub($artifact)->getLastChangeset()->returns($no_changeset);

        $formelement_field_file->shouldReceive('checkThatAtLeastOneFileIsUploaded')->andReturns(false);

        $this->assertTrue($formelement_field_file->isEmpty($submitted_value, $artifact));
    }
}

class Tracker_FormElement_Field_File_RESTTests extends TuleapTestCase
{

    public function itThrowsAnExceptionWhenReturningValueIndexedByFieldName()
    {
        $field = new Tracker_FormElement_Field_File(
            1,
            101,
            null,
            'field_file',
            'Field File',
            '',
            1,
            'P',
            true,
            '',
            1
        );

        $this->expectException('Tracker_FormElement_RESTValueByField_NotImplementedException');

        $value = 'some_value';

        $field->getFieldDataFromRESTValueByField($value);
    }
}
