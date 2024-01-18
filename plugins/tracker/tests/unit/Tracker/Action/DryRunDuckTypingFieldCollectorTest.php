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

use Psr\Log\NullLogger;
use Tracker_FormElement_Field_Burndown;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerExternalFormElementBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementIntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerFormElementStringFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;
use Tuleap\Tracker\Test\Stub\VerifyExternalFieldsHaveSameTypeStub;
use Tuleap\Tracker\Test\Stub\VerifyFieldCanBeEasilyMigratedStub;
use Tuleap\Tracker\Test\Stub\VerifyIsExternalFieldStub;
use Tuleap\Tracker\Test\Stub\VerifyIsOpenListFieldStub;
use Tuleap\Tracker\Test\Stub\VerifyIsPermissionsOnArtifactFieldStub;
use Tuleap\Tracker\Test\Stub\VerifyIsStaticListFieldStub;
use Tuleap\Tracker\Test\Stub\VerifyIsUserGroupListFieldStub;
use Tuleap\Tracker\Test\Stub\VerifyIsUserListFieldStub;
use Tuleap\Tracker\Test\Stub\VerifyListFieldsAreCompatibleStub;
use Tuleap\Tracker\Test\Stub\VerifyOpenListFieldsAreCompatibleStub;
use Tuleap\Tracker\Test\Stub\VerifyPermissionsCanBeFullyMovedStub;
use Tuleap\Tracker\Test\Stub\VerifyStaticFieldValuesCanBeFullyMovedStub;
use Tuleap\Tracker\Test\Stub\VerifyThereArePermissionsToMigrateStub;
use Tuleap\Tracker\Test\Stub\VerifyUserFieldValuesCanBeFullyMovedStub;
use Tuleap\Tracker\Test\Stub\VerifyUserGroupValuesCanBeFullyMovedStub;

final class DryRunDuckTypingFieldCollectorTest extends TestCase
{
    private const DESTINATION_TRACKER_ID = 105;
    private const SOURCE_TRACKER_ID      = 609;

    private \Tracker $source_tracker;
    private \Tracker $destination_tracker;
    private Artifact $artifact;
    private \PFUser $user;
    /** @var list<\Tracker_FormElement_Field> */
    private array $all_fields;
    private \Tracker_FormElement_Field_Selectbox|\Tracker_FormElement_Field_MultiSelectbox $source_list_field;
    private \Tracker_FormElement_Field_Selectbox|\Tracker_FormElement_Field_MultiSelectbox $destination_list_field;
    private EventDispatcherStub $event_dispatcher;
    private VerifyFieldCanBeEasilyMigratedStub $verify_easily_migrated;
    private VerifyIsStaticListFieldStub $verify_static_list;
    private VerifyListFieldsAreCompatibleStub $verify_list_fields_are_compatible;
    private VerifyStaticFieldValuesCanBeFullyMovedStub $verify_static_list_move;
    private VerifyIsUserListFieldStub $verify_user_list;
    private VerifyUserFieldValuesCanBeFullyMovedStub $verify_user_list_move;
    private VerifyIsUserGroupListFieldStub $verify_user_group_list;
    private VerifyUserGroupValuesCanBeFullyMovedStub $verify_user_group_move;
    private VerifyIsPermissionsOnArtifactFieldStub $verify_permission;
    private VerifyThereArePermissionsToMigrateStub $verify_permission_to_migrate;
    private VerifyPermissionsCanBeFullyMovedStub $verify_permission_move;
    private VerifyIsOpenListFieldStub $verify_open_list;
    private VerifyOpenListFieldsAreCompatibleStub $verify_open_lists_are_compatible;
    private VerifyIsExternalFieldStub $verify_external_field;
    private VerifyExternalFieldsHaveSameTypeStub $verify_external_fields_have_same_type;

