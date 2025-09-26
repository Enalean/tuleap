<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PermissionSerializerTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private Tracker_Permission_PermissionsSerializer $serializer;
    private int $project_id                    = 333;
    private int $marketing_ugroup_id           = 115;
    private int $support_ugroup_id             = 114;
    private int $summary_field_id              = 352;
    private string $support_ugroup_literalize  = '@ug_114';
    private string $project_members_literalize = '@acme_project_members';
    private string $project_admin_literalize   = '@acme_project_admin';
    private PFUser $support_member_only;
    private PFUser $support_and_project_member;
    private PFUser $user_project_member;
    private PFUser $user_not_project_member;
    private PFUser $marketing_member_only;
    private Tracker&MockObject $tracker;
    private Artifact $artifact;
    private Project $project;
    private Tracker_Permission_PermissionRetrieveAssignee&MockObject $assignee_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withId($this->project_id)->withUnixName('acme')->build();

        $this->setUpUsers();

        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker->method('getProject')->willReturn($this->project);

        $this->assignee_retriever = $this->createMock(\Tracker_Permission_PermissionRetrieveAssignee::class);

        $this->serializer = new Tracker_Permission_PermissionsSerializer($this->assignee_retriever);
    }

    private function setUpUsers(): void
    {
        $this->user_project_member        = $this->getUserWithGroups([ProjectUGroup::PROJECT_MEMBERS]);
        $this->user_not_project_member    = $this->getUserWithGroups([]);
        $this->support_member_only        = $this->getUserWithGroups([$this->support_ugroup_id]);
        $this->marketing_member_only      = $this->getUserWithGroups([$this->marketing_ugroup_id]);
        $this->support_and_project_member = $this->getUserWithGroups(
            [ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id]
        );
    }

    private function getUserWithGroups(array $ugroup_ids): PFUser&MockObject
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('getUgroups')->with($this->project_id, $this->anything())->willReturn($ugroup_ids);

        return $user;
    }

    private function assertTrackerUGroupIdsWithoutAdminsEquals(Artifact $artifact, array $expected_values): void
    {
        $this->assertEquals(
            $expected_values,
            array_values($this->serializer->getUserGroupsThatCanViewTracker($artifact))
        );
    }

    private function assertTrackerUGroupIdsEquals(Artifact $artifact, array $expected_values): void
    {
        $this->assertTrackerUGroupIdsWithoutAdminsEquals(
            $artifact,
            array_merge([ProjectUGroup::PROJECT_ADMIN], $expected_values)
        );
    }

    private function assertArtifactUGroupIdsWithoutAdminsEquals(
        Artifact $artifact,
        array $expected_values,
    ): void {
        $this->assertEquals(
            $expected_values,
            array_values($this->serializer->getUserGroupsThatCanViewArtifact($artifact))
        );
    }

    private function assertArtifactUGroupIdsEquals(Artifact $artifact, array $expected_values): void
    {
        if ($expected_values) {
            $expected_values = array_merge([ProjectUGroup::PROJECT_ADMIN], $expected_values);
        }
        $this->assertArtifactUGroupIdsWithoutAdminsEquals(
            $artifact,
            $expected_values
        );
    }

    private function assertSubmitterOnlyUGroupIdsEquals(Artifact $artifact, $expected_value): void
    {
        $this->assertEquals(
            $expected_value,
            $this->serializer->getLiteralizedUserGroupsSubmitterOnly($artifact)
        );
    }

    private function assertFieldsPermissionUGroupIdsEquals(Artifact $artifact, array $expected_value): void
    {
        $this->assertEquals(
            $expected_value,
            $this->serializer->getLiteralizedUserGroupsThatCanViewTrackerFields($artifact)
        );
    }

    private function assertTrackerUGroupsEquals(Tracker $tracker, $expected_value): void
    {
        $this->assertEquals(
            $expected_value,
            $this->serializer->getLiteralizedAllUserGroupsThatCanViewTracker($tracker)
        );
    }

    private function anArtifact($submitter, array $assignees, array $authorized_ugroups): Artifact
    {
        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getSubmittedByUser')->willReturn($submitter);
        $artifact->method('getTracker')->willReturn($this->tracker);
        $artifact->method('getAuthorizedUGroups')->willReturn($authorized_ugroups);

        $this->assignee_retriever->method('getAssignees')->with($artifact)->willReturn($assignees);
        return $artifact;
    }

    public function testProjectAdminAlwaysReturnsProjectAdminWhenAllUsersHaveAccessToAllArtifacts(): void
    {
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn([]);

        $this->assertTrackerUGroupIdsWithoutAdminsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [ProjectUGroup::PROJECT_ADMIN]
        );
    }

    public function testProjectAdminReturnsAnonymousWhenAllUsersHaveAccessToAllArtifacts(): void
    {
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')
            ->willReturn([Tracker::PERMISSION_FULL => [ProjectUGroup::ANONYMOUS]]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [ProjectUGroup::ANONYMOUS]
        );
    }

    public function testProjectAdminReturnsRegisteredUsersWhenTheyAreGranted(): void
    {
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')
            ->willReturn([Tracker::PERMISSION_FULL => [ProjectUGroup::REGISTERED]]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [
                ProjectUGroup::REGISTERED,
            ]
        );
    }

    public function testProjectAdminReturnsProjectMemberWhenTheyAreGranted(): void
    {
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')
            ->willReturn([Tracker::PERMISSION_FULL => [ProjectUGroup::PROJECT_MEMBERS]]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [ProjectUGroup::PROJECT_MEMBERS]
        );
    }

    public function testProjectAdminReturnsOneDynamicUserGroupWhenTheyAreGranted(): void
    {
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')
            ->willReturn([Tracker::PERMISSION_FULL => [$this->support_ugroup_id]]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [$this->support_ugroup_id]
        );
    }

    public function testProjectAdminReturnsDynamicUsersAndProjectMembersGroupWhenTheyAreGranted(): void
    {
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')
            ->willReturn([Tracker::PERMISSION_FULL => [$this->support_ugroup_id, ProjectUGroup::PROJECT_MEMBERS]]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [
                $this->support_ugroup_id,
                ProjectUGroup::PROJECT_MEMBERS,
            ]
        );
    }

    public function testTrackerAdminReturnsProjectMemberWhenTheyAreGranted(): void
    {
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')
            ->willReturn([Tracker::PERMISSION_ADMIN => [ProjectUGroup::PROJECT_MEMBERS]]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [ProjectUGroup::PROJECT_MEMBERS]
        );
    }

    public function testTrackerAdminReturnsOneDynamicUserGroupWhenTheyAreGranted(): void
    {
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')
            ->willReturn([Tracker::PERMISSION_ADMIN => [$this->support_ugroup_id]]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [$this->support_ugroup_id]
        );
    }

    public function testTrackerAdminReturnsDynamicUsersAndProjectMembersGroupWhenTheyAreGranted(): void
    {
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')
            ->willReturn(
                [
                    Tracker::PERMISSION_ADMIN => [$this->support_ugroup_id, ProjectUGroup::PROJECT_MEMBERS],
                ]
            );

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [
                $this->support_ugroup_id,
                ProjectUGroup::PROJECT_MEMBERS,
            ]
        );
    }

    public function testProjectMemberReturnsProjectMembersWhenTheArtifactIsSubmittedByAMemberOfTheProject(): void
    {
        $this->mockSubmitter([ProjectUGroup::PROJECT_MEMBERS]);
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_project_member, [], []),
            [ProjectUGroup::PROJECT_MEMBERS]
        );
    }

    public function testProjectMemberReturnsEmptyArrayWhenArtifactIsSubmittedByNonProjectMember(): void
    {
        $this->mockSubmitter([ProjectUGroup::PROJECT_MEMBERS]);
        $this->assertTrackerUGroupIdsEquals($this->anArtifact($this->user_not_project_member, [], []), []);
    }

    public function testSubmittedByTwoGroupsReturnsProjectMembersWhenTheArtifactIsSubmittedByAMemberOfTheProject(): void
    {
        $this->mockSubmitter([ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_project_member, [], []),
            [ProjectUGroup::PROJECT_MEMBERS]
        );
    }

    public function testSubmittedByTwoGroupsReturnsSupportMembersWhenTheArtifactIsSubmittedByAMemberOfSupportTeam(): void
    {
        $this->mockSubmitter([ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->support_member_only, [], []),
            [$this->support_ugroup_id]
        );
    }

    public function testSubmittedByTwoGroupsReturnsSupportMembersAndProjectMembersWhenTheArtifactIsSubmittedByAMemberOfBothTeams(): void
    {
        $this->mockSubmitter([ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->support_and_project_member, [], []),
            [
                ProjectUGroup::PROJECT_MEMBERS,
                $this->support_ugroup_id,
            ]
        );
    }

    public function testAssignedToOneGroupOnlyReturnsProjectMembersWhenTheArtifactIsAssignedToAMemberOfTheProject(): void
    {
        $this->mockAssignee([ProjectUGroup::PROJECT_MEMBERS]);
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [$this->user_project_member], []),
            [ProjectUGroup::PROJECT_MEMBERS]
        );
    }

    public function testAssignedToOneGroupOnlyReturnsEmptyArrayWhenTheArtifactIsAssignedToANonProjectMember(): void
    {
        $this->mockAssignee([ProjectUGroup::PROJECT_MEMBERS]);
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [$this->user_not_project_member], []),
            []
        );
    }

    public function testAssignedToOneGroupOnlyReturnsEmptyArrayWhenTheArtifactIsAssignedToAMemberOfAnotherUGroup(): void
    {
        $this->mockAssignee([ProjectUGroup::PROJECT_MEMBERS]);
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [$this->support_member_only], []),
            []
        );
    }

    public function testAssignedToTwoReturnsProjectMembersWhenTheArtifactIsAssignedToAMemberOfTheProject(): void
    {
        $this->mockAssignee([ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id]);
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [$this->user_project_member], []),
            [ProjectUGroup::PROJECT_MEMBERS]
        );
    }

    public function testAssignedToTwoReturnsSupportMembersWhenTheArtifactIsAssignedToAMemberOfSupportTeam(): void
    {
        $this->mockAssignee([ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id]);
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [$this->support_member_only], []),
            [$this->support_ugroup_id]
        );
    }

    public function testAssignedToTwoReturnsSupportMembersAndProjectMembersWhenTheArtifactIsAssignedToAMemberOfBothTeams(): void
    {
        $this->mockAssignee([ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id]);
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [$this->support_and_project_member], []),
            [
                ProjectUGroup::PROJECT_MEMBERS,
                $this->support_ugroup_id,
            ]
        );
    }

    public function testAssignedToTwoPeopleReturnsSupportAndMarketingTeamsWhenTheArtifactIsAssignedToOnePeopleOfEachGroup(): void
    {
        $this->mockAssignee([$this->support_ugroup_id, $this->marketing_ugroup_id]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact(
                $this->user_not_project_member,
                [$this->support_member_only, $this->marketing_member_only],
                []
            ),
            [
                $this->support_ugroup_id,
                $this->marketing_ugroup_id,
            ]
        );
    }

    public function testAssignedToTwoReturnsSupportTeamWhenTheArtifactIsAssignedToPeopleFromProjectAndSupport(): void
    {
        $this->mockAssignee([$this->support_ugroup_id, $this->marketing_ugroup_id]);
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact(
                $this->user_not_project_member,
                [$this->user_project_member, $this->support_member_only],
                []
            ),
            [
                $this->support_ugroup_id,
            ]
        );
    }

    public function testAssignedToTwoReturnsNobodyWhenTheArtifactIsAssignedToPeopleFromOtherTeams(): void
    {
        $this->mockAssignee([$this->support_ugroup_id, $this->marketing_ugroup_id]);
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact(
                $this->user_not_project_member,
                [$this->user_project_member, $this->user_not_project_member],
                []
            ),
            []
        );
    }

    public function testSubmittedByOrAssignedToReturnsProjectMembersWhenAProjectMemberSubmittedTheArtifact(): void
    {
        $this->mockSubmitterAndAssignee([ProjectUGroup::PROJECT_MEMBERS], [ProjectUGroup::PROJECT_MEMBERS]);
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_project_member, [], []),
            [ProjectUGroup::PROJECT_MEMBERS]
        );
    }

    public function testSubmittedByOrAssignedToReturnsProjectMembersWhenAProjectMemberIsAssignedToTheArtifact(): void
    {
        $this->mockSubmitterAndAssignee([ProjectUGroup::PROJECT_MEMBERS], [ProjectUGroup::PROJECT_MEMBERS]);
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->support_member_only, [$this->user_project_member], []),
            [ProjectUGroup::PROJECT_MEMBERS]
        );
    }

    public function testSubmittedByOrAssignedToReturnsEmptyWhenNoProjectMembersAreAssignedNorSubmitterToTheArtifact(): void
    {
        $this->mockSubmitterAndAssignee([ProjectUGroup::PROJECT_MEMBERS], [ProjectUGroup::PROJECT_MEMBERS]);
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->support_member_only, [$this->user_not_project_member], []),
            []
        );
    }

    public function testSubmittedByOrAssignedToTwoGroupsReturnsSupportTeamWhenSubmittedBySupportMember(): void
    {
        $this->mockSubmitterAndAssignee(
            [ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id],
            [ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id]
        );
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->support_member_only, [], []),
            [$this->support_ugroup_id]
        );
    }

    public function testSubmittedByOrAssignedToTwoGroupsReturnsSupportTeamWhenAssignedToSupportTeam(): void
    {
        $this->mockSubmitterAndAssignee(
            [ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id],
            [ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id]
        );
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [$this->support_member_only], []),
            [$this->support_ugroup_id]
        );
    }

    public function testSubmittedByOrAssignedToTwoGroupsReturnsBothWhenSubmittedByProjectMemberAndAssignedToSupportTeam(): void
    {
        $this->mockSubmitterAndAssignee(
            [ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id],
            [ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id]
        );
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_project_member, [$this->support_member_only], []),
            [ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id]
        );
    }

    public function testSubmittedByOrAssignedToTwoGroupsReturnsEmptyArrayWhenNeitherGroupsAreInvolved(): void
    {
        $this->mockSubmitterAndAssignee(
            [ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id],
            [ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id]
        );
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [$this->marketing_member_only], []),
            []
        );
    }

    public function testSeveralPermissionsHasAnExternaSubmitter(): void
    {
        $this->mockSeveralPermissions(
            [ProjectUGroup::PROJECT_MEMBERS, $this->marketing_ugroup_id],
            [ProjectUGroup::PROJECT_MEMBERS],
            [$this->support_ugroup_id]
        );
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [$this->support_ugroup_id]
        );
    }

    public function testSeveralPermissionsHasAnExternalSubmitterAndProjectMemberAssignee(): void
    {
        $this->mockSeveralPermissions(
            [ProjectUGroup::PROJECT_MEMBERS, $this->marketing_ugroup_id],
            [ProjectUGroup::PROJECT_MEMBERS],
            [$this->support_ugroup_id]
        );
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [$this->user_project_member], []),
            [$this->support_ugroup_id, ProjectUGroup::PROJECT_MEMBERS]
        );
    }

    public function testSeveralPermissionsHasAnExternalSubmitterAndMarketingAssignee(): void
    {
        $this->mockSeveralPermissions(
            [ProjectUGroup::PROJECT_MEMBERS, $this->marketing_ugroup_id],
            [ProjectUGroup::PROJECT_MEMBERS],
            [$this->support_ugroup_id]
        );
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [$this->marketing_member_only], []),
            [$this->support_ugroup_id]
        );
    }

    public function testSeveralPermissionsHasAMarketingSubmitterAndProjectMemberAssignee(): void
    {
        $this->mockSeveralPermissions(
            [ProjectUGroup::PROJECT_MEMBERS, $this->marketing_ugroup_id],
            [ProjectUGroup::PROJECT_MEMBERS],
            [$this->support_ugroup_id]
        );
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->marketing_member_only, [$this->user_project_member], []),
            [$this->support_ugroup_id, $this->marketing_ugroup_id, ProjectUGroup::PROJECT_MEMBERS]
        );
    }

    public function testSeveralPermissionsHasAMarketingSubmitterAndMultiTeamAssignee(): void
    {
        $this->mockSeveralPermissions(
            [ProjectUGroup::PROJECT_MEMBERS, $this->marketing_ugroup_id],
            [ProjectUGroup::PROJECT_MEMBERS],
            [$this->support_ugroup_id]
        );
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->marketing_member_only, [$this->support_and_project_member], []),
            [$this->support_ugroup_id, $this->marketing_ugroup_id, ProjectUGroup::PROJECT_MEMBERS]
        );
    }

    public function testArtifactPermissionsReturnsArtifactPermissionsSelected(): void
    {
        $this->artifact = $this->anArtifact($this->marketing_ugroup_id, [], [$this->support_ugroup_id]);

        $this->assertArtifactUGroupIdsEquals(
            $this->artifact,
            [
                $this->support_ugroup_id,
            ]
        );
    }

    public function testArtifactPermissionsReturnsNothingIfArtifactPermissionsAreNotSelected(): void
    {
        $this->artifact = $this->anArtifact($this->marketing_ugroup_id, [], []);

        $this->assertArtifactUGroupIdsEquals(
            $this->artifact,
            []
        );
    }

    public function testSubmitterOnlyPermissionPermissionReturnsSubmitterOnlyUGroupsIds(): void
    {
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn(
            [Tracker::PERMISSION_SUBMITTER_ONLY => [$this->support_ugroup_id]]
        );

        $this->tracker->method('userIsAdmin')->willReturn(true);

        $this->artifact = $this->anArtifact($this->marketing_ugroup_id, [], []);

        $this->assertSubmitterOnlyUGroupIdsEquals(
            $this->artifact,
            [$this->support_ugroup_literalize]
        );
    }

    public function testFieldPermissionReturnsArtifactFieldsPermissionsWithoutPermissionSubmit(): void
    {
        $this->tracker->method('getFieldsAuthorizedUgroupsByPermissionType')->willReturn(
            [
                $this->summary_field_id =>
                    [
                        TrackerField::PERMISSION_READ   => [$this->support_ugroup_id],
                        TrackerField::PERMISSION_SUBMIT => [ProjectUGroup::PROJECT_ADMIN],
                        TrackerField::PERMISSION_UPDATE => [ProjectUGroup::PROJECT_MEMBERS],
                    ],
            ]
        );

        $this->artifact = $this->anArtifact($this->marketing_ugroup_id, [], []);

        $this->assertFieldsPermissionUGroupIdsEquals(
            $this->artifact,
            [$this->summary_field_id => [$this->support_ugroup_literalize, $this->project_members_literalize]]
        );
    }

    public function testGroupsPermissionsReturnsAllGroupsCanViewTracker(): void
    {
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn(
            [
                Tracker::PERMISSION_FULL           => [ProjectUGroup::PROJECT_ADMIN],
                Tracker::PERMISSION_ADMIN          => [ProjectUGroup::PROJECT_ADMIN],
                Tracker::PERMISSION_SUBMITTER      => [ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::PROJECT_MEMBERS],
                Tracker::PERMISSION_ASSIGNEE       => [ProjectUGroup::PROJECT_ADMIN, $this->support_ugroup_id],
                Tracker::PERMISSION_SUBMITTER_ONLY => [ProjectUGroup::PROJECT_ADMIN],
            ]
        );

        $this->assertTrackerUGroupsEquals(
            $this->tracker,
            [
                $this->project_admin_literalize,
                $this->project_members_literalize,
                $this->support_ugroup_literalize,
            ]
        );
    }

    private function mockSubmitter(array $submitter): void
    {
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')
            ->willReturn([Tracker::PERMISSION_SUBMITTER => $submitter]);
    }

    private function mockAssignee(array $assignee): void
    {
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn(
            [Tracker::PERMISSION_ASSIGNEE => $assignee]
        );
    }

    private function mockSubmitterAndAssignee(array $submitter, array $assignee): void
    {
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn(
            [
                Tracker::PERMISSION_SUBMITTER => $submitter,
                Tracker::PERMISSION_ASSIGNEE  => $assignee,
            ]
        );
    }

    private function mockSeveralPermissions(array $submitter, array $assignee, array $full): void
    {
        $this->tracker->method('getAuthorizedUgroupsByPermissionType')->willReturn(
            [
                Tracker::PERMISSION_FULL      => $full,
                Tracker::PERMISSION_SUBMITTER => $submitter,
                Tracker::PERMISSION_ASSIGNEE  => $assignee,
            ]
        );
    }
}
