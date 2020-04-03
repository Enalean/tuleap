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

final class Tracker_FormElement_FieldTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalLanguageMock;
    use \Tuleap\GlobalResponseMock;

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

        $artifact_update = Mockery::mock(Tracker_Artifact::class);
        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue::class);

        foreach ($matrix as $case) {
            $this->setUp();

            $field = Mockery::mock(Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods(
            );
            $field->shouldReceive('getId')->andReturn(101);
            $field->shouldReceive('getLabel')->andReturn('Summary');
            $field->shouldReceive('getName')->andReturn('summary');

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
                $field->shouldReceive('userCanUpdate')->andReturn(true);
            } else {
                $field->shouldReceive('userCanUpdate')->andReturn(false);
            }
            if ($case[3]) {
                $field->shouldReceive('isRequired')->andReturn(true);
            } else {
                $field->shouldReceive('isRequired')->andReturn(false);
            }
            // 4 => Is valid?
            switch ((string) $case[4]) {
                // no need to check
                case '-':
                    $field->shouldReceive('isValid')->never();
                    $field->shouldReceive('setHasErrors')->never();
                    $is_valid = true;
                    break;
                // Error due to required
                case 'R':
                    $field->shouldReceive('isValid')->never();
                    $field->shouldReceive('setHasErrors')->andReturn([true]);
                    $GLOBALS['Language']->shouldReceive(
                        'getText',
                        [
                            'plugin_tracker_common_artifact',
                            'err_required',
                            $field->getLabel() . ' (' . $field->getName() . ')'
                        ]
                    );
                    $GLOBALS['Response']->shouldReceive('addFeedback', ['error', '*']);
                    $is_valid = false;
                    break;
                // Error due to perms
                case 'P':
                    $field->shouldReceive('isValid')->never();
                    $field->shouldReceive('setHasErrors')->andReturn([true]);
                    $GLOBALS['Language']->shouldReceive(
                        'getText',
                        [
                            'plugin_tracker_common_artifact',
                            'bad_field_permission_update',
                            $field->getLabel()
                        ]
                    );
                    $GLOBALS['Response']->expectOnce('addFeedback', ['error', '*']);
                    $is_valid = false;
                    break;
                // Depends on field->isValid()
                case 'V':
                    $field->shouldReceive('isValid')->once()->andReturn(true);
                    $field->shouldReceive('setHasErrors')->never();
                    $is_valid = true;
                    break;
                default:
                    break;
            }

            $result = $field->validateFieldWithPermissionsAndRequiredStatus(
                $artifact_update,
                $submitted_value,
                $last_changeset_value
            );
            $this->assertEquals($is_valid, $result);
        }
    }

    public function testIsValidNotRequired(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $field    = Mockery::mock(Tracker_FormElement_Field::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getLabel')->andReturn('Status');
        $field->shouldReceive('isRequired')->andReturn(false);
        $field->shouldReceive('validate')->withArgs([Mockery::any(), ''])->andReturn(true);
        $field->shouldReceive('validate')->withArgs([Mockery::any(), '123'])->andReturn(false);

        $this->assertFalse($field->hasErrors());

        $this->assertTrue($field->isValid($artifact, ''));
        $this->assertFalse($field->hasErrors());

        $this->assertFalse($field->isValid($artifact, '123'));
        $this->assertTrue($field->hasErrors());
    }

    public function testIsValidRequired(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $field    = Mockery::mock(Tracker_FormElement_Field::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();
        $field->shouldReceive('getLabel')->andReturn('Status');
        $field->shouldReceive('getName')->andReturn('status');
        $field->shouldReceive('isRequired')->andReturn(true)->twice();
        $field->shouldReceive('validate')->andReturn(true)->once();

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
        $field = Mockery::mock(Tracker_FormElement_Field::class)
            ->makePartial()->shouldAllowMockingProtectedMethods();

        $value = [
            "field_id" => 587,
            "value"    => 'some_value'
        ];

        $this->assertEquals('some_value', $field->getFieldDataFromRESTValueByField($value));
    }
}
