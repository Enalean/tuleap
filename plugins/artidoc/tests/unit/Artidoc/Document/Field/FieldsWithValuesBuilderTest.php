<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Document\Field;

use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StaticListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StaticListValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StringFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserGroupListValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserGroupsListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserListFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserListValue;
use Tuleap\Artidoc\Stubs\Document\Field\List\BuildListFieldWithValueStub;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueStringTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldsWithValuesBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private const TRACKER_ID = 66;
    private \Tuleap\Tracker\Tracker $tracker;
    private ConfiguredFieldCollection $field_collection;
    private \Tracker_Artifact_Changeset $changeset;

    protected function setUp(): void
    {
        $project                = ProjectTestBuilder::aProject()->withId(168)->build();
        $this->tracker          = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->withProject($project)->build();
        $artifact               = ArtifactTestBuilder::anArtifact(78)->inTracker($this->tracker)->build();
        $this->changeset        = ChangesetTestBuilder::aChangeset(1263)->ofArtifact($artifact)->build();
        $this->field_collection = new ConfiguredFieldCollection([]);
    }

    /**
     * @return list<StringFieldWithValue | UserGroupsListFieldWithValue | StaticListFieldWithValue | UserListFieldWithValue>
     */
    private function getFields(): array
    {
        $builder = new FieldsWithValuesBuilder(
            $this->field_collection,
            BuildListFieldWithValueStub::withCallback(
                static function () {
                    throw new \Exception('This test was not supposed to build list fields.');
                },
            ),
        );
        return $builder->getFieldsWithValues($this->changeset);
    }

    public function testItReturnsEmpty(): void
    {
        $this->field_collection = new ConfiguredFieldCollection([]);

        self::assertSame([], $this->getFields());
    }

    public function testItBuildsStringFields(): void
    {
        $first_string_field     = StringFieldBuilder::aStringField(268)
            ->withLabel('naphthalol')
            ->inTracker($this->tracker)
            ->build();
        $second_string_field    = StringFieldBuilder::aStringField(255)
            ->withLabel('dictator')
            ->inTracker($this->tracker)
            ->build();
        $this->field_collection = new ConfiguredFieldCollection([
            self::TRACKER_ID => [
                new ConfiguredField($first_string_field, DisplayType::COLUMN),
                new ConfiguredField($second_string_field, DisplayType::BLOCK),
            ],
        ]);

        $this->changeset->setFieldValue(
            $first_string_field,
            ChangesetValueStringTestBuilder::aValue(948, $this->changeset, $first_string_field)
                ->withValue('pleurogenic')
                ->build()
        );
        $this->changeset->setFieldValue(
            $second_string_field,
            ChangesetValueStringTestBuilder::aValue(364, $this->changeset, $second_string_field)
                ->withValue('proficiently')
                ->build()
        );

        self::assertEquals([
            new StringFieldWithValue('naphthalol', DisplayType::COLUMN, 'pleurogenic'),
            new StringFieldWithValue('dictator', DisplayType::BLOCK, 'proficiently'),
        ], $this->getFields());
    }

    public function testItSkipsMissingChangesetValues(): void
    {
        $first_string_field     = StringFieldBuilder::aStringField(268)
            ->withLabel('slickenside')
            ->inTracker($this->tracker)
            ->build();
        $second_string_field    = StringFieldBuilder::aStringField(255)
            ->withLabel('roughwork')
            ->inTracker($this->tracker)
            ->build();
        $this->field_collection = new ConfiguredFieldCollection([
            self::TRACKER_ID => [
                new ConfiguredField($first_string_field, DisplayType::COLUMN),
                new ConfiguredField($second_string_field, DisplayType::BLOCK),
            ],
        ]);

        $this->changeset->setNoFieldValue($first_string_field);
        $this->changeset->setFieldValue(
            $second_string_field,
            ChangesetValueStringTestBuilder::aValue(364, $this->changeset, $second_string_field)
                ->withValue('Scripture')
                ->build()
        );
        self::assertEquals([
            new StringFieldWithValue('roughwork', DisplayType::BLOCK, 'Scripture'),
        ], $this->getFields());
    }

    public function testItBuildsUserGroupListFieldsWithValues(): void
    {
        $user_group_list_value1 = ProjectUGroupTestBuilder::buildProjectMembers();
        $user_group_list_value2 = ProjectUGroupTestBuilder::aCustomUserGroup(919)->withName('Reviewers')->build();
        $user_group_list_field  = ListUserGroupBindBuilder::aUserGroupBind(
            ListFieldBuilder::aListField(480)
                ->withMultipleValues()
                ->withLabel('trionychoidean')
                ->inTracker($this->tracker)
                ->build()
        )->withUserGroups(
            [
                $user_group_list_value1,
                $user_group_list_value2,
                ProjectUGroupTestBuilder::aCustomUserGroup(794)->withName('Mentlegen')->build(),
            ]
        )->build()->getField();

        $this->field_collection = new ConfiguredFieldCollection([
            self::TRACKER_ID => [
                new ConfiguredField($user_group_list_field, DisplayType::COLUMN),
            ],
        ]);

        $this->changeset->setFieldValue(
            $user_group_list_field,
            ChangesetValueListTestBuilder::aListOfValue(934, $this->changeset, $user_group_list_field)->build()
        );

        $expected_field_with_value = new UserGroupsListFieldWithValue('trionychoidean', DisplayType::COLUMN, [
            new UserGroupListValue('Project Members'),
            new UserGroupListValue('Reviewers'),
        ]);

        $builder = new FieldsWithValuesBuilder(
            $this->field_collection,
            BuildListFieldWithValueStub::withCallback(
                static function (ConfiguredField $configured_field) use ($expected_field_with_value): UserGroupsListFieldWithValue {
                    assert($configured_field->field instanceof \Tracker_FormElement_Field_List);
                    assert($configured_field->field->getBind() instanceof \Tracker_FormElement_Field_List_Bind_Ugroups);

                    return $expected_field_with_value;
                },
            ),
        );

        self::assertEquals([$expected_field_with_value], $builder->getFieldsWithValues($this->changeset));
    }

    public function testItBuildsStaticListFieldWithValues(): void
    {
        $static_list_field = ListStaticBindBuilder::aStaticBind(
            ListFieldBuilder::aListField(123)->inTracker($this->tracker)->withLabel('static list field')->build(),
        )->withBuildStaticValues([
            ListStaticValueBuilder::aStaticValue('Something')->build(),
        ])->build()->getField();

        $this->field_collection = new ConfiguredFieldCollection([
            self::TRACKER_ID => [
                new ConfiguredField($static_list_field, DisplayType::BLOCK),
            ],
        ]);

        $this->changeset->setFieldValue(
            $static_list_field,
            ChangesetValueListTestBuilder::aListOfValue(934, $this->changeset, $static_list_field)->build()
        );

        $expected_field_with_value = new StaticListFieldWithValue('static list field', DisplayType::BLOCK, [
            new StaticListValue('Something', null),
        ]);

        $builder = new FieldsWithValuesBuilder(
            $this->field_collection,
            BuildListFieldWithValueStub::withCallback(
                static function (ConfiguredField $configured_field) use ($expected_field_with_value): StaticListFieldWithValue {
                    assert($configured_field->field instanceof \Tracker_FormElement_Field_List);
                    assert($configured_field->field->getBind() instanceof \Tracker_FormElement_Field_List_Bind_Static);

                    return $expected_field_with_value;
                },
            ),
        );

        self::assertEquals([$expected_field_with_value], $builder->getFieldsWithValues($this->changeset));
    }

    public function testItBuildsUserListFieldsWithValue(): void
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

        $this->changeset->setFieldValue(
            $user_list_field,
            ChangesetValueListTestBuilder::aListOfValue(407, $this->changeset, $user_list_field)
                ->withValues([
                    ListUserValueBuilder::aUserWithId(102)->withDisplayedName('Bob')->build(),
                    ListUserValueBuilder::aUserWithId(103)->withDisplayedName('Alice')->build(),
                ])->build(),
        );

        $builder = new FieldsWithValuesBuilder(
            new ConfiguredFieldCollection([
                self::TRACKER_ID => [
                    new ConfiguredField($user_list_field, DisplayType::BLOCK),
                ],
            ]),
            BuildListFieldWithValueStub::withCallback(
                static function (ConfiguredField $configured_field) use ($expected_list_field_with_value): UserListFieldWithValue {
                    assert($configured_field->field instanceof \Tracker_FormElement_Field_List);
                    assert($configured_field->field->getBind() instanceof \Tracker_FormElement_Field_List_Bind_Users);

                    return $expected_list_field_with_value;
                }
            ),
        );

        self::assertEquals([$expected_list_field_with_value], $builder->getFieldsWithValues($this->changeset));
    }
}
