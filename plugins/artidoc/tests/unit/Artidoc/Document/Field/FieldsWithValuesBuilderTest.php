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
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\StringFieldWithValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserGroupListValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserGroupsListFieldWithValue;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueStringTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldsWithValuesBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private const TRACKER_ID = 66;
    private \Tracker $tracker;
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
     * @return list<StringFieldWithValue | UserGroupsListFieldWithValue>
     */
    private function getFields(): array
    {
        $builder = new FieldsWithValuesBuilder($this->field_collection);
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

    public function testItBuildsSupportedFieldsWithValues(): void
    {
        $GLOBALS['Language']->method('getText')->willReturn('Project Members');
        $first_list_field = ListUserGroupBindBuilder::aUserGroupBind(
            ListFieldBuilder::aListField(843)
                ->withLabel('presearch')
                ->inTracker($this->tracker)
                ->build()
        )->withUserGroups(
            [
                ProjectUGroupTestBuilder::aCustomUserGroup(821)->withName('haematoxylin')->build(),
            ]
        )->build()->getField();

        $second_list_value1     = ProjectUGroupTestBuilder::buildProjectMembers();
        $second_list_value2     = ProjectUGroupTestBuilder::aCustomUserGroup(919)->withName('Reviewers')->build();
        $second_list_field      = ListUserGroupBindBuilder::aUserGroupBind(
            ListFieldBuilder::aListField(480)
                ->withMultipleValues()
                ->withLabel('trionychoidean')
                ->inTracker($this->tracker)
                ->build()
        )->withUserGroups(
            [
                $second_list_value1,
                $second_list_value2,
                ProjectUGroupTestBuilder::aCustomUserGroup(794)->withName('Mentlegen')->build(),
            ]
        )->build()->getField();
        $this->field_collection = new ConfiguredFieldCollection([
            self::TRACKER_ID => [
                new ConfiguredField($first_list_field, DisplayType::BLOCK),
                new ConfiguredField($second_list_field, DisplayType::COLUMN),
            ],
        ]);

        $this->changeset->setFieldValue(
            $first_list_field,
            ChangesetValueListTestBuilder::aListOfValue(934, $this->changeset, $first_list_field)->build()
        );
        $this->changeset->setFieldValue(
            $second_list_field,
            ChangesetValueListTestBuilder::aListOfValue(407, $this->changeset, $second_list_field)
                ->withValues([
                    ListUserGroupValueBuilder::aUserGroupValue($second_list_value1)->build(),
                    ListUserGroupValueBuilder::aUserGroupValue($second_list_value2)->build(),
                ])->build()
        );

        self::assertEquals([
            new UserGroupsListFieldWithValue('presearch', DisplayType::BLOCK, []),
            new UserGroupsListFieldWithValue('trionychoidean', DisplayType::COLUMN, [
                new UserGroupListValue('Project Members'),
                new UserGroupListValue('Reviewers'),
            ]),
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
}
