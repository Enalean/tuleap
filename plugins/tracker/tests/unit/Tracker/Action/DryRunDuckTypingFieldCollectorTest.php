<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use PHPUnit\Framework\MockObject\Stub;
use Tracker_FormElement_Field_Integer;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementStringFieldBuilder;
use Tuleap\Tracker\Test\Stub\VerifyIsOpenListFieldStub;
use Tuleap\Tracker\Test\Stub\VerifyIsPermissionsOnArtifactFieldStub;
use Tuleap\Tracker\Test\Stub\VerifyIsUserGroupListFieldStub;
use Tuleap\Tracker\Test\Stub\VerifyOpenListFieldsAreCompatibleStub;
use Tuleap\Tracker\Test\Stub\VerifyPermissionsCanBeFullyMovedStub;
use Tuleap\Tracker\Test\Stub\VerifyThereArePermissionsToMigrateStub;
use Tuleap\Tracker\Test\Stub\VerifyUserFieldValuesCanBeFullyMovedStub;
use Tuleap\Tracker\Test\Stub\VerifyIsStaticListFieldStub;
use Tuleap\Tracker\Test\Stub\VerifyStaticFieldValuesCanBeFullyMovedStub;
use Tuleap\Tracker\Test\Stub\VerifyListFieldsAreCompatibleStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\Tracker\Test\Stub\VerifyFieldCanBeEasilyMigratedStub;
use Tuleap\Tracker\Test\Stub\VerifyIsUserListFieldStub;
use Tuleap\Tracker\Test\Stub\VerifyUserGroupValuesCanBeFullyMovedStub;

final class DryRunDuckTypingFieldCollectorTest extends TestCase
{
    private const DESTINATION_TRACKER_ID = 105;

    private \Tracker & Stub $source_tracker;
    private \Tracker & Stub $destination_tracker;
    private Artifact $artifact;

    protected function setUp(): void
    {
        $this->source_tracker = $this->createStub(\Tracker::class);

        $this->destination_tracker = $this->createStub(\Tracker::class);
        $this->destination_tracker->method("getId")->willReturn(self::DESTINATION_TRACKER_ID);

        $this->artifact = ArtifactTestBuilder::anArtifact(1)->build();
    }