    protected function setUp(): void
    {
        $this->source_tracker      = TrackerTestBuilder::aTracker()->withId(self::SOURCE_TRACKER_ID)->build();
        $this->destination_tracker = TrackerTestBuilder::aTracker()->withId(self::DESTINATION_TRACKER_ID)->build();

        $this->artifact = ArtifactTestBuilder::anArtifact(1)->build();
        $this->user     = UserTestBuilder::aUser()->withSiteAdministrator()->build();

        $this->all_fields = [];

        $this->source_list_field      = TrackerFormElementListFieldBuilder::aListField(101)
            ->withName('field_name')
            ->inTracker($this->source_tracker)
            ->build();
        $this->destination_list_field = TrackerFormElementListFieldBuilder::aListField(102)
            ->withName('field_name')
            ->inTracker($this->destination_tracker)
            ->build();

        $this->event_dispatcher                      = EventDispatcherStub::withIdentityCallback();
        $this->verify_easily_migrated                = VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields();
        $this->verify_static_list                    = VerifyIsStaticListFieldStub::withoutSingleStaticListField();
        $this->verify_list_fields_are_compatible     = VerifyListFieldsAreCompatibleStub::withCompatibleFields();
        $this->verify_static_list_move               = VerifyStaticFieldValuesCanBeFullyMovedStub::withPartialMove();
        $this->verify_user_list                      = VerifyIsUserListFieldStub::withoutUserListField();
        $this->verify_user_list_move                 = VerifyUserFieldValuesCanBeFullyMovedStub::withPartialMove();
        $this->verify_user_group_list                = VerifyIsUserGroupListFieldStub::withoutUserGroupListField();
        $this->verify_user_group_move                = VerifyUserGroupValuesCanBeFullyMovedStub::withPartialMove();
        $this->verify_permission                     = VerifyIsPermissionsOnArtifactFieldStub::withoutPermissionsOnArtifactField();
        $this->verify_permission_to_migrate          = VerifyThereArePermissionsToMigrateStub::withoutPermissionsToMigrate();
        $this->verify_permission_move                = VerifyPermissionsCanBeFullyMovedStub::withPartialMove();
        $this->verify_open_list                      = VerifyIsOpenListFieldStub::withoutOpenListField();
        $this->verify_open_lists_are_compatible      = VerifyOpenListFieldsAreCompatibleStub::withoutCompatibleFields();
        $this->verify_external_field                 = VerifyIsExternalFieldStub::withoutExternalField();
        $this->verify_external_fields_have_same_type = VerifyExternalFieldsHaveSameTypeStub::withoutSameType();
    }

    private function collect(): DuckTypedMoveFieldCollection
    {
        $collector = new DryRunDuckTypingFieldCollector(
            $this->event_dispatcher,
            RetrieveUsedFieldsStub::withFields(...$this->all_fields),
            $this->verify_easily_migrated,
            $this->verify_static_list,
            $this->verify_list_fields_are_compatible,
            $this->verify_static_list_move,
            $this->verify_user_list,
            $this->verify_user_list_move,
            $this->verify_user_group_list,
            $this->verify_user_group_move,
            $this->verify_permission,
            $this->verify_permission_to_migrate,
            $this->verify_permission_move,
            $this->verify_open_list,
            $this->verify_open_lists_are_compatible,
            $this->verify_external_field,
            $this->verify_external_fields_have_same_type,
        );

        return $collector->collect(
            $this->source_tracker,
            $this->destination_tracker,
            $this->artifact,
            $this->user,
            new NullLogger()
        );
    }

    private function collectWithListFields(): DuckTypedMoveFieldCollection
    {
        $this->all_fields = [$this->source_list_field, $this->destination_list_field];
        return $this->collect();
    }

