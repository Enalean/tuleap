<?php
/**
 * Copyright (c) Enalean, 2015-present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\File;

use DataAccessResult;
use ForgeConfig;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_File;
use Tracker_FileInfo;
use Tracker_FileInfoFactory;
use Tracker_FormElement_Field_File;
use Tracker_FormElement_RESTValueByField_NotImplementedException;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Tracker\Artifact\Artifact;

final class TrackerFormElementFieldFileTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use GlobalResponseMock;
    use TemporaryTestDirectory;

    /**
     * @var string
     */
    private $tmp_name;
    /**
     * @var string
     */
    private $another_tmp_name;

    protected function setUp(): void
    {
        $this->tmp_name = $this->getTmpDir() . '/uploaded_file.txt';
        touch($this->tmp_name);
        $this->another_tmp_name = $this->getTmpDir() . '/another_uploaded_file.txt';
        touch($this->another_tmp_name);
    }

    protected function tearDown(): void
    {
        unlink($this->tmp_name);
        unlink($this->another_tmp_name);
    }

    public function testGetChangesetValue()
    {
        $value_dao = Mockery::mock(FileFieldValueDao::class);

        $value_dao->shouldReceive('searchById')->andReturn(
            [
                ['changesetvalue_id' => 123, 'fileinfo_id' => 101],
                ['changesetvalue_id' => 123, 'fileinfo_id' => 102],
                ['changesetvalue_id' => 123, 'fileinfo_id' => 103],
            ]
        );
        $tracker_file_info_factory = Mockery::mock(Tracker_FileInfoFactory::class);
        $tracker_file_info_factory->shouldReceive('getById')->andReturn(Mockery::mock(Tracker_FileInfo::class));

        $file_field = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $file_field->shouldReceive('getValueDao')->andReturns($value_dao);
        $file_field->shouldReceive('getTrackerFileInfoFactory')->andReturns($tracker_file_info_factory);

        $changeset_value = $file_field->getChangesetValue(Mockery::mock(Tracker_Artifact_Changeset::class), 123, false);
        $this->assertInstanceOf(Tracker_Artifact_ChangesetValue_File::class, $changeset_value);
        $this->assertEquals(3, count($changeset_value->getFiles()));
    }

    public function testGetChangesetValueDoesntExist()
    {
        $value_dao = Mockery::mock(FileFieldValueDao::class);
        $dar       = Mockery::mock(DataAccessResult::class);
        $dar->shouldReceive('getRow')->andReturn(false);
        $dar->shouldReceive('rewind');
        $dar->shouldReceive('valid');
        $value_dao->shouldReceive('searchById')->andReturn($dar);

        $file_field = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $file_field->shouldReceive('getValueDao')->andReturn($value_dao);
        $file_field->shouldReceive('getTrackerFileInfoFactory')->andReturn(
            Mockery::mock(Tracker_FileInfoFactory::class)
        );

        $changeset_value = $file_field->getChangesetValue(Mockery::mock(Tracker_Artifact_Changeset::class), 123, false);
        $this->assertInstanceOf(Tracker_Artifact_ChangesetValue_File::class, $changeset_value);
        $this->assertEquals(0, count($changeset_value->getFiles()));
    }

    public function testAugmentDataFromRequestNull()
    {
        $f = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('getSubmittedInfoFromFILES')->andReturns(null);
        $f->shouldReceive('getId')->andReturns(66);

        $fields_data = [
            '102' => '123',
        ];
        $this->assertNull($f->augmentDataFromRequest($fields_data));
        $this->assertSame([], $fields_data[66]);
    }

    public function testAugmentDataFromRequestEmptyarray()
    {
        $f = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('getSubmittedInfoFromFILES')->andReturns([]);
        $f->shouldReceive('getId')->andReturns(66);

        $fields_data = [
            '102' => '123',
        ];
        $this->assertNull($f->augmentDataFromRequest($fields_data));
        $this->assertSame([], $fields_data[66]);
    }

    public function testAugmentDataFromRequestOneFileBelongingToField()
    {
        $f = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('getSubmittedInfoFromFILES')->andReturns(
            [
                'name'     => [
                    66 => [
                        0 => ['file' => 'toto.gif'],
                    ],
                ],
                'type'     => [
                    66 => [
                        0 => ['file' => 'image/gif'],
                    ],
                ],
                'error'    => [
                    66 => [
                        0 => ['file' => 0],
                    ],
                ],
                'tmp_name' => [
                    66 => [
                        0 => ['file' => 'dtgjio'],
                    ],
                ],
            ]
        );
        $f->shouldReceive('getId')->andReturns(66);

        $fields_data = [
            '102' => '123',
        ];
        $this->assertNull($f->augmentDataFromRequest($fields_data));
        $this->assertSame(
            [
                0 => [
                    'name'     => 'toto.gif',
                    'type'     => 'image/gif',
                    'error'    => 0,
                    'tmp_name' => 'dtgjio',
                ],
            ],
            $fields_data[66]
        );
    }

    public function testAugmentDataFromRequestTwofilesbelongingToField()
    {
        $f = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('getSubmittedInfoFromFILES')->andReturns(
            [
                'name'     => [
                    66 => [
                        0 => ['file' => 'toto.gif'],
                        1 => ['file' => 'Spec.doc'],
                    ],
                ],
                'type'     => [
                    66 => [
                        0 => ['file' => 'image/gif'],
                        1 => ['file' => 'application/word'],
                    ],
                ],
                'error'    => [
                    66 => [
                        0 => ['file' => 0],
                        1 => ['file' => 1],
                    ],
                ],
                'tmp_name' => [
                    66 => [
                        0 => ['file' => 'dtgjio'],
                        1 => ['file' => ''],
                    ],
                ],
            ]
        );
        $f->shouldReceive('getId')->andReturns(66);

        $fields_data = [
            '102' => '123',
        ];
        $this->assertNull($f->augmentDataFromRequest($fields_data));
        $this->assertSame(
            [
                0 => [
                    'name'     => 'toto.gif',
                    'type'     => 'image/gif',
                    'error'    => 0,
                    'tmp_name' => 'dtgjio',
                ],
                1 => [
                    'name'     => 'Spec.doc',
                    'type'     => 'application/word',
                    'error'    => 1,
                    'tmp_name' => '',
                ],
            ],
            $fields_data[66]
        );
    }

    public function testAugmentDataFromRequestTwoFilesBelongingToFieldAndOneFileNot()
    {
        $f = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('getSubmittedInfoFromFILES')->andReturns(
            [
                'name'     => [
                    66  => [
                        0 => ['file' => 'toto.gif'],
                        1 => ['file' => 'Spec.doc'],
                    ],
                    111 => [
                        0 => ['file' => 'Screenshot.png'],
                    ],
                ],
                'type'     => [
                    66  => [
                        0 => ['file' => 'image/gif'],
                        1 => ['file' => 'application/word'],
                    ],
                    111 => [
                        0 => ['file' => 'image/png'],
                    ],
                ],
                'error'    => [
                    66  => [
                        0 => ['file' => 0],
                        1 => ['file' => 1],
                    ],
                    111 => [
                        0 => ['file' => 0],
                    ],
                ],
                'tmp_name' => [
                    66  => [
                        0 => ['file' => 'dtgjio'],
                        1 => ['file' => ''],
                    ],
                    111 => [
                        0 => ['file' => 'aoeeg'],
                    ],
                ],
            ]
        );
        $f->shouldReceive('getId')->andReturns(66);

        $fields_data = [
            '102' => '123',
        ];
        $this->assertNull($f->augmentDataFromRequest($fields_data));
        $this->assertSame(
            [
                0 => [
                    'name'     => 'toto.gif',
                    'type'     => 'image/gif',
                    'error'    => 0,
                    'tmp_name' => 'dtgjio',
                ],
                1 => [
                    'name'     => 'Spec.doc',
                    'type'     => 'application/word',
                    'error'    => 1,
                    'tmp_name' => '',
                ],
            ],
            $fields_data[66]
        );
        $this->assertFalse(isset($fields_data[111]));
    }

    public function testAugmentDataFromRequestOneFileDoesNotBelongToField()
    {
        $f = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('getSubmittedInfoFromFILES')->andReturns(
            [
                'name'     => [
                    111 => [
                        0 => ['file' => 'toto.gif'],
                    ],
                ],
                'type'     => [
                    111 => [
                        0 => ['file' => 'image/gif'],
                    ],
                ],
                'error'    => [
                    111 => [
                        0 => ['file' => 0],
                    ],
                ],
                'tmp_name' => [
                    111 => [
                        0 => ['file' => 'dtgjio'],
                    ],
                ],
            ]
        );
        $f->shouldReceive('getId')->andReturns(66);

        $fields_data = [
            '102' => '123',
        ];
        $this->assertNull($f->augmentDataFromRequest($fields_data));
        $this->assertSame([], $fields_data[66]);
    }

    public function testAugmentDataFromRequestDontOverrideDescription()
    {
        $f = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('getSubmittedInfoFromFILES')->andReturns(
            [
                'name'     => [
                    66 => [
                        0 => ['file' => 'toto.gif'],
                    ],
                ],
                'type'     => [
                    66 => [
                        0 => ['file' => 'image/gif'],
                    ],
                ],
                'error'    => [
                    66 => [
                        0 => ['file' => 0],
                    ],
                ],
                'tmp_name' => [
                    66 => [
                        0 => ['file' => 'dtgjio'],
                    ],
                ],
            ]
        );
        $f->shouldReceive('getId')->andReturns(66);

        $fields_data = [
            '102' => '123',
            '66'  => [
                '0' => [
                    'description' => 'The description of the file',
                ],
            ],
        ];
        $this->assertNull($f->augmentDataFromRequest($fields_data));
        $this->assertEquals('The description of the file', $fields_data[66][0]['description']);
    }

    public function testIsValidNotFilled()
    {
        $artifact = Mockery::mock(Artifact::class);
        $value    = [
            [
                'description' => '',
                'name'        => '',
                'type'        => '',
                'tmp_name'    => '',
                'error'       => UPLOAD_ERR_NO_FILE,
                'size'        => 0,
            ],
        ];

        $required_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $required_file->shouldReceive('isPreviousChangesetEmpty')->andReturns(true);
        $this->assertFalse($required_file->isValidRegardingRequiredProperty($artifact, $value));

        $not_required_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $not_required_file->shouldReceive('isRequired')->andReturns(false);
        $this->assertTrue($not_required_file->isValidRegardingRequiredProperty($artifact, $value));
    }

    public function testIsValidTwoNotFilled()
    {
        $artifact = Mockery::mock(Artifact::class);
        $value    = [
            [
                'description' => '',
                'name'        => '',
                'type'        => '',
                'tmp_name'    => '',
                'error'       => UPLOAD_ERR_NO_FILE,
                'size'        => 0,
            ],
            [
                'description' => '',
                'name'        => '',
                'type'        => '',
                'tmp_name'    => '',
                'error'       => UPLOAD_ERR_NO_FILE,
                'size'        => 0,
            ],
        ];

        $required_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $required_file->shouldReceive('isPreviousChangesetEmpty')->andReturns(true);
        $this->assertFalse($required_file->isValidRegardingRequiredProperty($artifact, $value));

        $not_required_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $not_required_file->shouldReceive('isRequired')->andReturns(false);
        $this->assertTrue($not_required_file->isValidRegardingRequiredProperty($artifact, $value));
    }

    public function testIsValidOnlyDescriptionFilled()
    {
        $artifact = Mockery::mock(Artifact::class);
        $value    = [
            [
                'description' => 'User sets the description but the file is not submitted (missing, ... ?)',
                'name'        => '',
                'type'        => '',
                'tmp_name'    => '',
                'error'       => UPLOAD_ERR_NO_FILE,
                'size'        => 0,
            ],
        ];

        $required_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $this->assertFalse($required_file->isValid($artifact, $value));

        $not_required_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $not_required_file->shouldReceive('isRequired')->andReturns(false);
        $this->assertFalse($not_required_file->isValid($artifact, $value));
    }

    public function testIsValidDescriptionFilledButErrorWithFileUpload()
    {
        $artifact = Mockery::mock(Artifact::class);
        $value    = [
            [
                'description' => 'User sets the description but the file has error (network, ... ?)',
                'name'        => '',
                'type'        => '',
                'tmp_name'    => '',
                'error'       => UPLOAD_ERR_PARTIAL,
                'size'        => 0,
            ],
        ];

        $required_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $this->assertFalse($required_file->isValid($artifact, $value));

        $not_required_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $not_required_file->shouldReceive('isRequired')->andReturns(false);
        $this->assertFalse($not_required_file->isValid($artifact, $value));
    }

    public function testIsValidDescriptionFilledAndFileOk()
    {
        $artifact = Mockery::mock(Artifact::class);
        $value    = [
            [
                'description' => "Capture d'ecran",
                'name'        => 'Screenshot.png',
                'type'        => 'image/png',
                'tmp_name'    => $this->tmp_name,
                'error'       => UPLOAD_ERR_OK,
                'size'        => 0,
            ],
        ];

        $required_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $this->assertTrue($required_file->isValid($artifact, $value));

        $not_required_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $not_required_file->shouldReceive('isRequired')->andReturns(false);
        $this->assertTrue($not_required_file->isValid($artifact, $value));
    }

    public function itIsValidWhenFieldIsRequiredButHasAFileFromPreviousChangeset()
    {
        $artifact = Mockery::mock(Artifact::class);
        $value    = [
            [
                'description' => "Capture d'ecran",
                'name'        => 'Screenshot.png',
                'type'        => 'image/png',
                'tmp_name'    => $this->tmp_name,
                'error'       => UPLOAD_ERR_OK,
                'size'        => 0,
            ],
        ];

        $required_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $required_file->shouldReceive('isPreviousChangesetEmpty')->andReturns(false);
        $this->assertTrue($required_file->isValid($artifact, $value));
    }

    public function testIsValidTwoFilesOk()
    {
        $artifact = Mockery::mock(Artifact::class);
        $value    = [
            [
                'description' => "Capture d'ecran",
                'name'        => 'Screenshot.png',
                'type'        => 'image/png',
                'tmp_name'    => $this->tmp_name,
                'error'       => UPLOAD_ERR_OK,
                'size'        => 123,
            ],
            [
                'description' => 'Specs',
                'name'        => 'Specifications.doc',
                'type'        => 'application/msword',
                'tmp_name'    => $this->another_tmp_name,
                'error'       => UPLOAD_ERR_OK,
                'size'        => 456,
            ],
        ];

        $required_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $this->assertTrue($required_file->isValid($artifact, $value));

        $not_required_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $not_required_file->shouldReceive('isRequired')->andReturns(false);
        $this->assertTrue($not_required_file->isValid($artifact, $value));
    }

    public function testIsValidOneFileOkAmongTwo()
    {
        $artifact = Mockery::mock(Artifact::class);
        $value    = [
            [
                'description' => "Capture d'ecran",
                'name'        => 'Screenshot.png',
                'type'        => 'image/png',
                'tmp_name'    => $this->tmp_name,
                'error'       => UPLOAD_ERR_OK,
                'size'        => 123,
            ],
            [
                'description' => 'Specs',
                'name'        => 'Specifications.doc',
                'type'        => 'application/msword',
                'tmp_name'    => $this->another_tmp_name,
                'error'       => UPLOAD_ERR_PARTIAL,
                'size'        => 0,
            ],
        ];

        $required_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $this->assertFalse($required_file->isValid($artifact, $value));

        $not_required_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $not_required_file->shouldReceive('isRequired')->andReturns(false);
        $this->assertFalse($not_required_file->isValid($artifact, $value));
    }

    public function testIsValidOneFileOkAndOneEmpty()
    {
        $artifact = Mockery::mock(Artifact::class);
        $value    = [
            [
                'description' => "Capture d'ecran",
                'name'        => 'Screenshot.png',
                'type'        => 'image/png',
                'tmp_name'    => $this->tmp_name,
                'error'       => UPLOAD_ERR_OK,
                'size'        => 123,
            ],
            [
                'description' => '',
                'name'        => '',
                'type'        => '',
                'tmp_name'    => '',
                'error'       => UPLOAD_ERR_NO_FILE,
                'size'        => 0,
            ],
        ];

        $required_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $required_file->shouldReceive('isRequired')->andReturns(true);
        $this->assertTrue($required_file->isValid($artifact, $value));

        $not_required_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $not_required_file->shouldReceive('isRequired')->andReturns(false);
        $this->assertTrue($not_required_file->isValid($artifact, $value));
    }

    public function testGetRootPath()
    {
        ForgeConfig::set('sys_data_dir', dirname(__FILE__) . '/data');
        $f = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $f->shouldReceive('getId')->andReturns(123);
        $this->assertEquals(ForgeConfig::get('sys_data_dir') . '/tracker/123', $f->getRootPath());
    }

    public function testItReturnsTrueWhenTheFieldIsEmptyAtFieldUpdateAndHasAnEmptyPreviousChangeset()
    {
        $formelement_field_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $submitted_value        = [
            [
                'description' => '',
                'name'        => '',
                'type'        => '',
                'tmp_name'    => '',
                'error'       => UPLOAD_ERR_NO_FILE,
                'size'        => 0,
            ],
        ];

        $artifact = Mockery::mock(Artifact::class);

        $formelement_field_file->shouldReceive('checkThatAtLeastOneFileIsUploaded')->andReturn(false);
        $formelement_field_file->shouldReceive('isPreviousChangesetEmpty')->andReturn(true);

        $this->assertTrue($formelement_field_file->isEmpty($submitted_value, $artifact));
    }

    public function testItReturnsFalseWhenTheFieldIsEmptyAtFieldUpdateAndHasAPreviousChangeset()
    {
        $formelement_field_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $submitted_value        = [
            [
                'description' => '',
                'name'        => '',
                'type'        => '',
                'tmp_name'    => '',
                'error'       => UPLOAD_ERR_NO_FILE,
                'size'        => 0,
            ],
        ];

        $file       = new Tracker_FileInfo(123, '*', '*', 'Description 123', 'file123.txt', 123, 'text/xml');
        $changesets = Mockery::mock(Tracker_Artifact_ChangesetValue_File::class);
        $changesets->shouldReceive('getFiles')->andReturn([$file]);
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getLastChangeset')->andReturn($changesets);

        $formelement_field_file->shouldReceive('checkThatAtLeastOneFileIsUploaded')->andReturns(false);
        $formelement_field_file->shouldReceive('isPreviousChangesetEmpty')->andReturns(false);

        $this->assertFalse($formelement_field_file->isEmpty($submitted_value, $artifact));
    }

    public function testItReturnsTrueWhenTheFieldIsEmptyAtFieldUpdateAndHasAPreviousChangesetWhichIsDeleted()
    {
        $formelement_field_file = Mockery::mock(Tracker_FormElement_Field_File::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $submitted_value        = [
            'delete' => [123],
            [
                'description' => '',
                'name'        => '',
                'type'        => '',
                'tmp_name'    => '',
                'error'       => UPLOAD_ERR_NO_FILE,
                'size'        => 0,
            ],
        ];

        $file       = new Tracker_FileInfo(123, '*', '*', 'Description 123', 'file123.txt', 123, 'text/xml');
        $changesets = Mockery::mock(Tracker_Artifact_ChangesetValue_File::class);
        $changesets->shouldReceive('getFiles')->andReturn([$file]);
        $artifact = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getLastChangeset')->andReturn($changesets);

        $formelement_field_file->shouldReceive('checkThatAtLeastOneFileIsUploaded')->andReturns(false);
        $formelement_field_file->shouldReceive('isPreviousChangesetEmpty')->andReturns(true);

        $this->assertTrue($formelement_field_file->isEmpty($submitted_value, $artifact));
    }

    public function testItReturnsTrueWhenTheFieldIsEmptyAtArtifactCreation()
    {
        $formelement_field_file = Mockery::mock(Tracker_FormElement_Field_File::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $submitted_value        = [];

        $no_changeset = Mockery::mock(\Tracker_Artifact_Changeset_Null::class);
        $artifact     = Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getLastChangeset')->andReturn($no_changeset);

        $formelement_field_file->shouldReceive('checkThatAtLeastOneFileIsUploaded')->andReturns(false);

        $this->assertTrue($formelement_field_file->isEmpty($submitted_value, $artifact));
    }

    public function testItThrowsAnExceptionWhenReturningValueIndexedByFieldName(): void
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

        $this->expectException(Tracker_FormElement_RESTValueByField_NotImplementedException::class);

        $field->getFieldDataFromRESTValueByField(['some_value']);
    }
}
