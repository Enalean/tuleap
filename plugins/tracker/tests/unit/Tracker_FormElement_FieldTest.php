<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\GlobalResponseMock;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_FieldTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use GlobalResponseMock;

    public function testValidateField(): void
    {
        // 0 => Field has value in last changeset
        // 1 => Field submitted in the request
        // 2 => User can update
        // 3 => Field is required
        //
        // 4 => Is valid? =>
        //      '-' => no need to check
        //      'R' => Error due to required
        //      'P' => Error due to perms
        //      'V' => Depends on field->isValid() (see next col)
        // 5 => Should we call field->isValid() ?
        // 6 => Value in new changeset =>
        //      '-' => keep the old value taken from last changeset
        //        0 => No value
        //        1 => Submitted value
        $matrix = [
            [0, 0, 0, 0, '-', 0, 0],
            [0, 0, 0, 1, '-', 0, 0],
            [0, 0, 1, 0, '-', 0, 0],
            [0, 0, 1, 1, 'R', 0, 0],

            [0, 1, 0, 0, 'P', 0, 0],
            [0, 1, 0, 1, 'P', 0, 0],
            [0, 1, 1, 0, 'V', 1, 1],
            [0, 1, 1, 1, 'V', 1, 1],

            [1, 0, 0, 0, '-', 0, '-'],
            [1, 0, 0, 1, '-', 0, '-'],
            [1, 0, 1, 0, '-', 0, '-'],
            [1, 0, 1, 1, '-', 0, '-'],

            [1, 1, 0, 0, 'P', 0, '-'],
            [1, 1, 0, 1, 'P', 0, '-'],
            [1, 1, 1, 0, 'V', 1, 1],
            [1, 1, 1, 1, 'V', 1, 1],
        ];

        $artifact_update = $this->createMock(Artifact::class);
        $changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue::class);

        foreach ($matrix as $case) {
            $this->setUp();

            $field = $this->createPartialMock(StringField::class, ['getId', 'getLabel', 'getName', 'userCanUpdate', 'isRequired', 'isValid', 'setHasErrors']);
            $field->method('getId')->willReturn(101);
            $field->method('getLabel')->willReturn('Summary');
            $field->method('getName')->willReturn('summary');

            if ($case[0]) {
                $last_changeset_value = $changeset_value;
            } else {
                $last_changeset_value = null;
            }
            if ($case[1]) {
                $submitted_value = 'Toto';
            } else {
                $submitted_value = null; //null === no submitted value /!\ != from '' or '0' /!\
            }
            if ($case[2]) {
                $field->method('userCanUpdate')->willReturn(true);
            } else {
                $field->method('userCanUpdate')->willReturn(false);
            }
            if ($case[3]) {
                $field->method('isRequired')->willReturn(true);
            } else {
                $field->method('isRequired')->willReturn(false);
            }
            // 4 => Is valid?
            switch ((string) $case[4]) {
                // no need to check
                case '-':
                    $field->expects($this->never())->method('isValid');
                    $field->expects($this->never())->method('setHasErrors');
                    $is_valid = true;
                    break;
                // Error due to required
                case 'R':
                    $field->expects($this->never())->method('isValid');
                    $field->method('setHasErrors')->willReturn([true]);
                    $GLOBALS['Response']->method('addFeedback')->with('error');
                    $is_valid = false;
                    break;
                // Error due to perms
                case 'P':
                    $field->expects($this->never())->method('isValid');
                    $field->method('setHasErrors')->willReturn([true]);
                    $GLOBALS['Response']->method('addFeedback')->with('error');
                    $is_valid = false;
                    break;
                // Depends on field->isValid()
                case 'V':
                    $field->expects($this->once())->method('isValid')->willReturn(true);
                    $field->expects($this->never())->method('setHasErrors');
                    $is_valid = true;
                    break;
                default:
                    break;
            }

            $result = $field->validateFieldWithPermissionsAndRequiredStatus(
                $artifact_update,
                $submitted_value,
                $this->createMock(PFUser::class),
                $last_changeset_value
            );
            $this->assertEquals($is_valid, $result);
        }
    }

    public function testIsValidNotRequired(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $field    = $this->createPartialMock(StringField::class, ['getLabel', 'getName', 'isRequired', 'validate']);
        $field->method('getLabel')->willReturn('Status');
        $field->method('isRequired')->willReturn(false);
        $field->method('validate')->willReturnCallback(static fn (Artifact $artifact, mixed $value) => $value === '');

        $this->assertFalse($field->hasErrors());

        $this->assertTrue($field->isValid($artifact, ''));
        $this->assertFalse($field->hasErrors());

        $this->assertFalse($field->isValid($artifact, '123'));
        $this->assertTrue($field->hasErrors());
    }

    public function testIsValidRequired(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $field    = $this->createPartialMock(StringField::class, ['getLabel', 'getName', 'isRequired', 'validate']);
        $field->method('getLabel')->willReturn('Status');
        $field->method('getName')->willReturn('status');
        $field->expects($this->exactly(2))->method('isRequired')->willReturn(true);
        $field->expects($this->once())->method('validate')->willReturn(true);

        $this->assertFalse($field->hasErrors());

        $this->assertFalse($field->isValidRegardingRequiredProperty($artifact, ''));
        $this->assertTrue($field->hasErrors());

        $this->assertFalse($field->isValidRegardingRequiredProperty($artifact, null));
        $this->assertTrue($field->hasErrors());

        $this->assertTrue($field->isValid($artifact, '123'));
        $this->assertFalse($field->hasErrors());
    }

    public function itReturnsTheValueIndexedByFieldName(): void
    {
        $field = StringFieldBuilder::aStringField(101)->build();

        $value = [
            'field_id' => 587,
            'value'    => 'some_value',
        ];

        $this->assertEquals('some_value', $field->getFieldDataFromRESTValueByField($value));
    }
}
