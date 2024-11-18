<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use Tracker_FormElement_Field_List;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;

final class AreListFieldsCompatibleVerifierTest extends TestCase
{
    private AreListFieldsCompatibleVerifier $verifier;

    protected function setUp(): void
    {
        $this->verifier = new AreListFieldsCompatibleVerifier();
    }

    /**
     * @dataProvider getSourceAndDestinationFieldsForSameBindTypeTest
     */
    public function testReturnsFalseWhenTheFieldsDoNotHaveTheSameTypeOfBind(
        Tracker_FormElement_Field_List $source_field,
        Tracker_FormElement_Field_List $destination_field,
        bool $are_compatible_expectation,
    ): void {
        self::assertSame(
            $are_compatible_expectation,
            $this->verifier->areListFieldsCompatible(
                $source_field,
                $destination_field,
            )
        );
    }

    public function testItReturnsFalseWhenSelectBoxesHaveNotTheSameMultiplicity(): void
    {
        $source_single   = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(197)->build()
        )->build()->getField();
        $source_multiple = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(869)->withMultipleValues()->build()
        )->build()->getField();

        $destination_single   = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(487)->build()
        )->build()->getField();
        $destination_multiple = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(132)->withMultipleValues()->build()
        )->build()->getField();

        self::assertFalse($this->verifier->areListFieldsCompatible($source_single, $destination_multiple));
        self::assertFalse($this->verifier->areListFieldsCompatible($source_multiple, $destination_single));
        self::assertTrue($this->verifier->areListFieldsCompatible($source_single, $destination_single));
        self::assertTrue($this->verifier->areListFieldsCompatible($source_multiple, $destination_multiple));
    }

    public static function getSourceAndDestinationFieldsForSameBindTypeTest(): array
    {
        $static_list_field     = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(992)->build()
        )->build()->getField();
        $user_list_field       = ListUserBindBuilder::aUserBind(
            ListFieldBuilder::aListField(935)->build()
        )->build()->getField();
        $user_group_list_field = ListUserGroupBindBuilder::aUserGroupBind(
            ListFieldBuilder::aListField(807)->build()
        )->build()->getField();

        return [
            [$static_list_field, $static_list_field, true],
            [$static_list_field, $user_list_field, false],
            [$static_list_field, $user_group_list_field, false],

            [$user_list_field, $user_list_field, true],
            [$user_list_field, $static_list_field, false],
            [$user_list_field, $user_group_list_field, false],

            [$user_group_list_field, $user_group_list_field, true],
            [$user_group_list_field, $static_list_field, false],
            [$user_group_list_field, $user_list_field, false],
        ];
    }
}
