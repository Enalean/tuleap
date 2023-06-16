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
use Tuleap\Tracker\Test\Stub\CheckIsSingleStaticListFieldStub;
use Tuleap\Tracker\Test\Stub\CheckStaticFieldCanBeFullyMovedStub;
use Tuleap\Tracker\Test\Stub\CheckStaticListFieldsValueIsMovableStub;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\Tracker\Test\Stub\CheckFieldTypeCompatibilityStub;

final class DryRunDuckTypingFieldCollectorTest extends TestCase
{
    private const TARGET_TRACKER_ID = 105;

    private \Tracker & Stub $source_tracker;
    private \Tracker & Stub $target_tracker;
    private Artifact $artifact;

    protected function setUp(): void
    {
        $this->source_tracker = $this->createStub(\Tracker::class);

        $this->target_tracker = $this->createStub(\Tracker::class);
        $this->target_tracker->method("getId")->willReturn(self::TARGET_TRACKER_ID);

        $this->artifact = ArtifactTestBuilder::anArtifact(1)->build();
    }

    public function testFieldWillNotBeMigratedWhenTargetTrackerHasNoFieldWithTheSameName(): void
    {
        $source_string_field        = TrackerFormElementStringFieldBuilder::aStringField(101)->withName("source_string")->build();
        $source_tracker_used_fields = [$source_string_field];

        $target_tracker_used_fields = [
            TrackerFormElementStringFieldBuilder::aStringField(102)->withName("target_string")->build(),
            TrackerFormElementStringFieldBuilder::aStringField(103)->withName("another_target_string")->build(),
        ];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$target_tracker_used_fields),
            CheckStaticListFieldsValueIsMovableStub::withMovableStaticValue(),
            CheckIsSingleStaticListFieldStub::withSingleStaticListField(),
            CheckFieldTypeCompatibilityStub::withCompatibleTypes(),
            CheckStaticFieldCanBeFullyMovedStub::withMovableField()
        );

        $collection = $collector->collect($this->source_tracker, $this->target_tracker, $this->artifact);

        self::assertContains($source_string_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->mapping_fields);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testFieldWillNotBeMigratedWhenFieldInTargetTrackerHasNoCompatibleType(): void
    {
        $source_string_field        = TrackerFormElementStringFieldBuilder::aStringField(101)->withName("release_number")->build();
        $source_tracker_used_fields = [$source_string_field];

        $target_tracker_used_fields = [
            new Tracker_FormElement_Field_Integer(
                102,
                self::TARGET_TRACKER_ID,
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
            RetrieveUsedFieldsStub::withFields(...$target_tracker_used_fields),
            CheckStaticListFieldsValueIsMovableStub::withMovableStaticValue(),
            CheckIsSingleStaticListFieldStub::withSingleStaticListField(),
            CheckFieldTypeCompatibilityStub::withoutCompatibleTypes(),
            CheckStaticFieldCanBeFullyMovedStub::withMovableField()
        );

        $collection = $collector->collect($this->source_tracker, $this->target_tracker, $this->artifact);

        self::assertContains($source_string_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->mapping_fields);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testFieldWillBeMigratedWhenFieldInTargetTrackerHasTheSameNameAndACompatibleType(): void
    {
        $source_string_field        = TrackerFormElementStringFieldBuilder::aStringField(101)->withName("source_string")->build();
        $source_tracker_used_fields = [$source_string_field];

        $target_string_field        = TrackerFormElementStringFieldBuilder::aStringField(102)->withName("source_string")->build();
        $target_tracker_used_fields = [$target_string_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$target_tracker_used_fields),
            CheckStaticListFieldsValueIsMovableStub::withMovableStaticValue(),
            CheckIsSingleStaticListFieldStub::withoutSingleStaticListField(),
            CheckFieldTypeCompatibilityStub::withCompatibleTypes(),
            CheckStaticFieldCanBeFullyMovedStub::withMovableField()
        );

        $collection = $collector->collect($this->source_tracker, $this->target_tracker, $this->artifact);

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertContains($source_string_field, $collection->migrateable_field_list);
        self::assertCount(1, $collection->mapping_fields);
        self::assertEquals($source_string_field, $collection->mapping_fields[0]->source);
        self::assertEquals($target_string_field, $collection->mapping_fields[0]->destination);
    }

    public function testFieldWillBeMigratedWhenWhenListFieldsAllowMigration(): void
    {
        $source_string_field        = TrackerFormElementListFieldBuilder::aListField(101)->withName("field_name")->build();
        $source_tracker_used_fields = [$source_string_field];

        $target_string_field        = TrackerFormElementListFieldBuilder::aListField(102)->withName("field_name")->build();
        $target_tracker_used_fields = [$target_string_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$target_tracker_used_fields),
            CheckStaticListFieldsValueIsMovableStub::withMovableStaticValue(),
            CheckIsSingleStaticListFieldStub::withSingleStaticListField(),
            CheckFieldTypeCompatibilityStub::withCompatibleTypes(),
            CheckStaticFieldCanBeFullyMovedStub::withMovableField()
        );

        $collection = $collector->collect($this->source_tracker, $this->target_tracker, $this->artifact);

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertContains($source_string_field, $collection->migrateable_field_list);
        self::assertCount(1, $collection->mapping_fields);
        self::assertEquals($source_string_field, $collection->mapping_fields[0]->source);
        self::assertEquals($target_string_field, $collection->mapping_fields[0]->destination);
    }

    public function testFieldWillNotBeMigratedWhenWhenListFieldsAreNotCompatible(): void
    {
        $source_string_field        = TrackerFormElementListFieldBuilder::aListField(101)->withName("field_name")->build();
        $source_tracker_used_fields = [$source_string_field];

        $target_string_field        = TrackerFormElementListFieldBuilder::aListField(102)->withName("field_name")->build();
        $target_tracker_used_fields = [$target_string_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$target_tracker_used_fields),
            CheckStaticListFieldsValueIsMovableStub::withMovableStaticValue(),
            CheckIsSingleStaticListFieldStub::withSingleStaticListField(),
            CheckFieldTypeCompatibilityStub::withoutCompatibleTypes(),
            CheckStaticFieldCanBeFullyMovedStub::withMovableField()
        );

        $collection = $collector->collect($this->source_tracker, $this->target_tracker, $this->artifact);

        self::assertContains($source_string_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertEmpty($collection->mapping_fields);
    }

    public function testFieldWillBeNotMigratedWhenWhenListFieldsAllowMigrationButFieldCanNotBeMoved(): void
    {
        $source_string_field        = TrackerFormElementListFieldBuilder::aListField(101)->withName("field_name")->build();
        $source_tracker_used_fields = [$source_string_field];

        $target_string_field        = TrackerFormElementListFieldBuilder::aListField(102)->withName("field_name")->build();
        $target_tracker_used_fields = [$target_string_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$target_tracker_used_fields),
            CheckStaticListFieldsValueIsMovableStub::withNoMovableStaticValue(),
            CheckIsSingleStaticListFieldStub::withSingleStaticListField(),
            CheckFieldTypeCompatibilityStub::withCompatibleTypes(),
            CheckStaticFieldCanBeFullyMovedStub::withMovableField()
        );

        $collection = $collector->collect($this->source_tracker, $this->target_tracker, $this->artifact);

        self::assertContains($source_string_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertEmpty($collection->mapping_fields);
    }

    public function testStaticFieldCanBePartiallyMoved(): void
    {
        $source_string_field        = TrackerFormElementListFieldBuilder::aListField(101)->withName("source_string")->build();
        $source_tracker_used_fields = [$source_string_field];

        $target_string_field        = TrackerFormElementListFieldBuilder::aListField(102)->withName("source_string")->build();
        $target_tracker_used_fields = [$target_string_field];

        $collector = new DryRunDuckTypingFieldCollector(
            RetrieveUsedFieldsStub::withFields(...$source_tracker_used_fields),
            RetrieveUsedFieldsStub::withFields(...$target_tracker_used_fields),
            CheckStaticListFieldsValueIsMovableStub::withMovableStaticValue(),
            CheckIsSingleStaticListFieldStub::withSingleStaticListField(),
            CheckFieldTypeCompatibilityStub::withCompatibleTypes(),
            CheckStaticFieldCanBeFullyMovedStub::withoutMovableField()
        );

        $collection = $collector->collect($this->source_tracker, $this->target_tracker, $this->artifact);

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertContains($source_string_field, $collection->partially_migrated_fields);
        self::assertEmpty($collection->mapping_fields);
    }
}
