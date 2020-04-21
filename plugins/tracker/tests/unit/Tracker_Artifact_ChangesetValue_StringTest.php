<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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

class Tracker_Artifact_ChangesetValue_StringTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItReturnsTheRESTValue(): void
    {
        $field = Mockery::mock(Tracker_FormElement_Field_String::class);
        $field->shouldReceive('getId')->andReturn(10);
        $field->shouldReceive('getLabel')->andReturn("field_string");
        $user  = Mockery::mock(PFUser::class);

        $changeset      = new Tracker_Artifact_ChangesetValue_String(
            111,
            \Mockery::spy(\Tracker_Artifact_Changeset::class),
            $field,
            true,
            'myxedemic enthymematic',
            'text'
        );
        $representation = $changeset->getRESTValue($user);

        $this->assertEquals('myxedemic enthymematic', $representation->value);
    }
}
