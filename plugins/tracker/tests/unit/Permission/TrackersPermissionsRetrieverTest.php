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
use PFUser;
use Tracker;
use Tracker_UserWithReadAllPermission;
use Tracker_Workflow_WorkflowUser;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\include\CheckUserCanAccessProjectStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Permission\SearchUserGroupsPermissionOnArtifactsStub;
use Tuleap\Tracker\Test\Stub\Permission\SearchUserGroupsPermissionOnFieldsStub;
use Tuleap\Tracker\Test\Stub\Permission\SearchUserGroupsPermissionOnTrackersStub;
use Tuleap\User\TuleapFunctionsUser;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
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
            SearchUserGroupsPermissionOnTrackersStub::build(),
            SearchUserGroupsPermissionOnArtifactsStub::buildEmpty(),
            CheckUserCanAccessProjectStub::build(),
            EventDispatcherStub::withIdentityCallback(),
            RetrieveUserByIdStub::withNoUser(),
        );
        ForgeConfig::setFeatureFlag(TrackersPermissionsRetriever::FEATURE_FLAG, 0);
        self::assertFalse($permissions->isEnabled());
        ForgeConfig::setFeatureFlag(TrackersPermissionsRetriever::FEATURE_FLAG, 1);
        self::assertTrue($permissions->isEnabled());
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
            SearchUserGroupsPermissionOnTrackersStub::build(),
            SearchUserGroupsPermissionOnArtifactsStub::buildEmpty(),
            CheckUserCanAccessProjectStub::build(),
            EventDispatcherStub::withIdentityCallback(),
            RetrieveUserByIdStub::withNoUser(),
        );

        $user->method('getUgroups')->willReturn([]);
        $result = $permissions->retrieveUserPermissionOnFields($user, [$field1, $field2, $field3, $field4], FieldPermissionType::PERMISSION_READ);
        self::assertEqualsCanonicalizing([$field1, $field3, $field4], $result->allowed);
        self::assertEqualsCanonicalizing([$field2], $result->not_allowed);
    }

    public function testItReturnsAllowedReadFieldsEvenIfOnlyUpdatePermission(): void
    {
        $user                  = $this->createMock(PFUser::class);
        $project               = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker               = TrackerTestBuilder::aTracker()->withId(201)->withProject($project)->build();
        $field1                = IntFieldBuilder::anIntField(301)->inTracker($tracker)->build();
        $field2                = IntFieldBuilder::anIntField(302)->inTracker($tracker)->build();
        $field3                = IntFieldBuilder::anIntField(303)->inTracker($tracker)->build();
        $field4                = IntFieldBuilder::anIntField(304)->inTracker($tracker)->build();
        $permissions_on_fields = $this->createMock(SearchUserGroupsPermissionOnFields::class);
        $permissions           = new TrackersPermissionsRetriever(
            $permissions_on_fields,
            SearchUserGroupsPermissionOnTrackersStub::build(),
            SearchUserGroupsPermissionOnArtifactsStub::buildEmpty(),
            CheckUserCanAccessProjectStub::build(),
            EventDispatcherStub::withIdentityCallback(),
            RetrieveUserByIdStub::withNoUser(),
        );

        $user->method('getUgroups')->willReturn([]);
        $permissions_on_fields->expects(self::exactly(2))->method('searchUserGroupsPermissionOnFields')
            ->willReturnCallback(static fn(array $user_groups_id, array $fields_id, string $permission) => match ($permission) {
                FieldPermissionType::PERMISSION_READ->value   => [],
                FieldPermissionType::PERMISSION_UPDATE->value => [301, 303, 304],
            });
        $result = $permissions->retrieveUserPermissionOnFields($user, [$field1, $field2, $field3, $field4], FieldPermissionType::PERMISSION_READ);
        self::assertEqualsCanonicalizing([$field1, $field3, $field4], $result->allowed);
        self::assertEqualsCanonicalizing([$field2], $result->not_allowed);
    }

    public static function provideSpecialUsers(): iterable
    {
        yield 'Tracker_Workflow_WorkflowUser' => [new Tracker_Workflow_WorkflowUser()];
        yield 'TuleapFunctionsUser' => [new TuleapFunctionsUser()];
        yield 'Tracker_UserWithReadAllPermission' => [new Tracker_UserWithReadAllPermission(UserTestBuilder::buildWithDefaults())];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideSpecialUsers')]
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
            SearchUserGroupsPermissionOnTrackersStub::build(),
            SearchUserGroupsPermissionOnArtifactsStub::buildEmpty(),
            CheckUserCanAccessProjectStub::build(),
            EventDispatcherStub::withIdentityCallback(),
            RetrieveUserByIdStub::withNoUser(),
        );

        $result = $permissions->retrieveUserPermissionOnFields($user, $fields, FieldPermissionType::PERMISSION_READ);
        self::assertEqualsCanonicalizing($fields, $result->allowed);
        self::assertEmpty($result->not_allowed);
    }

    public function testItReturnsAllowedTrackersView(): void
    {
        $user        = $this->createMock(PFUser::class);
        $project     = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker1    = $this->createMock(Tracker::class);
        $tracker2    = $this->createMock(Tracker::class);
        $permissions = new TrackersPermissionsRetriever(
            SearchUserGroupsPermissionOnFieldsStub::buildEmpty(),
            SearchUserGroupsPermissionOnTrackersStub::build()->withViewResults([301]),
            SearchUserGroupsPermissionOnArtifactsStub::buildEmpty(),
            CheckUserCanAccessProjectStub::build(),
            EventDispatcherStub::withIdentityCallback(),
            RetrieveUserByIdStub::withNoUser(),
        );
        $user->method('getUgroups')->willReturn([]);
        $user->method('getId')->willReturn(102);
        $tracker1->method('getId')->willReturn(301);
        $tracker1->method('getProject')->willReturn($project);
        $tracker1->method('userIsAdmin')->willReturn(false);
        $tracker2->method('getId')->willReturn(302);
        $tracker2->method('getProject')->willReturn($project);
        $tracker2->method('userIsAdmin')->willReturn(false);

        $result = $permissions->retrieveUserPermissionOnTrackers($user, [$tracker1, $tracker2], TrackerPermissionType::PERMISSION_VIEW);
        self::assertEqualsCanonicalizing([$tracker1], $result->allowed);
        self::assertEqualsCanonicalizing([$tracker2], $result->not_allowed);
    }

    public static function provideTrackerPermissionTypes(): iterable
    {
        yield 'Permission VIEW' => [TrackerPermissionType::PERMISSION_VIEW];
        yield 'Permission SUBMIT' => [TrackerPermissionType::PERMISSION_SUBMIT];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideTrackerPermissionTypes')]
    public function testItAllowAllTrackersIfUserIsAdmin(TrackerPermissionType $permission): void
    {
        $permissions = new TrackersPermissionsRetriever(
            SearchUserGroupsPermissionOnFieldsStub::buildEmpty(),
            SearchUserGroupsPermissionOnTrackersStub::build(),
            SearchUserGroupsPermissionOnArtifactsStub::buildEmpty(),
            CheckUserCanAccessProjectStub::build(),
            EventDispatcherStub::withIdentityCallback(),
            RetrieveUserByIdStub::withNoUser(),
        );
        $tracker     = $this->createMock(Tracker::class);
        $trackers    = [$tracker, $tracker, $tracker, $tracker, $tracker];
        $user        = $this->createMock(PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getUgroups')->willReturn([]);
        $user->method('getId')->willReturn(101);
        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->withId(101)->build());
        $tracker->method('userIsAdmin')->with($user)->willReturn(true);
        $tracker->method('getId')->willReturn(301);

        $result = $permissions->retrieveUserPermissionOnTrackers($user, $trackers, $permission);
        self::assertEqualsCanonicalizing($trackers, $result->allowed);
        self::assertEmpty($result->not_allowed);
    }

    public function testItReturnsNotAllowedTrackersSubmitIfUserAnonymous(): void
    {
        $user        = $this->createMock(PFUser::class);
        $tracker1    = $this->createMock(Tracker::class);
        $tracker2    = $this->createMock(Tracker::class);
        $permissions = new TrackersPermissionsRetriever(
            SearchUserGroupsPermissionOnFieldsStub::buildEmpty(),
            SearchUserGroupsPermissionOnTrackersStub::build(),
            SearchUserGroupsPermissionOnArtifactsStub::buildEmpty(),
            CheckUserCanAccessProjectStub::build(),
            EventDispatcherStub::withIdentityCallback(),
            RetrieveUserByIdStub::withNoUser(),
        );
        $user->method('isAnonymous')->willReturn(true);

        $result = $permissions->retrieveUserPermissionOnTrackers($user, [$tracker1, $tracker2], TrackerPermissionType::PERMISSION_SUBMIT);
        self::assertEmpty($result->allowed);
        self::assertEqualsCanonicalizing([$tracker1, $tracker2], $result->not_allowed);
    }

    public function testItReturnsAllowedTrackersSubmit(): void
    {
        $user        = $this->createMock(PFUser::class);
        $project     = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker1    = $this->createMock(Tracker::class);
        $tracker2    = $this->createMock(Tracker::class);
        $permissions = new TrackersPermissionsRetriever(
            SearchUserGroupsPermissionOnFieldsStub::buildEmpty(),
            SearchUserGroupsPermissionOnTrackersStub::build()->withSubmitResults([301]),
            SearchUserGroupsPermissionOnArtifactsStub::buildEmpty(),
            CheckUserCanAccessProjectStub::build(),
            EventDispatcherStub::withIdentityCallback(),
            RetrieveUserByIdStub::withNoUser(),
        );
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getUgroups')->willReturn([]);
        $user->method('getId')->willReturn(102);
        $tracker1->method('getId')->willReturn(301);
        $tracker1->method('getProject')->willReturn($project);
        $tracker1->method('userIsAdmin')->willReturn(false);
        $tracker2->method('getId')->willReturn(302);
        $tracker2->method('getProject')->willReturn($project);
        $tracker2->method('userIsAdmin')->willReturn(false);

        $result = $permissions->retrieveUserPermissionOnTrackers($user, [$tracker1, $tracker2], TrackerPermissionType::PERMISSION_SUBMIT);
        self::assertEqualsCanonicalizing([$tracker1], $result->allowed);
        self::assertEqualsCanonicalizing([$tracker2], $result->not_allowed);
    }

    public function testItReturnsAllowedTrackersSubmitFilteredByEvent(): void
    {
        $user        = $this->createMock(PFUser::class);
        $project     = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker1    = $this->createMock(Tracker::class);
        $tracker2    = $this->createMock(Tracker::class);
        $permissions = new TrackersPermissionsRetriever(
            SearchUserGroupsPermissionOnFieldsStub::buildEmpty(),
            SearchUserGroupsPermissionOnTrackersStub::build()->withSubmitResults([301, 302]),
            SearchUserGroupsPermissionOnArtifactsStub::buildEmpty(),
            CheckUserCanAccessProjectStub::build(),
            EventDispatcherStub::withCallback(function (CanSubmitNewArtifact $event) {
                if ($event->getTracker()->getId() !== 301) {
                    $event->disableArtifactSubmission();
                }
                return $event;
            }),
            RetrieveUserByIdStub::withNoUser(),
        );
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getUgroups')->willReturn([]);
        $user->method('getId')->willReturn(102);
        $tracker1->method('getId')->willReturn(301);
        $tracker1->method('getProject')->willReturn($project);
        $tracker1->method('userIsAdmin')->willReturn(false);
        $tracker2->method('getId')->willReturn(302);
        $tracker2->method('getProject')->willReturn($project);
        $tracker2->method('userIsAdmin')->willReturn(false);

        $result = $permissions->retrieveUserPermissionOnTrackers($user, [$tracker1, $tracker2], TrackerPermissionType::PERMISSION_SUBMIT);
        self::assertEqualsCanonicalizing([$tracker1], $result->allowed);
        self::assertEqualsCanonicalizing([$tracker2], $result->not_allowed);
    }

    public function testItReturnsNotAllowedArtifactsForUpdateAnonymous(): void
    {
        $user        = UserTestBuilder::anAnonymousUser()->build();
        $permissions = new TrackersPermissionsRetriever(
            SearchUserGroupsPermissionOnFieldsStub::buildEmpty(),
            SearchUserGroupsPermissionOnTrackersStub::build(),
            SearchUserGroupsPermissionOnArtifactsStub::buildEmpty(),
            CheckUserCanAccessProjectStub::build(),
            EventDispatcherStub::withIdentityCallback(),
            RetrieveUserByIdStub::withNoUser(),
        );
        $artifacts   = [
            ArtifactTestBuilder::anArtifact(1)->build(),
            ArtifactTestBuilder::anArtifact(2)->build(),
            ArtifactTestBuilder::anArtifact(3)->build(),
            ArtifactTestBuilder::anArtifact(4)->build(),
            ArtifactTestBuilder::anArtifact(5)->build(),
        ];

        $result = $permissions->retrieveUserPermissionOnArtifacts($user, $artifacts, ArtifactPermissionType::PERMISSION_UPDATE);
        self::assertEmpty($result->allowed);
        self::assertEqualsCanonicalizing($artifacts, $result->not_allowed);
    }

    public static function provideArtifactPermissionTypes(): iterable
    {
        yield 'Permission VIEW' => [ArtifactPermissionType::PERMISSION_VIEW];
        yield 'Permission UPDATE' => [ArtifactPermissionType::PERMISSION_UPDATE];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideArtifactPermissionTypes')]
    public function testItReturnsAllowedArtifact(ArtifactPermissionType $permission): void
    {
        $user    = $this->createMock(PFUser::class);
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(301);
        $artifact1 = ArtifactTestBuilder::anArtifact(1)->inTracker($tracker)->build();
        $artifact2 = ArtifactTestBuilder::anArtifact(2)->inTracker($tracker)->build();
        $artifact3 = ArtifactTestBuilder::anArtifact(3)->inTracker($tracker)->build();
        $artifacts = [$artifact1, $artifact2, $artifact3];

        $permissions = new TrackersPermissionsRetriever(
            SearchUserGroupsPermissionOnFieldsStub::buildEmpty(),
            SearchUserGroupsPermissionOnTrackersStub::build(),
            SearchUserGroupsPermissionOnArtifactsStub::buildWithResults([1]),
            CheckUserCanAccessProjectStub::build(),
            EventDispatcherStub::withIdentityCallback(),
            RetrieveUserByIdStub::withNoUser(),
        );

        $user->method('isAnonymous')->willReturn(false);
        $user->method('getUgroups')->willReturn([]);
        $user->method('isMemberOfUGroup')->with(1, 101)->willReturn(true);
        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->withId(101)->build());
        $tracker->method('userIsAdmin')->willReturn(false);
        $tracker->method('getGroupId')->willReturn(101);
        $tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn([
            Tracker::PERMISSION_FULL => [1],
        ]);

        $results = $permissions->retrieveUserPermissionOnArtifacts($user, $artifacts, $permission);
        self::assertEqualsCanonicalizing([$artifact1], $results->allowed);
        self::assertEqualsCanonicalizing([$artifact2, $artifact3], $results->not_allowed);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideArtifactPermissionTypes')]
    public function testItAllowAllArtifactIfUserIsAdmin(ArtifactPermissionType $permission): void
    {
        $user    = $this->createMock(PFUser::class);
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(301);
        $artifact1 = ArtifactTestBuilder::anArtifact(1)->inTracker($tracker)->build();
        $artifact2 = ArtifactTestBuilder::anArtifact(2)->inTracker($tracker)->build();
        $artifact3 = ArtifactTestBuilder::anArtifact(3)->inTracker($tracker)->build();
        $artifacts = [$artifact1, $artifact2, $artifact3];

        $permissions = new TrackersPermissionsRetriever(
            SearchUserGroupsPermissionOnFieldsStub::buildEmpty(),
            SearchUserGroupsPermissionOnTrackersStub::build(),
            SearchUserGroupsPermissionOnArtifactsStub::buildWithResults([1]),
            CheckUserCanAccessProjectStub::build(),
            EventDispatcherStub::withIdentityCallback(),
            RetrieveUserByIdStub::withNoUser(),
        );

        $user->method('isAnonymous')->willReturn(false);
        $user->method('getUgroups')->willReturn([]);
        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->withId(101)->build());
        $tracker->method('userIsAdmin')->willReturn(true);
        $tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn([]);

        $results = $permissions->retrieveUserPermissionOnArtifacts($user, $artifacts, $permission);
        self::assertEqualsCanonicalizing($artifacts, $results->allowed);
        self::assertEmpty($results->not_allowed);
    }
}