    public function testFieldWillNotBeMigratedWhenTargetTrackerHasNoFieldWithTheSameName(): void
    {
        $source_string_field        = TrackerFormElementStringFieldBuilder::aStringField(101)->withName("source_string")->build();
        $source_tracker_used_fields = [$source_string_field];

        $destination_tracker_used_fields = [
            TrackerFormElementStringFieldBuilder::aStringField(102)->withName("destination_string")->build(),
            TrackerFormElementStringFieldBuilder::aStringField(103)->withName("another_destination_string")->build(),
        ];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withoutSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withCompleteMove(),
            VerifyIsUserListFieldStub::withoutUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserGroupListFieldStub::withoutUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withoutPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withoutPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withPartialMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertContains($source_string_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->mapping_fields);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testFieldWillNotBeMigratedWhenFieldInTargetTrackerHasNoCompatibleType(): void
    {
        $source_string_field        = TrackerFormElementStringFieldBuilder::aStringField(101)->withName("release_number")->build();
        $source_tracker_used_fields = [$source_string_field];

        $destination_tracker_used_fields = [
            new Tracker_FormElement_Field_Integer(
                102,
                self::DESTINATION_TRACKER_ID,
                null,
                "release_number",
                "Release number",
                null,
                1,
                null,
                null,
                null,
                null,
                null
            ),
        ];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withoutSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withoutCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserListFieldStub::withoutUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserGroupListFieldStub::withoutUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withoutPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withoutPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withPartialMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertContains($source_string_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->mapping_fields);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testFieldWillBeMigratedWhenFieldInTargetTrackerHasTheSameNameAndIsAnEasilyMovableField(): void
    {
        $source_string_field        = TrackerFormElementStringFieldBuilder::aStringField(101)->withName("source_string")->build();
        $source_tracker_used_fields = [$source_string_field];

        $destination_string_field        = TrackerFormElementStringFieldBuilder::aStringField(102)->withName("source_string")->build();
        $destination_tracker_used_fields = [$destination_string_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withoutSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withoutCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserListFieldStub::withoutUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserGroupListFieldStub::withoutUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withoutPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withoutPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withPartialMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertContains($source_string_field, $collection->migrateable_field_list);
        self::assertCount(1, $collection->mapping_fields);
        self::assertEquals($source_string_field, $collection->mapping_fields[0]->source);
        self::assertEquals($destination_string_field, $collection->mapping_fields[0]->destination);
    }

    public function testStaticListFieldWillBeMigratedWhenMatchingDestinationFieldContainsAllTheSelectedValues(): void
    {
        $source_list_field          = TrackerFormElementListFieldBuilder::aListField(101)->withName("field_name")->build();
        $source_tracker_used_fields = [$source_list_field];

        $destination_list_field          = TrackerFormElementListFieldBuilder::aListField(102)->withName("field_name")->build();
        $destination_tracker_used_fields = [$destination_list_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withCompleteMove(),
            VerifyIsUserListFieldStub::withoutUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserGroupListFieldStub::withoutUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withoutPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withoutPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withPartialMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertContains($source_list_field, $collection->migrateable_field_list);
        self::assertCount(1, $collection->mapping_fields);
        self::assertEquals($source_list_field, $collection->mapping_fields[0]->source);
        self::assertEquals($destination_list_field, $collection->mapping_fields[0]->destination);
    }

    public function testFieldWillNotBeMigratedWhenWhenListFieldsAreNotCompatible(): void
    {
        $source_list_field          = TrackerFormElementListFieldBuilder::aListField(101)->withName("field_name")->build();
        $source_tracker_used_fields = [$source_list_field];

        $destination_list_field          = TrackerFormElementListFieldBuilder::aListField(102)->withName("field_name")->build();
        $destination_tracker_used_fields = [$destination_list_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withoutCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withCompleteMove(),
            VerifyIsUserListFieldStub::withUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserGroupListFieldStub::withoutUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withoutPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withoutPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withPartialMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertContains($source_list_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertEmpty($collection->mapping_fields);
    }

    public function testFieldWillBeNotMigratedWhenWhenListFieldsAllowMigrationButFieldCanNotBeMoved(): void
    {
        $source_list_field          = TrackerFormElementListFieldBuilder::aListField(101)->withName("field_name")->build();
        $source_tracker_used_fields = [$source_list_field];

        $destination_list_field          = TrackerFormElementListFieldBuilder::aListField(102)->withName("field_name")->build();
        $destination_tracker_used_fields = [$destination_list_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withoutCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserListFieldStub::withoutUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserGroupListFieldStub::withoutUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withoutPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withoutPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withPartialMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertContains($source_list_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertEmpty($collection->mapping_fields);
    }

    public function testStaticFieldCanBePartiallyMoved(): void
    {
        $source_list_field          = TrackerFormElementListFieldBuilder::aListField(101)->withName("source_string")->build();
        $source_tracker_used_fields = [$source_list_field];

        $destination_list_field          = TrackerFormElementListFieldBuilder::aListField(102)->withName("source_string")->build();
        $destination_tracker_used_fields = [$destination_list_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserListFieldStub::withoutUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserGroupListFieldStub::withoutUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withoutPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withoutPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withPartialMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertContains($source_list_field, $collection->partially_migrated_fields);
        self::assertSame($source_list_field, $collection->mapping_fields[0]->source);
        self::assertSame($destination_list_field, $collection->mapping_fields[0]->destination);
    }

    public function testUserBoundFieldWillBeNotMigratedWhenTheDestinationFieldIsNotBoundToUsers(): void
    {
        $source_list_field          = TrackerFormElementListFieldBuilder::aListField(101)->withName("assigned_to")->build();
        $source_tracker_used_fields = [$source_list_field];

        $destination_list_field          = TrackerFormElementListFieldBuilder::aListField(102)->withName("assigned_to")->build();
        $destination_tracker_used_fields = [$destination_list_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withoutSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withoutCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserListFieldStub::withUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserGroupListFieldStub::withoutUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withoutPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withoutPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withPartialMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertContains($source_list_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertEmpty($collection->mapping_fields);
    }

    public function testUsersBoundFieldCanBePartiallyMovedWhenTheDestinationFieldDoesNotContainAllTheSelectedUsers(): void
    {
        $source_list_field          = TrackerFormElementListFieldBuilder::aListField(101)->withName("assigned_to")->build();
        $source_tracker_used_fields = [$source_list_field];

        $destination_list_field          = TrackerFormElementListFieldBuilder::aListField(102)->withName("assigned_to")->build();
        $destination_tracker_used_fields = [$destination_list_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withoutSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserListFieldStub::withUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserGroupListFieldStub::withoutUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withoutPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withoutPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withPartialMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertContains($source_list_field, $collection->partially_migrated_fields);
        self::assertSame($source_list_field, $collection->mapping_fields[0]->source);
        self::assertSame($destination_list_field, $collection->mapping_fields[0]->destination);
    }

    public function testUserBoundListFieldWillBeMigratedWhenMatchingDestinationFieldContainsAllTheSelectedUsers(): void
    {
        $source_list_field          = TrackerFormElementListFieldBuilder::aListField(101)->withName("assigned_to")->build();
        $source_tracker_used_fields = [$source_list_field];

        $destination_list_field          = TrackerFormElementListFieldBuilder::aListField(102)->withName("assigned_to")->build();
        $destination_tracker_used_fields = [$destination_list_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withoutSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserListFieldStub::withUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withCompleteMove(),
            VerifyIsUserGroupListFieldStub::withoutUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withoutPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withoutPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withPartialMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertContains($source_list_field, $collection->migrateable_field_list);
        self::assertSame($source_list_field, $collection->mapping_fields[0]->source);
        self::assertSame($destination_list_field, $collection->mapping_fields[0]->destination);
    }

    public function testUserGroupBoundFieldWillBeNotMigratedWhenTheDestinationFieldIsNotBoundToUserGroups(): void
    {
        $source_list_field          = TrackerFormElementListFieldBuilder::aListField(101)->withName("cc")->build();
        $source_tracker_used_fields = [$source_list_field];

        $destination_list_field          = TrackerFormElementListFieldBuilder::aListField(102)->withName("cc")->build();
        $destination_tracker_used_fields = [$destination_list_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withoutSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withoutCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserListFieldStub::withUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserGroupListFieldStub::withoutUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withoutPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withoutPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withPartialMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertContains($source_list_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertEmpty($collection->mapping_fields);
    }

    public function testUserGroupsBoundFieldCanBePartiallyMovedWhenTheDestinationFieldDoesNotContainAllTheSelectedUserGroups(): void
    {
        $source_list_field          = TrackerFormElementListFieldBuilder::aListField(101)->withName("cc")->build();
        $source_tracker_used_fields = [$source_list_field];

        $destination_list_field          = TrackerFormElementListFieldBuilder::aListField(102)->withName("cc")->build();
        $destination_tracker_used_fields = [$destination_list_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withoutSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserListFieldStub::withoutUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserGroupListFieldStub::withUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withoutPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withoutPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withPartialMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertContains($source_list_field, $collection->partially_migrated_fields);
        self::assertSame($source_list_field, $collection->mapping_fields[0]->source);
        self::assertSame($destination_list_field, $collection->mapping_fields[0]->destination);
    }

    public function testUserGroupsBoundListFieldWillBeMigratedWhenMatchingDestinationFieldContainsAllTheSelectedUserGroups(): void
    {
        $source_list_field          = TrackerFormElementListFieldBuilder::aListField(101)->withName("cc")->build();
        $source_tracker_used_fields = [$source_list_field];

        $destination_list_field          = TrackerFormElementListFieldBuilder::aListField(102)->withName("cc")->build();
        $destination_tracker_used_fields = [$destination_list_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withoutSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserListFieldStub::withoutUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withCompleteMove(),
            VerifyIsUserGroupListFieldStub::withUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withCompleteMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withoutPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withoutPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withPartialMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertContains($source_list_field, $collection->migrateable_field_list);
        self::assertSame($source_list_field, $collection->mapping_fields[0]->source);
        self::assertSame($destination_list_field, $collection->mapping_fields[0]->destination);
    }

    public function testPermissionsFieldWillNotBeMigratedDestinationFieldIsNotCompatible(): void
    {
        $source_permissions_field = $this->createStub(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $source_permissions_field->method("getName")->willReturn("permissions");

        $source_tracker_used_fields = [$source_permissions_field];

        $destination_permissions_field = TrackerFormElementListFieldBuilder::aListField(1)->withName("permissions")->build();

        $destination_tracker_used_fields = [$destination_permissions_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withoutSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withoutCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserListFieldStub::withoutUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserGroupListFieldStub::withoutUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withoutPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withoutPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withPartialMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertContains($source_permissions_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testPermissionsFieldWillNotBeMigratedIfThereIsNoPermissionToMigrate(): void
    {
        $source_permissions_field = $this->createStub(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $source_permissions_field->method("getName")->willReturn("permissions");

        $source_tracker_used_fields = [$source_permissions_field];

        $destination_permissions_field = $this->createStub(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $destination_permissions_field->method("getName")->willReturn("permissions");

        $destination_tracker_used_fields = [$destination_permissions_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withoutSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withoutCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserListFieldStub::withoutUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserGroupListFieldStub::withoutUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withoutPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withPartialMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertContains($source_permissions_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testPermissionsFieldWillBePartiallyMigratedWhenDestinationFieldDoesNotContainAllSourceValues(): void
    {
        $source_permissions_field = $this->createStub(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $source_permissions_field->method("getName")->willReturn("permissions");

        $source_tracker_used_fields = [$source_permissions_field];

        $destination_permissions_field = $this->createStub(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $destination_permissions_field->method("getName")->willReturn("permissions");

        $destination_tracker_used_fields = [$destination_permissions_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withoutSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withoutCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserListFieldStub::withoutUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserGroupListFieldStub::withoutUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withPartialMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertContains($source_permissions_field, $collection->partially_migrated_fields);
        self::assertSame($source_permissions_field, $collection->mapping_fields[0]->source);
        self::assertSame($destination_permissions_field, $collection->mapping_fields[0]->destination);
        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
    }

    public function testPermissionsFieldWillBeFullyMigratedWhenAllValuesAreAvailableInDestinationField(): void
    {
        $source_permissions_field = $this->createStub(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $source_permissions_field->method("getName")->willReturn("permissions");

        $source_tracker_used_fields = [$source_permissions_field];

        $destination_permissions_field = $this->createStub(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $destination_permissions_field->method("getName")->willReturn("permissions");

        $destination_tracker_used_fields = [$destination_permissions_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withoutSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withoutCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserListFieldStub::withoutUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserGroupListFieldStub::withoutUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withCompleteMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertContains($source_permissions_field, $collection->migrateable_field_list);
        self::assertSame($source_permissions_field, $collection->mapping_fields[0]->source);
        self::assertSame($destination_permissions_field, $collection->mapping_fields[0]->destination);
        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testOpenListFieldWillNotBeMigratedDestinationFieldIsNotCompatible(): void
    {
        $source_permissions_field = $this->createStub(\Tracker_FormElement_Field_OpenList::class);
        $source_permissions_field->method("getName")->willReturn("open_list");

        $source_tracker_used_fields = [$source_permissions_field];

        $destination_permissions_field = TrackerFormElementListFieldBuilder::aListField(1)->withName("permissions")->build();

        $destination_tracker_used_fields = [$destination_permissions_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withoutSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withoutCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserListFieldStub::withoutUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserGroupListFieldStub::withoutUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withoutPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withoutPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withPartialMove(),
            VerifyIsOpenListFieldStub::withoutOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertContains($source_permissions_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testOpenListCanBeMigrated(): void
    {
        $source_permissions_field = $this->createStub(\Tracker_FormElement_Field_OpenList::class);
        $source_permissions_field->method("getName")->willReturn("open_list");

        $source_tracker_used_fields = [$source_permissions_field];

        $destination_permissions_field = $this->createStub(\Tracker_FormElement_Field_OpenList::class);
        $destination_permissions_field->method("getName")->willReturn("open_list");

        $destination_tracker_used_fields = [$destination_permissions_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$destination_tracker_used_fields),
            VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields(),
            VerifyIsStaticListFieldStub::withoutSingleStaticListField(),
            VerifyListFieldsAreCompatibleStub::withoutCompatibleFields(),
            VerifyStaticFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserListFieldStub::withoutUserListField(),
            VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsUserGroupListFieldStub::withoutUserGroupListField(),
            VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove(),
            VerifyIsPermissionsOnArtifactFieldStub::withPermissionsOnArtifactField(),
            VerifyThereArePermissionsToMigrateStub::withPermissionsToMigrate(),
            VerifyPermissionsCanBeFullyMovedStub::withCompleteMove(),
            VerifyIsOpenListFieldStub::withOpenListField(),
            VerifyOpenListFieldsAreCompatibleStub::withCompatibleFields()
        );

        $collection = $collector->collect($this->source_tracker, $this->destination_tracker, $this->artifact);

        self::assertContains($source_permissions_field, $collection->migrateable_field_list);
        self::assertSame($source_permissions_field, $collection->mapping_fields[0]->source);
        self::assertSame($destination_permissions_field, $collection->mapping_fields[0]->destination);
        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
    }
}
