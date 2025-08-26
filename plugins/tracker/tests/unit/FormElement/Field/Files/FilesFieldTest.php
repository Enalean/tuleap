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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\Files;

use ForgeConfig;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use TestHelper;
use Tracker_Artifact_Changeset_Null;
use Tracker_FileInfo;
use Tracker_FileInfoFactory;
use Tracker_FormElement_RESTValueByField_NotImplementedException;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueFileTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class FilesFieldTest extends TestCase
{
    use GlobalLanguageMock;
    use GlobalResponseMock;
    use TemporaryTestDirectory;

    private string $tmp_name;
    private string $another_tmp_name;

    #[\Override]
    protected function setUp(): void
    {
        $this->tmp_name = $this->getTmpDir() . '/uploaded_file.txt';
        touch($this->tmp_name);
        $this->another_tmp_name = $this->getTmpDir() . '/another_uploaded_file.txt';
        touch($this->another_tmp_name);
    }

    #[\Override]
    protected function tearDown(): void
    {
        unlink($this->tmp_name);
        unlink($this->another_tmp_name);
    }

    public function testGetChangesetValue(): void
    {
        $value_dao = $this->createMock(FileFieldValueDao::class);

        $value_dao->method('searchById')->willReturn([
            ['changesetvalue_id' => 123, 'fileinfo_id' => 101],
            ['changesetvalue_id' => 123, 'fileinfo_id' => 102],
            ['changesetvalue_id' => 123, 'fileinfo_id' => 103],
        ]);
        $tracker_file_info_factory = $this->createMock(Tracker_FileInfoFactory::class);
        $file_field                = $this->createPartialMock(FilesField::class, ['getValueDao', 'getTrackerFileInfoFactory']);
        $tracker_file_info_factory->method('getById')->willReturn(new Tracker_FileInfo(1, $file_field, 101, '', '', 1, ''));

        $file_field->method('getValueDao')->willReturn($value_dao);
        $file_field->method('getTrackerFileInfoFactory')->willReturn($tracker_file_info_factory);

        $changeset_value = $file_field->getChangesetValue(ChangesetTestBuilder::aChangeset(6521)->build(), 123, false);
        self::assertEquals(3, count($changeset_value->getFiles()));
    }

    public function testGetChangesetValueDoesntExist(): void
    {
        $value_dao = $this->createMock(FileFieldValueDao::class);
        $value_dao->method('searchById')->willReturn(TestHelper::emptyDar());

        $file_field = $this->createPartialMock(FilesField::class, ['getValueDao', 'getTrackerFileInfoFactory']);
        $file_field->method('getValueDao')->willReturn($value_dao);
        $file_field->method('getTrackerFileInfoFactory')->willReturn($this->createStub(Tracker_FileInfoFactory::class));

        $changeset_value = $file_field->getChangesetValue(ChangesetTestBuilder::aChangeset(6521)->build(), 123, false);
        self::assertEquals(0, count($changeset_value->getFiles()));
    }

    public function testAugmentDataFromRequestNull(): void
    {
        $f = $this->createPartialMock(FilesField::class, ['getSubmittedInfoFromFILES', 'getId']);
        $f->method('getSubmittedInfoFromFILES')->willReturn(null);
        $f->method('getId')->willReturn(66);

        $fields_data = ['102' => '123'];
        $f->augmentDataFromRequest($fields_data);
        self::assertSame([], $fields_data[66]);
    }

    public function testAugmentDataFromRequestEmptyarray(): void
    {
        $f = $this->createPartialMock(FilesField::class, ['getSubmittedInfoFromFILES', 'getId']);
        $f->method('getSubmittedInfoFromFILES')->willReturn([]);
        $f->method('getId')->willReturn(66);

        $fields_data = ['102' => '123'];
        $f->augmentDataFromRequest($fields_data);
        self::assertSame([], $fields_data[66]);
    }

    public function testAugmentDataFromRequestOneFileBelongingToField(): void
    {
        $f = $this->createPartialMock(FilesField::class, ['getSubmittedInfoFromFILES', 'getId']);
        $f->method('getSubmittedInfoFromFILES')->willReturn([
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
        ]);
        $f->method('getId')->willReturn(66);

        $fields_data = ['102' => '123'];
        $f->augmentDataFromRequest($fields_data);
        self::assertSame([
            0 => [
                'name'     => 'toto.gif',
                'type'     => 'image/gif',
                'error'    => 0,
                'tmp_name' => 'dtgjio',
            ],
        ], $fields_data[66]);
    }

    public function testAugmentDataFromRequestTwofilesbelongingToField(): void
    {
        $f = $this->createPartialMock(FilesField::class, ['getSubmittedInfoFromFILES', 'getId']);
        $f->method('getSubmittedInfoFromFILES')->willReturn([
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
        ]);
        $f->method('getId')->willReturn(66);

        $fields_data = ['102' => '123'];
        $f->augmentDataFromRequest($fields_data);
        self::assertSame([
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
        ], $fields_data[66]);
    }

    public function testAugmentDataFromRequestTwoFilesBelongingToFieldAndOneFileNot(): void
    {
        $f = $this->createPartialMock(FilesField::class, ['getSubmittedInfoFromFILES', 'getId']);
        $f->method('getSubmittedInfoFromFILES')->willReturn([
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
        ]);
        $f->method('getId')->willReturn(66);

        $fields_data = ['102' => '123'];
        $f->augmentDataFromRequest($fields_data);
        self::assertSame([
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
        ], $fields_data[66]);
        self::assertFalse(isset($fields_data[111]));
    }

    public function testAugmentDataFromRequestOneFileDoesNotBelongToField(): void
    {
        $f = $this->createPartialMock(FilesField::class, ['getSubmittedInfoFromFILES', 'getId']);
        $f->method('getSubmittedInfoFromFILES')->willReturn([
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
        ]);
        $f->method('getId')->willReturn(66);

        $fields_data = ['102' => '123'];
        $f->augmentDataFromRequest($fields_data);
        self::assertSame([], $fields_data[66]);
    }

    public function testAugmentDataFromRequestDontOverrideDescription(): void
    {
        $f = $this->createPartialMock(FilesField::class, ['getSubmittedInfoFromFILES', 'getId']);
        $f->method('getSubmittedInfoFromFILES')->willReturn([
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
        ]);
        $f->method('getId')->willReturn(66);

        $fields_data = [
            '102' => '123',
            '66'  => [
                '0' => [
                    'description' => 'The description of the file',
                ],
            ],
        ];
        $f->augmentDataFromRequest($fields_data);
        self::assertEquals('The description of the file', $fields_data[66][0]['description']);
    }

    public function testIsValidNotFilled(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(645)->build();
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

        $required_file = $this->createPartialMock(FilesField::class, ['isRequired', 'isPreviousChangesetEmpty']);
        $required_file->method('isRequired')->willReturn(true);
        $required_file->method('isPreviousChangesetEmpty')->willReturn(true);
        self::assertFalse($required_file->isValidRegardingRequiredProperty($artifact, $value));

        $not_required_file = $this->createPartialMock(FilesField::class, ['isRequired']);
        $not_required_file->method('isRequired')->willReturn(false);
        self::assertTrue($not_required_file->isValidRegardingRequiredProperty($artifact, $value));
    }

    public function testIsValidTwoNotFilled(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(6845)->build();
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

        $required_file = $this->createPartialMock(FilesField::class, ['isRequired', 'isPreviousChangesetEmpty']);
        $required_file->method('isRequired')->willReturn(true);
        $required_file->method('isPreviousChangesetEmpty')->willReturn(true);
        self::assertFalse($required_file->isValidRegardingRequiredProperty($artifact, $value));

        $not_required_file = $this->createPartialMock(FilesField::class, ['isRequired']);
        $not_required_file->method('isRequired')->willReturn(false);
        self::assertTrue($not_required_file->isValidRegardingRequiredProperty($artifact, $value));
    }

    public function testIsValidOnlyDescriptionFilled(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();
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

        $required_file = $this->createPartialMock(FilesField::class, ['isRequired']);
        $required_file->method('isRequired')->willReturn(true);
        self::assertFalse($required_file->isValid($artifact, $value));

        $not_required_file = $this->createPartialMock(FilesField::class, ['isRequired']);
        $not_required_file->method('isRequired')->willReturn(false);
        self::assertFalse($not_required_file->isValid($artifact, $value));
    }

    public function testIsValidDescriptionFilledButErrorWithFileUpload(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();
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

        $required_file = $this->createPartialMock(FilesField::class, ['isRequired']);
        $required_file->method('isRequired')->willReturn(true);
        self::assertFalse($required_file->isValid($artifact, $value));

        $not_required_file = $this->createPartialMock(FilesField::class, ['isRequired']);
        $not_required_file->method('isRequired')->willReturn(false);
        self::assertFalse($not_required_file->isValid($artifact, $value));
    }

    public function testIsValidDescriptionFilledAndFileOk(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();
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

        $required_file = $this->createPartialMock(FilesField::class, ['isRequired']);
        $required_file->method('isRequired')->willReturn(true);
        self::assertTrue($required_file->isValid($artifact, $value));

        $not_required_file = $this->createPartialMock(FilesField::class, ['isRequired']);
        $not_required_file->method('isRequired')->willReturn(false);
        self::assertTrue($not_required_file->isValid($artifact, $value));
    }

    public function testItIsValidWhenFieldIsRequiredButHasAFileFromPreviousChangeset(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(654)->build();
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

        $required_file = $this->createPartialMock(FilesField::class, ['isRequired', 'isPreviousChangesetEmpty']);
        $required_file->method('isRequired')->willReturn(true);
        $required_file->method('isPreviousChangesetEmpty')->willReturn(false);
        self::assertTrue($required_file->isValid($artifact, $value));
    }

    public function testIsValidTwoFilesOk(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(456)->build();
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

        $required_file = $this->createPartialMock(FilesField::class, ['isRequired']);
        $required_file->method('isRequired')->willReturn(true);
        self::assertTrue($required_file->isValid($artifact, $value));

        $not_required_file = $this->createPartialMock(FilesField::class, ['isRequired']);
        $not_required_file->method('isRequired')->willReturn(false);
        self::assertTrue($not_required_file->isValid($artifact, $value));
    }

    public function testIsValidOneFileOkAmongTwo(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(456)->build();
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

        $required_file = $this->createPartialMock(FilesField::class, ['isRequired']);
        $required_file->method('isRequired')->willReturn(true);
        self::assertFalse($required_file->isValid($artifact, $value));

        $not_required_file = $this->createPartialMock(FilesField::class, ['isRequired']);
        $not_required_file->method('isRequired')->willReturn(false);
        self::assertFalse($not_required_file->isValid($artifact, $value));
    }

    public function testIsValidOneFileOkAndOneEmpty(): void
    {
        $artifact = ArtifactTestBuilder::anArtifact(456)->build();
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

        $required_file = $this->createPartialMock(FilesField::class, ['isRequired']);
        $required_file->method('isRequired')->willReturn(true);
        self::assertTrue($required_file->isValid($artifact, $value));

        $not_required_file = $this->createPartialMock(FilesField::class, ['isRequired']);
        $not_required_file->method('isRequired')->willReturn(false);
        self::assertTrue($not_required_file->isValid($artifact, $value));
    }

    public function testGetRootPath(): void
    {
        ForgeConfig::set('sys_data_dir', dirname(__FILE__) . '/data');
        $f = $this->createPartialMock(FilesField::class, ['getId']);
        $f->method('getId')->willReturn(123);
        self::assertEquals(ForgeConfig::get('sys_data_dir') . '/tracker/123', $f->getRootPath());
    }

    public function testItReturnsTrueWhenTheFieldIsEmptyAtFieldUpdateAndHasAnEmptyPreviousChangeset(): void
    {
        $formelement_field_file = $this->createPartialMock(FilesField::class, [
            'checkThatAtLeastOneFileIsUploaded',
            'isPreviousChangesetEmpty',
        ]);
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

        $artifact = ArtifactTestBuilder::anArtifact(456)->build();

        $formelement_field_file->method('checkThatAtLeastOneFileIsUploaded')->willReturn(false);
        $formelement_field_file->method('isPreviousChangesetEmpty')->willReturn(true);

        self::assertTrue($formelement_field_file->isEmpty($submitted_value, $artifact));
    }

    public function testItReturnsFalseWhenTheFieldIsEmptyAtFieldUpdateAndHasAPreviousChangeset(): void
    {
        $formelement_field_file = $this->createPartialMock(FilesField::class, [
            'checkThatAtLeastOneFileIsUploaded',
            'isPreviousChangesetEmpty',
        ]);
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

        $file            = new Tracker_FileInfo(123, $formelement_field_file, 101, 'Description 123', 'file123.txt', 123, 'text/xml');
        $changeset       = ChangesetTestBuilder::aChangeset(54)->build();
        $changeset_value = ChangesetValueFileTestBuilder::aValue(1, $changeset, $formelement_field_file)->withFiles([$file])->build();
        $changeset->setFieldValue($formelement_field_file, $changeset_value);
        $artifact = ArtifactTestBuilder::anArtifact(456)->withChangesets($changeset)->build();

        $formelement_field_file->method('checkThatAtLeastOneFileIsUploaded')->willReturn(false);
        $formelement_field_file->method('isPreviousChangesetEmpty')->willReturn(false);

        self::assertFalse($formelement_field_file->isEmpty($submitted_value, $artifact));
    }

    public function testItReturnsTrueWhenTheFieldIsEmptyAtFieldUpdateAndHasAPreviousChangesetWhichIsDeleted(): void
    {
        $formelement_field_file = $this->createPartialMock(FilesField::class, [
            'checkThatAtLeastOneFileIsUploaded',
            'isPreviousChangesetEmpty',
        ]);
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

        $file            = new Tracker_FileInfo(123, $formelement_field_file, 101, 'Description 123', 'file123.txt', 123, 'text/xml');
        $changeset       = ChangesetTestBuilder::aChangeset(54)->build();
        $changeset_value = ChangesetValueFileTestBuilder::aValue(1, $changeset, $formelement_field_file)->withFiles([$file])->build();
        $changeset->setFieldValue($formelement_field_file, $changeset_value);
        $artifact = ArtifactTestBuilder::anArtifact(456)->withChangesets($changeset)->build();

        $formelement_field_file->method('checkThatAtLeastOneFileIsUploaded')->willReturn(false);
        $formelement_field_file->method('isPreviousChangesetEmpty')->willReturn(true);

        self::assertTrue($formelement_field_file->isEmpty($submitted_value, $artifact));
    }

    public function testItReturnsTrueWhenTheFieldIsEmptyAtArtifactCreation(): void
    {
        $formelement_field_file = $this->createPartialMock(FilesField::class, ['checkThatAtLeastOneFileIsUploaded']);
        $submitted_value        = [];

        $artifact = ArtifactTestBuilder::anArtifact(456)->withChangesets(new Tracker_Artifact_Changeset_Null())->build();

        $formelement_field_file->method('checkThatAtLeastOneFileIsUploaded')->willReturn(false);

        self::assertTrue($formelement_field_file->isEmpty($submitted_value, $artifact));
    }

    public function testItThrowsAnExceptionWhenReturningValueIndexedByFieldName(): void
    {
        $field = new FilesField(
            1,
            101,
            10,
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
