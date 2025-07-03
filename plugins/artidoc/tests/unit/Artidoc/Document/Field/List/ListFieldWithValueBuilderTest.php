<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Field\List;

use Tracker_Artifact_ChangesetValue_List;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StaticListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StaticListValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserGroupListValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserGroupsListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserListValue;
use Tuleap\Artidoc\Stubs\Document\Field\List\BuildStaticListFieldWithValueStub;
use Tuleap\Artidoc\Stubs\Document\Field\List\BuildUserGroupListFieldWithValueStub;
use Tuleap\Artidoc\Stubs\Document\Field\List\BuildUserListFieldWithValueStub;
use Tuleap\Color\ItemColor;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ListFieldWithValueBuilderTest extends TestCase
{
    public function testItBuildsUserListFields(): void
    {
        $user_list_field = ListUserBindBuilder::aUserBind(
            ListFieldBuilder::aListField(123)->withLabel('user list field')->build()
        )->build()->getField();

        $expected_list_field_with_value = new UserListFieldWithValue(
            $user_list_field->getLabel(),
            DisplayType::BLOCK,
            [
                new UserListValue('Bob', 'bob_avatar_url.png'),
                new UserListValue('Alice', 'alice_avatar_url.png'),
            ]
        );

        $builder = new ListFieldWithValueBuilder(
            BuildUserListFieldWithValueStub::withCallback(static function (ConfiguredField $configured_field) use ($expected_list_field_with_value): UserListFieldWithValue {
                assert($configured_field->field instanceof \Tracker_FormElement_Field_List);
                assert($configured_field->field->getBind() instanceof \Tracker_FormElement_Field_List_Bind_Users);

                return $expected_list_field_with_value;
            }),
            BuildStaticListFieldWithValueStub::withCallback(static fn () => throw new \Exception('BuildStaticListFieldWithValueStub was not expected to be called')),
            BuildUserGroupListFieldWithValueStub::withCallback(static fn () => throw new \Exception('BuildUserGroupListFieldWithValueStub was not expected to be called')),
        );

        self::assertSame(
            $expected_list_field_with_value,
            $builder->buildListFieldWithValue(
                new ConfiguredField($user_list_field, DisplayType::BLOCK),
                $this->buildDummyChangesetForField($user_list_field),
            )
        );
    }

    public function testItBuildsStaticListFields(): void
    {
        $static_list_field = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(123)->withLabel('static list field')->build()
        )->build()->getField();

        $expected_static_list_field_with_value = new StaticListFieldWithValue(
            $static_list_field->getLabel(),
            DisplayType::BLOCK,
            [
                new StaticListValue('Red', ItemColor::fromName('fiesta-red')),
                new StaticListValue('Blue', ItemColor::fromName('deep-blue')),
            ]
        );

        $builder = new ListFieldWithValueBuilder(
            BuildUserListFieldWithValueStub::withCallback(static fn () => throw new \Exception('BuildUserListFieldWithValueStub was not expected to be called')),
            BuildStaticListFieldWithValueStub::withCallback(static function (ConfiguredField $configured_field) use ($expected_static_list_field_with_value): StaticListFieldWithValue {
                assert($configured_field->field instanceof \Tracker_FormElement_Field_List);
                assert($configured_field->field->getBind() instanceof \Tracker_FormElement_Field_List_Bind_Static);

                return $expected_static_list_field_with_value;
            }),
            BuildUserGroupListFieldWithValueStub::withCallback(static fn () => throw new \Exception('BuildUserGroupListFieldWithValueStub was not expected to be called')),
        );

        self::assertSame(
            $expected_static_list_field_with_value,
            $builder->buildListFieldWithValue(
                new ConfiguredField($static_list_field, DisplayType::BLOCK),
                $this->buildDummyChangesetForField($static_list_field),
            )
        );
    }

    public function testItBuildsUserGroupsListFields(): void
    {
        $user_group_list_field = ListUserGroupBindBuilder::aUserGroupBind(
            ListFieldBuilder::aListField(123)->withLabel('user group list field')->build()
        )->build()->getField();

        $expected_user_group_list_field_with_value = new UserGroupsListFieldWithValue(
            $user_group_list_field->getLabel(),
            DisplayType::BLOCK,
            [
                new UserGroupListValue('NPCs'),
                new UserGroupListValue('MVPs'),
            ]
        );

        $builder = new ListFieldWithValueBuilder(
            BuildUserListFieldWithValueStub::withCallback(static fn () => throw new \Exception('BuildUserListFieldWithValueStub was not expected to be called')),
            BuildStaticListFieldWithValueStub::withCallback(static fn () => throw new \Exception('BuildStaticListFieldWithValueStub was not expected to be called')),
            BuildUserGroupListFieldWithValueStub::withCallback(static function (ConfiguredField $configured_field) use ($expected_user_group_list_field_with_value): UserGroupsListFieldWithValue {
                assert($configured_field->field instanceof \Tracker_FormElement_Field_List);
                assert($configured_field->field->getBind() instanceof \Tracker_FormElement_Field_List_Bind_Ugroups);

                return $expected_user_group_list_field_with_value;
            }),
        );

        self::assertSame(
            $expected_user_group_list_field_with_value,
            $builder->buildListFieldWithValue(
                new ConfiguredField($user_group_list_field, DisplayType::BLOCK),
                $this->buildDummyChangesetForField($user_group_list_field),
            )
        );
    }

    private function buildDummyChangesetForField(\Tracker_FormElement_Field_List $list_field): Tracker_Artifact_ChangesetValue_List
    {
        return ChangesetValueListTestBuilder::aListOfValue(407, ChangesetTestBuilder::aChangeset(102)->build(), $list_field)->build();
    }
}
