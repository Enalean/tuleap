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

namespace unit\Artidoc\Document\Field;

use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Document\Field\UserListFieldWithValueBuilder;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserListValue;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideDefaultUserAvatarUrlStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueOpenListBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\OpenListValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserListFieldWithValueBuilderTest extends TestCase
{
    private const TRACKER_ID = 65453;
    private Tracker $tracker;
    private \Tracker_Artifact_Changeset $changeset;

    protected function setUp(): void
    {
        $project         = ProjectTestBuilder::aProject()->withId(168)->build();
        $this->tracker   = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->withProject($project)->build();
        $artifact        = ArtifactTestBuilder::anArtifact(78)->inTracker($this->tracker)->build();
        $this->changeset = ChangesetTestBuilder::aChangeset(1263)->ofArtifact($artifact)->build();
    }

    public function testItBuildsUserListFieldWithValues(): void
    {
        $bob   = UserTestBuilder::aUser()->withUserName('Bob')->withId(102)->build();
        $alice = UserTestBuilder::aUser()->withUserName('Alice')->withId(103)->build();

        $user_list_field = ListUserBindBuilder::aUserBind(
            ListFieldBuilder::aListField(123)->inTracker($this->tracker)->withLabel('user list field')->build(),
        )->withUsers([$bob, $alice])
            ->build()
            ->getField();

        $builder = new UserListFieldWithValueBuilder(
            RetrieveUserByIdStub::withUsers($bob, $alice),
            ProvideUserAvatarUrlStub::build(),
            ProvideDefaultUserAvatarUrlStub::build(),
        );

        $this->changeset->setFieldValue(
            $user_list_field,
            ChangesetValueListTestBuilder::aListOfValue(407, $this->changeset, $user_list_field)->withValues([
                ListUserValueBuilder::aUserWithId((int) $bob->getId())->withDisplayedName($bob->getUserName())->build(),
                ListUserValueBuilder::aUserWithId((int) $alice->getId())->withDisplayedName($alice->getUserName())->build(),
            ])->build()
        );

        $configured_field =  new ConfiguredField(
            $user_list_field,
            DisplayType::BLOCK,
        );

        $changeset_value = $this->changeset->getValue($user_list_field);
        assert($changeset_value instanceof \Tracker_Artifact_ChangesetValue_List);

        self::assertEquals(
            new UserListFieldWithValue(
                $user_list_field->getLabel(),
                DisplayType::BLOCK,
                [
                    new UserListValue($bob->getUserName(), 'avatar.png'),
                    new UserListValue($alice->getUserName(), 'avatar.png'),
                ]
            ),
            $builder->buildUserListFieldWithValue($configured_field, $changeset_value)
        );
    }

    public function testItReturnsEmptyValuesWhenNoneValueIsSelected(): void
    {
        $user_list_field = ListUserBindBuilder::aUserBind(
            ListFieldBuilder::aListField(123)->inTracker($this->tracker)->withLabel('Empty user list field')->build(),
        )->build()->getField();

        $builder = new UserListFieldWithValueBuilder(
            RetrieveUserByIdStub::withNoUser(),
            ProvideUserAvatarUrlStub::build(),
            ProvideDefaultUserAvatarUrlStub::build(),
        );

        $this->changeset->setFieldValue(
            $user_list_field,
            ChangesetValueListTestBuilder::aListOfValue(407, $this->changeset, $user_list_field)->withValues([
                ListUserValueBuilder::noneUser()->build(),
            ])->build()
        );

        $configured_field =  new ConfiguredField(
            $user_list_field,
            DisplayType::BLOCK,
        );

        $changeset_value = $this->changeset->getValue($user_list_field);
        assert($changeset_value instanceof \Tracker_Artifact_ChangesetValue_List);

        self::assertEquals(
            new UserListFieldWithValue($user_list_field->getLabel(), DisplayType::BLOCK, []),
            $builder->buildUserListFieldWithValue($configured_field, $changeset_value)
        );
    }

    public function testUnknownUsersHaveDefaultAvatarsForOpenListValues(): void
    {
        $bob = UserTestBuilder::aUser()->withUserName('Bob')->withId(102)->build();

        $user_list_field = ListUserBindBuilder::aUserBind(
            ListFieldBuilder::aListField(123)->inTracker($this->tracker)->withLabel('user list field')->build(),
        )->withUsers([$bob])
            ->build()
            ->getField();

        $builder = new UserListFieldWithValueBuilder(
            RetrieveUserByIdStub::withUsers($bob),
            ProvideUserAvatarUrlStub::build(),
            ProvideDefaultUserAvatarUrlStub::build(),
        );

        $this->changeset->setFieldValue(
            $user_list_field,
            ChangesetValueOpenListBuilder::aListOfValue(407, $this->changeset, $user_list_field)->withValues([
                OpenListValueBuilder::anOpenListValue('alice@example.com')->withId(4531)->build(),
            ])->build()
        );

        $configured_field =  new ConfiguredField(
            $user_list_field,
            DisplayType::BLOCK,
        );

        $changeset_value = $this->changeset->getValue($user_list_field);
        assert($changeset_value instanceof \Tracker_Artifact_ChangesetValue_List);

        self::assertEquals(
            new UserListFieldWithValue(
                $user_list_field->getLabel(),
                DisplayType::BLOCK,
                [
                    new UserListValue('alice@example.com', 'default_avatar.png'),
                ]
            ),
            $builder->buildUserListFieldWithValue($configured_field, $changeset_value)
        );
    }
}
