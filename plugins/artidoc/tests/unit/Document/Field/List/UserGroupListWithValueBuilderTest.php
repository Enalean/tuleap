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

use Tuleap\Artidoc\Document\Field\ConfiguredField;
use Tuleap\Artidoc\Domain\Document\Section\Field\DisplayType;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserGroupValue;
use Tuleap\Artidoc\Domain\Document\Section\Field\FieldWithValue\UserGroupsListFieldWithValue;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserGroupListWithValueBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private const int TRACKER_ID = 66;
    private \Tuleap\Tracker\Tracker $tracker;
    private \Tracker_Artifact_Changeset $changeset;

    #[\Override]
    protected function setUp(): void
    {
        $project         = ProjectTestBuilder::aProject()->withId(168)->build();
        $this->tracker   = TrackerTestBuilder::aTracker()->withId(self::TRACKER_ID)->withProject($project)->build();
        $artifact        = ArtifactTestBuilder::anArtifact(78)->inTracker($this->tracker)->build();
        $this->changeset = ChangesetTestBuilder::aChangeset(1263)->ofArtifact($artifact)->build();
    }

    public function testItBuildsEmptyValuesWhenNoneIsSelected(): void
    {
        $empty_list_field = ListUserGroupBindBuilder::aUserGroupBind(
            ListFieldBuilder::aListField(843)
                ->withLabel('presearch')
                ->inTracker($this->tracker)
                ->build()
        )->withUserGroups(
            [
                ProjectUGroupTestBuilder::aCustomUserGroup(821)->withName('haematoxylin')->build(),
            ]
        )->build()->getField();

        $this->changeset->setFieldValue(
            $empty_list_field,
            ChangesetValueListTestBuilder::aListOfValue(934, $this->changeset, $empty_list_field)->build()
        );

        self::assertEquals(
            new UserGroupsListFieldWithValue('presearch', DisplayType::BLOCK, []),
            $this->getField(new ConfiguredField($empty_list_field, DisplayType::BLOCK))
        );
    }

    public function testItBuildsEmptyValuesWhenNoChangesetValue(): void
    {
        $empty_list_field = ListUserGroupBindBuilder::aUserGroupBind(
            ListFieldBuilder::aListField(843)
                ->withLabel('presearch')
                ->inTracker($this->tracker)
                ->build()
        )->build()->getField();

        $this->changeset->setFieldValue($empty_list_field, null);

        self::assertEquals(
            new UserGroupsListFieldWithValue('presearch', DisplayType::BLOCK, []),
            $this->getField(new ConfiguredField($empty_list_field, DisplayType::BLOCK))
        );
    }

    public function testItBuildsUserGroupListValues(): void
    {
        $GLOBALS['Language']->method('getText')->willReturn('Project Members');

        $list_value1 = ProjectUGroupTestBuilder::buildProjectMembers();
        $list_value2 = ProjectUGroupTestBuilder::aCustomUserGroup(919)->withName('Reviewers')->build();
        $list_field  = ListUserGroupBindBuilder::aUserGroupBind(
            ListFieldBuilder::aListField(480)
                ->withMultipleValues()
                ->withLabel('trionychoidean')
                ->inTracker($this->tracker)
                ->build()
        )->withUserGroups(
            [
                $list_value1,
                $list_value2,
                ProjectUGroupTestBuilder::aCustomUserGroup(794)->withName('Mentlegen')->build(),
            ]
        )->build()->getField();

        $this->changeset->setFieldValue(
            $list_field,
            ChangesetValueListTestBuilder::aListOfValue(407, $this->changeset, $list_field)
                ->withValues([
                    ListUserGroupValueBuilder::aUserGroupValue($list_value1)->build(),
                    ListUserGroupValueBuilder::aUserGroupValue($list_value2)->build(),
                ])->build()
        );

        self::assertEquals(
            new UserGroupsListFieldWithValue('trionychoidean', DisplayType::COLUMN, [
                new UserGroupValue('Project Members'),
                new UserGroupValue('Reviewers'),
            ]),
            $this->getField(new ConfiguredField($list_field, DisplayType::COLUMN))
        );
    }

    private function getField(ConfiguredField $configured_field): UserGroupsListFieldWithValue
    {
        $changeset_value = $this->changeset->getValue($configured_field->field);
        assert($changeset_value === null || $changeset_value instanceof \Tracker_Artifact_ChangesetValue_List);

        return (new UserGroupListWithValueBuilder())->buildUserGroupsListFieldWithValue($configured_field, $changeset_value);
    }
}