    public function testFieldWillNotBeMigratedWhenTargetTrackerHasNoFieldWithTheSameName(): void
    {
        $source_string_field = TrackerFormElementStringFieldBuilder::aStringField(101)
            ->withName("source_string")
            ->inTracker($this->source_tracker)
            ->build();
        $this->all_fields    = [
            $source_string_field,
            TrackerFormElementStringFieldBuilder::aStringField(102)
                ->withName("destination_string")
                ->inTracker($this->destination_tracker)
                ->build(),
            TrackerFormElementStringFieldBuilder::aStringField(103)
                ->withName("another_destination_string")
                ->inTracker($this->destination_tracker)
                ->build(),
        ];

        $this->verify_easily_migrated = VerifyFieldCanBeEasilyMigratedStub::withEasilyMovableFields();

        $collection = $this->collect();

        self::assertContains($source_string_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->mapping_fields);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testFieldWillNotBeMigratedWhenUserCanNotUpdateField(): void
    {
        $source_open_list = $this->createStub(\Tracker_FormElement_Field_OpenList::class);
        $source_open_list->method("getId")->willReturn(12);
        $source_open_list->method("getTrackerId")->willReturn(self::SOURCE_TRACKER_ID);
        $source_open_list->method("getLabel")->willReturn("string_field");
        $source_open_list->method("getName")->willReturn("string_field");
        $source_open_list->method("userCanUpdate")->willReturn(false);
        $this->all_fields = [
            $source_open_list,
            TrackerFormElementStringFieldBuilder::aStringField(102)
                ->inTracker($this->destination_tracker)
                ->withName('release_number')
                ->withLabel('Release number')
                ->build(),
        ];

        $this->verify_easily_migrated = VerifyFieldCanBeEasilyMigratedStub::withEasilyMovableFields();

        $collection = $this->collect();

        self::assertContains($source_open_list, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->mapping_fields);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testFieldWillBeMigratedForReadOnlyField(): void
    {
        $source_burndown = $this->createStub(\Tracker_FormElement_Field_Burndown::class);
        $source_burndown->method("getLabel")->willReturn("string_field");
        $source_burndown->method("getName")->willReturn("string_field");
        $source_burndown->method("userCanUpdate")->willReturn(false);
        $source_burndown->method("getId")->willReturn(12);
        $source_burndown->method("getTrackerId")->willReturn(self::SOURCE_TRACKER_ID);
        $this->all_fields = [
            $source_burndown,
            new Tracker_FormElement_Field_Burndown(
                102,
                self::DESTINATION_TRACKER_ID,
                null,
                "string_field",
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

        $this->verify_easily_migrated = VerifyFieldCanBeEasilyMigratedStub::withEasilyMovableFields();

        $collection = $this->collect();

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertContains($source_burndown, $collection->migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testFieldWillNotBeMigratedWhenFieldInTargetTrackerHasNoCompatibleType(): void
    {
        $source_string_field   = TrackerFormElementStringFieldBuilder::aStringField(101)
            ->withName("release_number")
            ->inTracker($this->source_tracker)
            ->build();
        $destination_int_field = TrackerFormElementIntFieldBuilder::anIntField(102)
            ->withName("release_number")
            ->inTracker($this->destination_tracker)
            ->build();
        $this->all_fields      = [$source_string_field, $destination_int_field];

        $this->verify_easily_migrated = VerifyFieldCanBeEasilyMigratedStub::withoutEasilyMovableFields();

        $collection = $this->collect();

        self::assertContains($source_string_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->mapping_fields);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testFieldWillBeMigratedWhenFieldInTargetTrackerHasTheSameNameAndIsAnEasilyMovableField(): void
    {
        $source_string_field      = TrackerFormElementStringFieldBuilder::aStringField(101)
            ->withName("source_string")
            ->inTracker($this->source_tracker)
            ->build();
        $destination_string_field = TrackerFormElementStringFieldBuilder::aStringField(102)
            ->withName("source_string")
            ->inTracker($this->destination_tracker)
            ->build();
        $this->all_fields         = [$source_string_field, $destination_string_field];

        $this->verify_easily_migrated = VerifyFieldCanBeEasilyMigratedStub::withEasilyMovableFields();

        $collection = $this->collect();

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertContains($source_string_field, $collection->migrateable_field_list);
        self::assertCount(1, $collection->mapping_fields);
        self::assertEquals($source_string_field, $collection->mapping_fields[0]->source);
        self::assertEquals($destination_string_field, $collection->mapping_fields[0]->destination);
    }

    public function testStaticListFieldWillBeMigratedWhenMatchingDestinationFieldContainsAllTheSelectedValues(): void
    {
        $this->verify_static_list      = VerifyIsStaticListFieldStub::withSingleStaticListField();
        $this->verify_static_list_move = VerifyStaticFieldValuesCanBeFullyMovedStub::withCompleteMove();

        $collection = $this->collectWithListFields();

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertContains($this->source_list_field, $collection->migrateable_field_list);
        self::assertCount(1, $collection->mapping_fields);
        self::assertEquals($this->source_list_field, $collection->mapping_fields[0]->source);
        self::assertEquals($this->destination_list_field, $collection->mapping_fields[0]->destination);
    }

    public function testFieldWillNotBeMigratedWhenWhenListFieldsAreNotCompatible(): void
    {
        $this->verify_static_list                = VerifyIsStaticListFieldStub::withSingleStaticListField();
        $this->verify_list_fields_are_compatible = VerifyListFieldsAreCompatibleStub::withoutCompatibleFields();
        $this->verify_static_list_move           = VerifyStaticFieldValuesCanBeFullyMovedStub::withCompleteMove();
        $this->verify_user_list                  = VerifyIsUserListFieldStub::withUserListField();

        $collection = $this->collectWithListFields();

        self::assertContains($this->source_list_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertEmpty($collection->mapping_fields);
    }

    public function testFieldWillBeNotMigratedWhenWhenListFieldsAllowMigrationButFieldCanNotBeMoved(): void
    {
        $this->verify_static_list                = VerifyIsStaticListFieldStub::withSingleStaticListField();
        $this->verify_list_fields_are_compatible = VerifyListFieldsAreCompatibleStub::withoutCompatibleFields();

        $collection = $this->collectWithListFields();

        self::assertContains($this->source_list_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertEmpty($collection->mapping_fields);
    }

    public function testStaticFieldCanBePartiallyMoved(): void
    {
        $this->verify_static_list = VerifyIsStaticListFieldStub::withSingleStaticListField();

        $collection = $this->collectWithListFields();

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertContains($this->source_list_field, $collection->partially_migrated_fields);
        self::assertSame($this->source_list_field, $collection->mapping_fields[0]->source);
        self::assertSame($this->destination_list_field, $collection->mapping_fields[0]->destination);
    }

    public function testUserBoundFieldWillBeNotMigratedWhenTheDestinationFieldIsNotBoundToUsers(): void
    {
        $this->verify_list_fields_are_compatible = VerifyListFieldsAreCompatibleStub::withoutCompatibleFields();
        $this->verify_user_list                  = VerifyIsUserListFieldStub::withUserListField();

        $collection = $this->collectWithListFields();

        self::assertContains($this->source_list_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertEmpty($collection->mapping_fields);
    }

    public function testUsersBoundFieldCanBePartiallyMovedWhenTheDestinationFieldDoesNotContainAllTheSelectedUsers(): void
    {
        $this->verify_user_list = VerifyIsUserListFieldStub::withUserListField();

        $collection = $this->collectWithListFields();

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertContains($this->source_list_field, $collection->partially_migrated_fields);
        self::assertSame($this->source_list_field, $collection->mapping_fields[0]->source);
        self::assertSame($this->destination_list_field, $collection->mapping_fields[0]->destination);
    }

    public function testUserBoundListFieldWillBeMigratedWhenMatchingDestinationFieldContainsAllTheSelectedUsers(): void
    {
        $this->verify_user_list      = VerifyIsUserListFieldStub::withUserListField();
        $this->verify_user_list_move = VerifyUserFieldValuesCanBeFullyMovedStub::withCompleteMove();

        $collection = $this->collectWithListFields();

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertContains($this->source_list_field, $collection->migrateable_field_list);
        self::assertSame($this->source_list_field, $collection->mapping_fields[0]->source);
        self::assertSame($this->destination_list_field, $collection->mapping_fields[0]->destination);
    }

    public function testUserGroupBoundFieldWillBeNotMigratedWhenTheDestinationFieldIsNotBoundToUserGroups(): void
    {
        $this->verify_list_fields_are_compatible = VerifyListFieldsAreCompatibleStub::withoutCompatibleFields();
        $this->verify_user_list                  = VerifyIsUserListFieldStub::withUserListField();

        $collection = $this->collectWithListFields();

        self::assertContains($this->source_list_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertEmpty($collection->mapping_fields);
    }

    public function testUserGroupsBoundFieldCanBePartiallyMovedWhenTheDestinationFieldDoesNotContainAllTheSelectedUserGroups(): void
    {
        $this->verify_user_group_list = VerifyIsUserGroupListFieldStub::withUserGroupListField();

        $collection = $this->collectWithListFields();

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertContains($this->source_list_field, $collection->partially_migrated_fields);
        self::assertSame($this->source_list_field, $collection->mapping_fields[0]->source);
        self::assertSame($this->destination_list_field, $collection->mapping_fields[0]->destination);
    }

    public function testUserGroupsBoundListFieldWillBeMigratedWhenMatchingDestinationFieldContainsAllTheSelectedUserGroups(): void
    {
        $this->verify_user_group_list = VerifyIsUserGroupListFieldStub::withUserGroupListField();
        $this->verify_user_group_move = VerifyUserGroupValuesCanBeFullyMovedStub::withCompleteMove();

        $collection = $this->collectWithListFields();

        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
        self::assertContains($this->source_list_field, $collection->migrateable_field_list);
        self::assertSame($this->source_list_field, $collection->mapping_fields[0]->source);
        self::assertSame($this->destination_list_field, $collection->mapping_fields[0]->destination);
    }

    public function testPermissionsFieldWillNotBeMigratedDestinationFieldIsNotCompatible(): void
    {
        $source_permissions_field = $this->createStub(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $source_permissions_field->method("getLabel")->willReturn("permissions");
        $source_permissions_field->method("getName")->willReturn("permissions");
        $source_permissions_field->method("getId")->willReturn(12);
        $source_permissions_field->method("getTrackerId")->willReturn(self::SOURCE_TRACKER_ID);

        $this->all_fields = [
            $source_permissions_field,
            TrackerFormElementListFieldBuilder::aListField(1)
                ->withName("permissions")
                ->inTracker($this->destination_tracker)
                ->build(),
        ];

        $collection = $this->collect();

        self::assertContains($source_permissions_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testPermissionsFieldWillNotBeMigratedIfThereIsNoPermissionToMigrate(): void
    {
        $source_permissions_field = $this->createStub(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $source_permissions_field->method("getLabel")->willReturn("permissions");
        $source_permissions_field->method("getName")->willReturn("permissions");
        $source_permissions_field->method("getId")->willReturn(12);
        $source_permissions_field->method("getTrackerId")->willReturn(self::SOURCE_TRACKER_ID);

        $destination_permissions_field = $this->createStub(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $destination_permissions_field->method("getLabel")->willReturn("permissions");
        $destination_permissions_field->method("getName")->willReturn("permissions");
        $destination_permissions_field->method("userCanUpdate")->willReturn(true);
        $destination_permissions_field->method("isUpdateable")->willReturn(true);
        $destination_permissions_field->method("getTrackerId")->willReturn(self::DESTINATION_TRACKER_ID);
        $this->all_fields = [$source_permissions_field, $destination_permissions_field];

        $this->verify_permission = VerifyIsPermissionsOnArtifactFieldStub::withPermissionsOnArtifactField();

        $collection = $this->collect();

        self::assertContains($source_permissions_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testPermissionsFieldWillBePartiallyMigratedWhenDestinationFieldDoesNotContainAllSourceValues(): void
    {
        $source_permissions_field = $this->createStub(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $source_permissions_field->method("getLabel")->willReturn("permissions");
        $source_permissions_field->method("getName")->willReturn("permissions");
        $source_permissions_field->method("getId")->willReturn(12);
        $source_permissions_field->method("getTrackerId")->willReturn(self::SOURCE_TRACKER_ID);

        $destination_permissions_field = $this->createStub(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $destination_permissions_field->method("getLabel")->willReturn("permissions");
        $destination_permissions_field->method("getName")->willReturn("permissions");
        $destination_permissions_field->method("userCanUpdate")->willReturn(true);
        $destination_permissions_field->method("isUpdateable")->willReturn(true);
        $destination_permissions_field->method("getTrackerId")->willReturn(self::DESTINATION_TRACKER_ID);
        $this->all_fields = [$source_permissions_field, $destination_permissions_field];

        $this->verify_permission            = VerifyIsPermissionsOnArtifactFieldStub::withPermissionsOnArtifactField();
        $this->verify_permission_to_migrate = VerifyThereArePermissionsToMigrateStub::withPermissionsToMigrate();

        $collection = $this->collect();

        self::assertContains($source_permissions_field, $collection->partially_migrated_fields);
        self::assertSame($source_permissions_field, $collection->mapping_fields[0]->source);
        self::assertSame($destination_permissions_field, $collection->mapping_fields[0]->destination);
        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
    }

    public function testPermissionsFieldWillBeFullyMigratedWhenAllValuesAreAvailableInDestinationField(): void
    {
        $source_permissions_field = $this->createStub(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $source_permissions_field->method("getLabel")->willReturn("permissions");
        $source_permissions_field->method("getName")->willReturn("permissions");
        $source_permissions_field->method("getId")->willReturn(12);
        $source_permissions_field->method("getTrackerId")->willReturn(self::SOURCE_TRACKER_ID);

        $destination_permissions_field = $this->createStub(\Tracker_FormElement_Field_PermissionsOnArtifact::class);
        $destination_permissions_field->method("getLabel")->willReturn("permissions");
        $destination_permissions_field->method("getName")->willReturn("permissions");
        $destination_permissions_field->method("userCanUpdate")->willReturn(true);
        $destination_permissions_field->method("isUpdateable")->willReturn(true);
        $destination_permissions_field->method("getTrackerId")->willReturn(self::DESTINATION_TRACKER_ID);
        $this->all_fields = [$source_permissions_field, $destination_permissions_field];

        $this->verify_permission            = VerifyIsPermissionsOnArtifactFieldStub::withPermissionsOnArtifactField();
        $this->verify_permission_to_migrate = VerifyThereArePermissionsToMigrateStub::withPermissionsToMigrate();
        $this->verify_permission_move       = VerifyPermissionsCanBeFullyMovedStub::withCompleteMove();

        $collection = $this->collect();

        self::assertContains($source_permissions_field, $collection->migrateable_field_list);
        self::assertSame($source_permissions_field, $collection->mapping_fields[0]->source);
        self::assertSame($destination_permissions_field, $collection->mapping_fields[0]->destination);
        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testOpenListFieldWillNotBeMigratedDestinationFieldIsNotCompatible(): void
    {
        $source_open_list_field = $this->createStub(\Tracker_FormElement_Field_OpenList::class);
        $source_open_list_field->method("getLabel")->willReturn("open_list");
        $source_open_list_field->method("getName")->willReturn("open_list");
        $source_open_list_field->method("getId")->willReturn(12);
        $source_open_list_field->method("getTrackerId")->willReturn(self::SOURCE_TRACKER_ID);
        $this->all_fields = [
            $source_open_list_field,
            TrackerFormElementListFieldBuilder::aListField(1)
                ->withName("open_list")
                ->inTracker($this->destination_tracker)
                ->build(),
        ];

        $collection = $this->collect();

        self::assertContains($source_open_list_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testOpenListCanBeMigrated(): void
    {
        $source_open_list_field = $this->createStub(\Tracker_FormElement_Field_OpenList::class);
        $source_open_list_field->method("getLabel")->willReturn("open_list");
        $source_open_list_field->method("getName")->willReturn("open_list");
        $source_open_list_field->method("getId")->willReturn(12);
        $source_open_list_field->method("getTrackerId")->willReturn(self::SOURCE_TRACKER_ID);

        $destination_open_list_field = $this->createStub(\Tracker_FormElement_Field_OpenList::class);
        $destination_open_list_field->method("getLabel")->willReturn("open_list");
        $destination_open_list_field->method("getName")->willReturn("open_list");
        $destination_open_list_field->method("userCanUpdate")->willReturn(true);
        $destination_open_list_field->method("isUpdateable")->willReturn(true);
        $destination_open_list_field->method("getTrackerId")->willReturn(self::DESTINATION_TRACKER_ID);
        $this->all_fields = [$source_open_list_field, $destination_open_list_field];

        $this->verify_open_list                 = VerifyIsOpenListFieldStub::withOpenListField();
        $this->verify_open_lists_are_compatible = VerifyOpenListFieldsAreCompatibleStub::withCompatibleFields();

        $collection = $this->collect();

        self::assertContains($source_open_list_field, $collection->migrateable_field_list);
        self::assertSame($source_open_list_field, $collection->mapping_fields[0]->source);
        self::assertSame($destination_open_list_field, $collection->mapping_fields[0]->destination);
        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testExternalFieldsCannotBeMigratedWhenTheyHaveNotTheSameType(): void
    {
        $source_external_field      = TrackerExternalFormElementBuilder::anExternalField(1)
            ->withName("external_field")
            ->inTracker($this->source_tracker)
            ->build();
        $destination_external_field = TrackerExternalFormElementBuilder::anExternalField(2)
            ->withName("external_field")
            ->inTracker($this->destination_tracker)
            ->build();
        $this->all_fields           = [$source_external_field, $destination_external_field];

        $this->verify_external_field = VerifyIsExternalFieldStub::withExternalField();

        $collection = $this->collect();

        self::assertContains($source_external_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->mapping_fields);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testExternalFieldsWontBeMigratedIfEventSaysSo(): void
    {
        $source_external_field      = TrackerExternalFormElementBuilder::anExternalField(1)
            ->withName("external_field")
            ->inTracker($this->source_tracker)
            ->build();
        $destination_external_field = TrackerExternalFormElementBuilder::anExternalField(2)
            ->withName("external_field")
            ->inTracker($this->destination_tracker)
            ->build();
        $this->all_fields           = [$source_external_field, $destination_external_field];

        $this->event_dispatcher                      = EventDispatcherStub::withCallback(
            static function (CollectMovableExternalFieldEvent $event): CollectMovableExternalFieldEvent {
                $event->markFieldAsNotMigrateable();
                return $event;
            }
        );
        $this->verify_external_field                 = VerifyIsExternalFieldStub::withExternalField();
        $this->verify_external_fields_have_same_type = VerifyExternalFieldsHaveSameTypeStub::withSameType();

        $collection = $this->collect();

        self::assertContains($source_external_field, $collection->not_migrateable_field_list);
        self::assertEmpty($collection->migrateable_field_list);
        self::assertEmpty($collection->mapping_fields);
        self::assertEmpty($collection->partially_migrated_fields);
    }

    public function testExternalFieldsWillBeFullyMigratedIfEventSaysSo(): void
    {
        $source_external_field      = TrackerExternalFormElementBuilder::anExternalField(1)
            ->withName("external_field")
            ->inTracker($this->source_tracker)
            ->build();
        $destination_external_field = TrackerExternalFormElementBuilder::anExternalField(2)
            ->withName("external_field")
            ->inTracker($this->destination_tracker)
            ->build();
        $this->all_fields           = [$source_external_field, $destination_external_field];

        $this->event_dispatcher                      = EventDispatcherStub::withCallback(
            static function (CollectMovableExternalFieldEvent $event): CollectMovableExternalFieldEvent {
                $event->markFieldAsFullyMigrateable();
                return $event;
            }
        );
        $this->verify_external_field                 = VerifyIsExternalFieldStub::withExternalField();
        $this->verify_external_fields_have_same_type = VerifyExternalFieldsHaveSameTypeStub::withSameType();

        $collection = $this->collect();

        self::assertContains($source_external_field, $collection->migrateable_field_list);
        self::assertSame($source_external_field, $collection->mapping_fields[0]->source);
        self::assertSame($destination_external_field, $collection->mapping_fields[0]->destination);
        self::assertEmpty($collection->not_migrateable_field_list);
        self::assertEmpty($collection->partially_migrated_fields);
    }
}
