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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StaticListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StaticListValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserGroupValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserGroupsListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserValue;
use Tuleap\Color\ColorName;
use Tuleap\Option\Option;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideDefaultUserAvatarUrlStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\StaticBindDecoratorBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class ListFieldWithValueBuilderTest extends TestCase
{
    private function getBuilder(): ListFieldWithValueBuilder
    {
        return new ListFieldWithValueBuilder(
            new UserListFieldWithValueBuilder(
                RetrieveUserByIdStub::withUsers(
                    UserTestBuilder::anActiveUser()
                        ->withId(121)
                        ->withRealName('Bob')
                        ->withUserName('bob')
                        ->build(),
                    UserTestBuilder::anActiveUser()
                        ->withId(122)
                        ->withRealName('Alice')
                        ->withUserName('alice')
                        ->build(),
                ),
                ProvideUserAvatarUrlStub::build(),
                ProvideDefaultUserAvatarUrlStub::build(),
            ),
            new StaticListFieldWithValueBuilder(),
            new UserGroupListWithValueBuilder(),
        );
    }

    public function testItBuildsUserListFields(): void
    {
        $user_list_field = ListUserBindBuilder::aUserBind(
            SelectboxFieldBuilder::aSelectboxField(123)->withLabel('user list field')->build()
        )->build()->getField();

        $expected_list_field_with_value = new UserListFieldWithValue(
            $user_list_field->getLabel(),
            DisplayType::BLOCK,
            [
                new UserValue('Bob', 'avatar.png'),
                new UserValue('Alice', 'avatar.png'),
            ]
        );

        $changeset_value = ChangesetValueListTestBuilder::aListOfValue(407, ChangesetTestBuilder::aChangeset(102)->build(), $user_list_field)
            ->withValues([
                ListUserValueBuilder::aUserWithId(121)->withDisplayedName('Bob')->build(),
                ListUserValueBuilder::aUserWithId(122)->withDisplayedName('Alice')->build(),
            ])
            ->build();

        self::assertEquals(
            $expected_list_field_with_value,
            $this->getBuilder()->buildListFieldWithValue(
                new ConfiguredField($user_list_field, DisplayType::BLOCK),
                $changeset_value,
            )
        );
    }

    public function testItBuildsStaticListFields(): void
    {
        $red_value         = ListStaticValueBuilder::aStaticValue('Red')->withId(101)->build();
        $blue_value        = ListStaticValueBuilder::aStaticValue('Blue')->withId(102)->build();
        $static_list_field = ListStaticBindBuilder::aStaticBind(
            SelectboxFieldBuilder::aSelectboxField(123)->withLabel('static list field')->build()
        )
            ->withBuildStaticValues([$red_value, $blue_value])
            ->withDecorators([
                StaticBindDecoratorBuilder::withColor(ColorName::FIESTA_RED)->withValueId($red_value->getId())->build(),
                StaticBindDecoratorBuilder::withColor(ColorName::DEEP_BLUE)->withValueId($blue_value->getId())->build(),
            ])->build()->getField();

        $expected_static_list_field_with_value = new StaticListFieldWithValue(
            $static_list_field->getLabel(),
            DisplayType::BLOCK,
            [
                new StaticListValue('Red', Option::fromValue(ColorName::FIESTA_RED)),
                new StaticListValue('Blue', Option::fromValue(ColorName::DEEP_BLUE)),
            ]
        );

        $changeset_value = ChangesetValueListTestBuilder::aListOfValue(407, ChangesetTestBuilder::aChangeset(102)->build(), $static_list_field)
            ->withValues([
                $red_value,
                $blue_value,
            ])
            ->build();

        self::assertEquals(
            $expected_static_list_field_with_value,
            $this->getBuilder()->buildListFieldWithValue(
                new ConfiguredField($static_list_field, DisplayType::BLOCK),
                $changeset_value,
            )
        );
    }

    public function testItBuildsUserGroupsListFields(): void
    {
        $user_group_list_field = ListUserGroupBindBuilder::aUserGroupBind(
            SelectboxFieldBuilder::aSelectboxField(123)->withLabel('user group list field')->build()
        )->build()->getField();

        $expected_user_group_list_field_with_value = new UserGroupsListFieldWithValue(
            $user_group_list_field->getLabel(),
            DisplayType::BLOCK,
            [
                new UserGroupValue('NPCs'),
                new UserGroupValue('MVPs'),
            ]
        );

        $changeset_value = ChangesetValueListTestBuilder::aListOfValue(407, ChangesetTestBuilder::aChangeset(102)->build(), $user_group_list_field)
            ->withValues([
                ListUserGroupValueBuilder::aUserGroupValue(ProjectUGroupTestBuilder::aCustomUserGroup(919)->withName('NPCs')->build())->build(),
                ListUserGroupValueBuilder::aUserGroupValue(ProjectUGroupTestBuilder::aCustomUserGroup(920)->withName('MVPs')->build())->build(),
            ])
            ->build();

        self::assertEquals(
            $expected_user_group_list_field_with_value,
            $this->getBuilder()->buildListFieldWithValue(
                new ConfiguredField($user_group_list_field, DisplayType::BLOCK),
                $changeset_value,
            )
        );
    }
}
