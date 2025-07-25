<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\Numeric;

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\Float\FloatField;
use Tuleap\Tracker\FormElement\Field\FloatingPointNumber\FloatValueDao;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Field_NumericTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testItDelegatesRetrievalOfTheOldValueToTheDaoWhenNoTimestampGiven(): void
    {
        $user      = new PFUser(['language_id' => 'en']);
        $value_dao = $this->createMock(FloatValueDao::class);
        $value_dao->method('getLastValue')->willReturn(['value' => '123.45']);
        $artifact = ArtifactTestBuilder::anArtifact(123)->build();
        $field    = $this->createPartialMock(FloatField::class, ['userCanRead', 'getValueDao']);
        $field->method('userCanRead')->with($user)->willReturn(true);
        $field->method('getValueDao')->willReturn($value_dao);

        $actual_value = $field->getComputedValue($user, $artifact);
        self::assertEquals('123.45', $actual_value);
    }

    public function testItDelegatesRetrievalOfTheOldValueToTheDaoWhenGivenATimestamp(): void
    {
        $artifact_id = 123;
        $field_id    = 195;
        $user        = new PFUser(['language_id' => 'en']);
        $value_dao   = $this->createMock(FloatValueDao::class);
        $artifact    = ArtifactTestBuilder::anArtifact($artifact_id)->build();
        $field       = $this->createPartialMock(FloatField::class, ['getId', 'userCanRead', 'getValueDao']);
        $timestamp   = 9340590569;
        $value       = 67.89;

        $field->method('getId')->willReturn($field_id);
        $field->method('getValueDao')->willReturn($value_dao);
        $field->method('userCanRead')->with($user)->willReturn(true);
        $value_dao->method('getValueAt')->with($artifact_id, $field_id, $timestamp)->willReturn(['value' => $value]);

        self::assertSame($value, $field->getComputedValue($user, $artifact, $timestamp));
    }

    public function testItReturnsZeroWhenUserDoesntHavePermissions(): void
    {
        $user     = new PFUser(['language_id' => 'en']);
        $artifact = ArtifactTestBuilder::anArtifact(123)->build();
        $field    = FloatFieldBuilder::aFloatField(64512)->withReadPermission($user, false)->build();

        $actual_value = $field->getComputedValue($user, $artifact);
        self::assertEquals(0, $actual_value);
    }
}
