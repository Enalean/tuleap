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

use PFUser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_UserWithReadAllPermission;
use Tracker_Workflow_WorkflowUser;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CheckProjectAccessStub;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Tracker\Artifact\CanSubmitNewArtifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Permission\SearchUserGroupsPermissionOnArtifactsStub;
use Tuleap\Tracker\Test\Stub\Permission\SearchUserGroupsPermissionOnFieldsStub;
use Tuleap\Tracker\Test\Stub\Permission\SearchUserGroupsPermissionOnTrackersStub;
use Tuleap\Tracker\Tracker;
use Tuleap\User\TuleapFunctionsUser;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackersPermissionsRetrieverTest extends TestCase
{
    private SearchUserGroupsPermissionOnFieldsStub|MockObject $fields_dao;
    private SearchUserGroupsPermissionOnTrackersStub $trackers_dao;
    private SearchUserGroupsPermissionOnArtifactsStub $artifacts_dao;
    private CheckProjectAccessStub $project_access;
    private EventDispatcherStub $event_dispatcher;

    protected function setUp(): void
    {
        $this->fields_dao       = SearchUserGroupsPermissionOnFieldsStub::buildEmpty();
        $this->trackers_dao     = SearchUserGroupsPermissionOnTrackersStub::build();
        $this->artifacts_dao    = SearchUserGroupsPermissionOnArtifactsStub::buildEmpty();
        $this->project_access   = CheckProjectAccessStub::withPrivateProjectWithoutAccess();
        $this->event_dispatcher = EventDispatcherStub::withIdentityCallback();
    }

    private function getRetriever(): TrackersPermissionsRetriever
    {
        return new TrackersPermissionsRetriever(
            $this->fields_dao,
            $this->trackers_dao,
            $this->artifacts_dao,
            $this->project_access,
            $this->event_dispatcher,
            RetrieveUserByIdStub::withNoUser(),
        );
    }

    public function testItReturnsAllowedFields(): void
    {
        $user             = $this->createMock(PFUser::class);
        $project          = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker          = TrackerTestBuilder::aTracker()->withId(201)->withProject($project)->build();
        $field1           = IntegerFieldBuilder::anIntField(301)->inTracker($tracker)->build();
        $field2           = IntegerFieldBuilder::anIntField(302)->inTracker($tracker)->build();
        $field3           = IntegerFieldBuilder::anIntField(303)->inTracker($tracker)->build();
        $field4           = IntegerFieldBuilder::anIntField(304)->inTracker($tracker)->build();
        $this->fields_dao = SearchUserGroupsPermissionOnFieldsStub::buildWithResults([301, 303, 304]);
        $user->method('getUgroups')->willReturn([]);

        $result = $this->getRetriever()->retrieveUserPermissionOnFields(
            $user,
            [$field1, $field2, $field3, $field4],
            FieldPermissionType::PERMISSION_READ
        );

        self::assertEqualsCanonicalizing([$field1, $field3, $field4], $result->allowed);
        self::assertEqualsCanonicalizing([$field2], $result->not_allowed);
    }

    public function testItReturnsAllowedReadFieldsEvenIfOnlyUpdatePermission(): void
    {
        $user             = $this->createMock(PFUser::class);
        $project          = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker          = TrackerTestBuilder::aTracker()->withId(201)->withProject($project)->build();
        $field1           = IntegerFieldBuilder::anIntField(301)->inTracker($tracker)->build();
        $field2           = IntegerFieldBuilder::anIntField(302)->inTracker($tracker)->build();
        $field3           = IntegerFieldBuilder::anIntField(303)->inTracker($tracker)->build();
        $field4           = IntegerFieldBuilder::anIntField(304)->inTracker($tracker)->build();
        $this->fields_dao = $this->createMock(SearchUserGroupsPermissionOnFields::class);
        $user->method('getUgroups')->willReturn([]);
        $this->fields_dao->expects($this->exactly(2))->method('searchUserGroupsPermissionOnFields')
            ->willReturnCallback(static fn(array $user_groups_id, array $fields_id, FieldPermissionType $permission) => match ($permission) {
                FieldPermissionType::PERMISSION_READ   => [],
                FieldPermissionType::PERMISSION_UPDATE => [301, 303, 304],
            });

        $result = $this->getRetriever()->retrieveUserPermissionOnFields(
            $user,
            [$field1, $field2, $field3, $field4],
            FieldPermissionType::PERMISSION_READ
        );

        self::assertEqualsCanonicalizing([$field1, $field3, $field4], $result->allowed);
        self::assertEqualsCanonicalizing([$field2], $result->not_allowed);
    }

    public static function provideSpecialUsers(): iterable
    {
        yield 'Tracker_Workflow_WorkflowUser' => [new Tracker_Workflow_WorkflowUser()];
        yield 'TuleapFunctionsUser' => [new TuleapFunctionsUser()];
        yield 'Tracker_UserWithReadAllPermission' => [new Tracker_UserWithReadAllPermission(UserTestBuilder::buildWithDefaults())];
    }

    #[DataProvider('provideSpecialUsers')]
    public function testItAllowsAllFieldsIfUserIsSpecial(PFUser $user): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(201)->withProject($project)->build();
        $fields  = [
            IntegerFieldBuilder::anIntField(301)->inTracker($tracker)->build(),
            IntegerFieldBuilder::anIntField(302)->inTracker($tracker)->build(),
            IntegerFieldBuilder::anIntField(303)->inTracker($tracker)->build(),
            IntegerFieldBuilder::anIntField(304)->inTracker($tracker)->build(),
        ];

        $result = $this->getRetriever()->retrieveUserPermissionOnFields(
            $user,
            $fields,
            FieldPermissionType::PERMISSION_READ
        );

        self::assertEqualsCanonicalizing($fields, $result->allowed);
        self::assertEmpty($result->not_allowed);
    }

    public function testItReturnsAllowedTrackersView(): void
    {
        $user                 = $this->createMock(PFUser::class);
        $project              = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker1             = $this->createMock(Tracker::class);
        $tracker2             = $this->createMock(Tracker::class);
        $this->trackers_dao   = SearchUserGroupsPermissionOnTrackersStub::build()->withViewResults([301]);
        $this->project_access = CheckProjectAccessStub::withValidAccess();
        $user->method('getUgroups')->willReturn([]);
        $user->method('getId')->willReturn(102);
        $tracker1->method('getId')->willReturn(301);
        $tracker1->method('getProject')->willReturn($project);
        $tracker1->method('userIsAdmin')->willReturn(false);
        $tracker2->method('getId')->willReturn(302);
        $tracker2->method('getProject')->willReturn($project);
        $tracker2->method('userIsAdmin')->willReturn(false);

        $result = $this->getRetriever()->retrieveUserPermissionOnTrackers(
            $user,
            [$tracker1, $tracker2],
            TrackerPermissionType::PERMISSION_VIEW
        );

        self::assertEqualsCanonicalizing([$tracker1], $result->allowed);
        self::assertEqualsCanonicalizing([$tracker2], $result->not_allowed);
    }

    public static function provideTrackerPermissionTypes(): iterable
    {
        yield 'Permission VIEW' => [TrackerPermissionType::PERMISSION_VIEW];
        yield 'Permission SUBMIT' => [TrackerPermissionType::PERMISSION_SUBMIT];
    }

    #[DataProvider('provideTrackerPermissionTypes')]
    public function testItAllowsAllTrackersIfUserIsAdmin(TrackerPermissionType $permission): void
    {
        $this->project_access = CheckProjectAccessStub::withValidAccess();
        $tracker              = $this->createMock(Tracker::class);
        $trackers             = [$tracker, $tracker, $tracker, $tracker, $tracker];
        $user                 = $this->createMock(PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getUgroups')->willReturn([]);
        $user->method('getId')->willReturn(101);
        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->withId(101)->build());
        $tracker->method('userIsAdmin')->with($user)->willReturn(true);
        $tracker->method('getId')->willReturn(301);

        $result = $this->getRetriever()->retrieveUserPermissionOnTrackers($user, $trackers, $permission);

        self::assertEqualsCanonicalizing($trackers, $result->allowed);
        self::assertEmpty($result->not_allowed);
    }

    #[DataProvider('provideTrackerPermissionTypes')]
    public function testItForbidsTrackersWhenProjectCannotBeViewed(TrackerPermissionType $permission): void
    {
        $this->project_access = CheckProjectAccessStub::withPrivateProjectWithoutAccess();
        $tracker              = $this->createMock(Tracker::class);
        $trackers             = [$tracker, $tracker];
        $user                 = $this->createMock(PFUser::class);
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getUgroups')->willReturn([]);
        $user->method('getId')->willReturn(101);
        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->withId(101)->build());
        $tracker->method('userIsAdmin')->with($user)->willReturn(false);
        $tracker->method('getId')->willReturn(301);

        $result = $this->getRetriever()->retrieveUserPermissionOnTrackers($user, $trackers, $permission);

        self::assertEmpty($result->allowed);
        self::assertEqualsCanonicalizing($trackers, $result->not_allowed);
    }

    public function testItReturnsNotAllowedTrackersSubmitIfUserAnonymous(): void
    {
        $user                 = UserTestBuilder::anAnonymousUser()->build();
        $tracker1             = TrackerTestBuilder::aTracker()->withId(201)->build();
        $tracker2             = TrackerTestBuilder::aTracker()->withId(202)->build();
        $this->project_access = CheckProjectAccessStub::withValidAccess();

        $result = $this->getRetriever()->retrieveUserPermissionOnTrackers(
            $user,
            [$tracker1, $tracker2],
            TrackerPermissionType::PERMISSION_SUBMIT
        );

        self::assertEmpty($result->allowed);
        self::assertEqualsCanonicalizing([$tracker1, $tracker2], $result->not_allowed);
    }

    public function testItReturnsAllowedTrackersSubmit(): void
    {
        $user                 = $this->createMock(PFUser::class);
        $project              = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker1             = $this->createMock(Tracker::class);
        $tracker2             = $this->createMock(Tracker::class);
        $this->trackers_dao   = SearchUserGroupsPermissionOnTrackersStub::build()->withSubmitResults([301]);
        $this->project_access = CheckProjectAccessStub::withValidAccess();
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getUgroups')->willReturn([]);
        $user->method('getId')->willReturn(102);
        $tracker1->method('getId')->willReturn(301);
        $tracker1->method('getProject')->willReturn($project);
        $tracker1->method('userIsAdmin')->willReturn(false);
        $tracker2->method('getId')->willReturn(302);
        $tracker2->method('getProject')->willReturn($project);
        $tracker2->method('userIsAdmin')->willReturn(false);

        $result = $this->getRetriever()->retrieveUserPermissionOnTrackers(
            $user,
            [$tracker1, $tracker2],
            TrackerPermissionType::PERMISSION_SUBMIT
        );

        self::assertEqualsCanonicalizing([$tracker1], $result->allowed);
        self::assertEqualsCanonicalizing([$tracker2], $result->not_allowed);
    }

    public function testItReturnsAllowedTrackersSubmitFilteredByEvent(): void
    {
        $user                   = $this->createMock(PFUser::class);
        $project                = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker1               = $this->createMock(Tracker::class);
        $tracker2               = $this->createMock(Tracker::class);
        $this->trackers_dao     = SearchUserGroupsPermissionOnTrackersStub::build()->withSubmitResults([301, 302]);
        $this->project_access   = CheckProjectAccessStub::withValidAccess();
        $this->event_dispatcher = EventDispatcherStub::withCallback(static function (CanSubmitNewArtifact $event) {
            if ($event->getTracker()->getId() !== 301) {
                $event->disableArtifactSubmission();
            }
            return $event;
        });
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getUgroups')->willReturn([]);
        $user->method('getId')->willReturn(102);
        $tracker1->method('getId')->willReturn(301);
        $tracker1->method('getProject')->willReturn($project);
        $tracker1->method('userIsAdmin')->willReturn(false);
        $tracker2->method('getId')->willReturn(302);
        $tracker2->method('getProject')->willReturn($project);
        $tracker2->method('userIsAdmin')->willReturn(false);

        $result = $this->getRetriever()->retrieveUserPermissionOnTrackers(
            $user,
            [$tracker1, $tracker2],
            TrackerPermissionType::PERMISSION_SUBMIT
        );

        self::assertEqualsCanonicalizing([$tracker1], $result->allowed);
        self::assertEqualsCanonicalizing([$tracker2], $result->not_allowed);
    }

    public function testItReturnsNotAllowedArtifactsForUpdateAnonymous(): void
    {
        $user      = UserTestBuilder::anAnonymousUser()->build();
        $artifacts = [
            ArtifactTestBuilder::anArtifact(1)->build(),
            ArtifactTestBuilder::anArtifact(2)->build(),
            ArtifactTestBuilder::anArtifact(3)->build(),
            ArtifactTestBuilder::anArtifact(4)->build(),
            ArtifactTestBuilder::anArtifact(5)->build(),
        ];

        $result = $this->getRetriever()->retrieveUserPermissionOnArtifacts(
            $user,
            $artifacts,
            ArtifactPermissionType::PERMISSION_UPDATE
        );

        self::assertEmpty($result->allowed);
        self::assertEqualsCanonicalizing($artifacts, $result->not_allowed);
    }

    public static function provideArtifactPermissionTypes(): iterable
    {
        yield 'Permission VIEW' => [ArtifactPermissionType::PERMISSION_VIEW];
        yield 'Permission UPDATE' => [ArtifactPermissionType::PERMISSION_UPDATE];
    }

    #[DataProvider('provideArtifactPermissionTypes')]
    public function testItReturnsAllowedArtifact(ArtifactPermissionType $permission): void
    {
        $user    = $this->createMock(PFUser::class);
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(301);
        $artifact1           = ArtifactTestBuilder::anArtifact(1)->inTracker($tracker)->build();
        $artifact2           = ArtifactTestBuilder::anArtifact(2)->inTracker($tracker)->build();
        $artifact3           = ArtifactTestBuilder::anArtifact(3)->inTracker($tracker)->build();
        $artifacts           = [$artifact1, $artifact2, $artifact3];
        $this->artifacts_dao = SearchUserGroupsPermissionOnArtifactsStub::buildWithResults([1]);

        $user->method('isAnonymous')->willReturn(false);
        $user->method('getUgroups')->willReturn([]);
        $user->method('isMemberOfUGroup')->with(1, 101)->willReturn(true);
        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->withId(101)->build());
        $tracker->method('userIsAdmin')->willReturn(false);
        $tracker->method('getGroupId')->willReturn(101);
        $tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn([
            Tracker::PERMISSION_FULL => [1],
        ]);

        $results = $this->getRetriever()->retrieveUserPermissionOnArtifacts($user, $artifacts, $permission);

        self::assertEqualsCanonicalizing([$artifact1], $results->allowed);
        self::assertEqualsCanonicalizing([$artifact2, $artifact3], $results->not_allowed);
    }

    #[DataProvider('provideArtifactPermissionTypes')]
    public function testItAllowsAllArtifactsIfUserIsAdmin(ArtifactPermissionType $permission): void
    {
        $user    = $this->createMock(PFUser::class);
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(301);
        $artifact1 = ArtifactTestBuilder::anArtifact(1)->inTracker($tracker)->build();
        $artifact2 = ArtifactTestBuilder::anArtifact(2)->inTracker($tracker)->build();
        $artifact3 = ArtifactTestBuilder::anArtifact(3)->inTracker($tracker)->build();
        $artifacts = [$artifact1, $artifact2, $artifact3];
        $user->method('isAnonymous')->willReturn(false);
        $user->method('getUgroups')->willReturn([]);
        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->withId(101)->build());
        $tracker->method('userIsAdmin')->willReturn(true);
        $tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn([]);

        $results = $this->getRetriever()->retrieveUserPermissionOnArtifacts($user, $artifacts, $permission);

        self::assertEqualsCanonicalizing($artifacts, $results->allowed);
        self::assertEmpty($results->not_allowed);
    }
}
