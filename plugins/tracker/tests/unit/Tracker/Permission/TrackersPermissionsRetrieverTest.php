<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Permission;

use ForgeConfig;
use LogicException;
use PFUser;
use Tracker;
use Tracker_UserWithReadAllPermission;
use Tracker_Workflow_WorkflowUser;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\include\CheckUserCanAccessProjectStub;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\Permission\SearchUserGroupsPermissionOnFieldsStub;
use Tuleap\Tracker\Test\Stub\Tracker\Permission\SearchUserGroupsPermissionOnTrackersStub;
use Tuleap\User\TuleapFunctionsUser;

final class TrackersPermissionsRetrieverTest extends TestCase
{
    use ForgeConfigSandbox;

    protected function setUp(): void
    {
        ForgeConfig::setFeatureFlag(TrackersPermissionsRetriever::FEATURE_FLAG, 1);
    }

    public function testIsEnabled(): void
    {
        $permissions = new TrackersPermissionsRetriever(
            SearchUserGroupsPermissionOnFieldsStub::buildEmpty(),
            SearchUserGroupsPermissionOnTrackersStub::buildEmpty(),
            CheckUserCanAccessProjectStub::build(),
        );
        ForgeConfig::setFeatureFlag(TrackersPermissionsRetriever::FEATURE_FLAG, 0);
        self::assertFalse($permissions->isEnabled());
        ForgeConfig::setFeatureFlag(TrackersPermissionsRetriever::FEATURE_FLAG, 1);
        self::assertTrue($permissions->isEnabled());
    }

    public function testItThrowsIfFeatureIsDisabled(): void
    {
        ForgeConfig::setFeatureFlag(TrackersPermissionsRetriever::FEATURE_FLAG, 0);
        $permissions = new TrackersPermissionsRetriever(
            SearchUserGroupsPermissionOnFieldsStub::buildEmpty(),
            SearchUserGroupsPermissionOnTrackersStub::buildEmpty(),
            CheckUserCanAccessProjectStub::build(),
        );
        self::expectException(LogicException::class);
        $permissions->retrieveUserPermissionOnFields(UserTestBuilder::buildWithDefaults(), [], FieldPermissionType::PERMISSION_READ);
    }

    public function testItReturnsAllowedFields(): void
    {
        $user        = $this->createMock(PFUser::class);
        $project     = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker     = TrackerTestBuilder::aTracker()->withId(201)->withProject($project)->build();
        $field1      = IntFieldBuilder::anIntField(301)->inTracker($tracker)->build();
        $field2      = IntFieldBuilder::anIntField(302)->inTracker($tracker)->build();
        $field3      = IntFieldBuilder::anIntField(303)->inTracker($tracker)->build();
        $field4      = IntFieldBuilder::anIntField(304)->inTracker($tracker)->build();
        $permissions = new TrackersPermissionsRetriever(
            SearchUserGroupsPermissionOnFieldsStub::buildWithResults([301, 303, 304]),
            SearchUserGroupsPermissionOnTrackersStub::buildEmpty(),
            CheckUserCanAccessProjectStub::build(),
        );

        $user->method('getUgroups')->willReturn([]);
        $result = $permissions->retrieveUserPermissionOnFields($user, [$field1, $field2, $field3, $field4], FieldPermissionType::PERMISSION_READ);
        self::assertEqualsCanonicalizing([$field1, $field3, $field4], $result->allowed);
        self::assertEqualsCanonicalizing([$field2], $result->not_allowed);
    }

    public function testItReturnsAllowedTrackers(): void
    {
        $user        = $this->createMock(PFUser::class);
        $project     = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker1    = $this->createMock(Tracker::class);
        $tracker2    = $this->createMock(Tracker::class);
        $permissions = new TrackersPermissionsRetriever(
            SearchUserGroupsPermissionOnFieldsStub::buildEmpty(),
            SearchUserGroupsPermissionOnTrackersStub::buildWithResults([301]),
            CheckUserCanAccessProjectStub::build(),
        );
        $user->method('getUgroups')->willReturn([]);
        $user->method('getId')->willReturn(102);
        $tracker1->method('getId')->willReturn(301);
        $tracker1->method('getProject')->willReturn($project);
        $tracker1->method('userIsAdmin')->willReturn(false);
        $tracker2->method('getId')->willReturn(302);
        $tracker2->method('getProject')->willReturn($project);
        $tracker2->method('userIsAdmin')->willReturn(false);

        $result = $permissions->retrieveUserPermissionOnTrackers($user, [$tracker1, $tracker2]);
        self::assertEqualsCanonicalizing([$tracker1], $result->allowed);
        self::assertEqualsCanonicalizing([$tracker2], $result->not_allowed);
    }

    public static function provideSpecialUsers(): iterable
    {
        yield 'Tracker_Workflow_WorkflowUser' => [new Tracker_Workflow_WorkflowUser()];
        yield 'TuleapFunctionsUser' => [new TuleapFunctionsUser()];
        yield 'Tracker_UserWithReadAllPermission' => [new Tracker_UserWithReadAllPermission(UserTestBuilder::buildWithDefaults())];
    }

    /**
     * @dataProvider provideSpecialUsers
     */
    public function testItAllowAllFieldsIfUserIsSpecial(PFUser $user): void
    {
        $project     = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker     = TrackerTestBuilder::aTracker()->withId(201)->withProject($project)->build();
        $fields      = [
            IntFieldBuilder::anIntField(301)->inTracker($tracker)->build(),
            IntFieldBuilder::anIntField(302)->inTracker($tracker)->build(),
            IntFieldBuilder::anIntField(303)->inTracker($tracker)->build(),
            IntFieldBuilder::anIntField(304)->inTracker($tracker)->build(),
        ];
        $permissions = new TrackersPermissionsRetriever(
            SearchUserGroupsPermissionOnFieldsStub::buildEmpty(),
            SearchUserGroupsPermissionOnTrackersStub::buildEmpty(),
            CheckUserCanAccessProjectStub::build(),
        );

        $result = $permissions->retrieveUserPermissionOnFields($user, $fields, FieldPermissionType::PERMISSION_READ);
        self::assertEqualsCanonicalizing($fields, $result->allowed);
        self::assertEmpty($result->not_allowed);
    }

    public function testItAllowAllTrackersIfUserIsAdmin(): void
    {
        $permissions = new TrackersPermissionsRetriever(
            SearchUserGroupsPermissionOnFieldsStub::buildEmpty(),
            SearchUserGroupsPermissionOnTrackersStub::buildEmpty(),
            CheckUserCanAccessProjectStub::build(),
        );
        $tracker     = $this->createMock(Tracker::class);
        $trackers    = [$tracker, $tracker, $tracker, $tracker, $tracker];
        $user        = $this->createMock(PFUser::class);
        $user->method('getUgroups')->willReturn([]);
        $user->method('getId')->willReturn(101);
        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->withId(101)->build());
        $tracker->method('userIsAdmin')->with($user)->willReturn(true);
        $tracker->method('getId')->willReturn(301);

        $result = $permissions->retrieveUserPermissionOnTrackers($user, $trackers);
        self::assertEqualsCanonicalizing($trackers, $result->allowed);
        self::assertEmpty($result->not_allowed);
    }
}
