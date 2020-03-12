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

use PHPUnit\Framework\TestCase;

final class PermissionSerializerTest extends TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Tracker_Permission_PermissionsSerializer
     */
    private $serializer;
    /**
     * @var int
     */
    private $project_id = 333;
    /**
     * @var int
     */
    private $marketing_ugroup_id = 115;
    /**
     * @var int
     */
    private $support_ugroup_id = 114;
    /**
     * @var int
     */
    private $summary_field_id = 352;
    /**
     * @var string
     */
    private $support_ugroup_literalize = '@ug_114';
    /**
     * @var string
     */
    private $project_members_literalize = '@_project_members';
    /**
     * @var string
     */
    private $project_admin_literalize = '@_project_admin';
    /**
     * @var \Mockery\LegacyMockInterface|PFUser
     */
    private $support_member_only;
    /**
     * @var \Mockery\LegacyMockInterface|PFUser
     */
    private $support_and_project_member;
    /**
     * @var \Mockery\LegacyMockInterface|PFUser
     */
    private $user_project_member;
    /**
     * @var \Mockery\LegacyMockInterface|PFUser
     */
    private $user_not_project_member;
    /**
     * @var \Mockery\LegacyMockInterface|PFUser
     */
    private $marketing_member_only;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|null
     */
    private $project;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Permission_PermissionRetrieveAssignee
     */
    private $assignee_retriever;

    protected function setUp(): void
    {
        $this->setUpUsers();

        $this->project = \Mockery::spy(\Project::class)->shouldReceive('getId')
            ->andReturns($this->project_id)->getMock();
        $this->tracker = Mockery::mock(Tracker::class);
        $this->tracker->shouldReceive('getProject')->andReturn($this->project);

        $this->assignee_retriever = \Mockery::spy(\Tracker_Permission_PermissionRetrieveAssignee::class);

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

    /**
     * @param array $ugroup_ids
     *
     * @return \Mockery\LegacyMockInterface|PFUser
     */
    private function getUserWithGroups(array $ugroup_ids)
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getUgroups')->with($this->project_id, \Mockery::any())->andReturns($ugroup_ids);

        return $user;
    }

    private function assertTrackerUGroupIdsWithoutAdminsEquals(Tracker_Artifact $artifact, array $expected_values): void
    {
        $this->assertEquals(
            $expected_values,
            array_values($this->serializer->getUserGroupsThatCanViewTracker($artifact))
        );
    }

    private function assertTrackerUGroupIdsEquals(Tracker_Artifact $artifact, array $expected_values): void
    {
        $this->assertTrackerUGroupIdsWithoutAdminsEquals(
            $artifact,
            array_merge([ProjectUGroup::PROJECT_ADMIN], $expected_values)
        );
    }

    private function assertArtifactUGroupIdsWithoutAdminsEquals(
        Tracker_Artifact $artifact,
        array $expected_values
    ): void {
        $this->assertEquals(
            $expected_values,
            array_values($this->serializer->getUserGroupsThatCanViewArtifact($artifact))
        );
    }

    private function assertArtifactUGroupIdsEquals(Tracker_Artifact $artifact, array $expected_values): void
    {
        if ($expected_values) {
            $expected_values = array_merge([ProjectUGroup::PROJECT_ADMIN], $expected_values);
        }
        $this->assertArtifactUGroupIdsWithoutAdminsEquals(
            $artifact,
            $expected_values
        );
    }

    private function assertSubmitterOnlyUGroupIdsEquals(Tracker_Artifact $artifact, $expected_value): void
    {
        $this->assertEquals(
            $expected_value,
            $this->serializer->getLiteralizedUserGroupsSubmitterOnly($artifact)
        );
    }

    private function assertFieldsPermissionUGroupIdsEquals(Tracker_Artifact $artifact, array $expected_value): void
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

    private function anArtifact($submitter, array $assignees, array $authorized_ugroups): Tracker_Artifact
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $artifact->shouldReceive('getSubmittedByUser')->andReturn($submitter);
        $artifact->shouldReceive('getTracker')->andReturn($this->tracker);
        $artifact->shouldReceive('getAuthorizedUGroups')->andReturn($authorized_ugroups);

        $this->assignee_retriever->shouldReceive('getAssignees')->with($artifact)->andReturn($assignees);
        return $artifact;
    }

    public function testProjectAdminAlwaysReturnsProjectAdminWhenAllUsersHaveAccessToAllArtifacts(): void
    {
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')->andReturns([]);

        $this->assertTrackerUGroupIdsWithoutAdminsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [ProjectUGroup::PROJECT_ADMIN]
        );
    }

    public function testProjectAdminReturnsAnonymousWhenAllUsersHaveAccessToAllArtifacts(): void
    {
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')
            ->andReturns([Tracker::PERMISSION_FULL => [ProjectUGroup::ANONYMOUS]]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [ProjectUGroup::ANONYMOUS]
        );
    }

    public function testProjectAdminReturnsRegisteredUsersWhenTheyAreGranted(): void
    {
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')
            ->andReturns([Tracker::PERMISSION_FULL => [ProjectUGroup::REGISTERED]]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [
                ProjectUGroup::REGISTERED
            ]
        );
    }

    public function testProjectAdminReturnsProjectMemberWhenTheyAreGranted(): void
    {
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')
            ->andReturns([Tracker::PERMISSION_FULL => [ProjectUGroup::PROJECT_MEMBERS]]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [ProjectUGroup::PROJECT_MEMBERS]
        );
    }

    public function testProjectAdminReturnsOneDynamicUserGroupWhenTheyAreGranted(): void
    {
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')
            ->andReturns([Tracker::PERMISSION_FULL => [$this->support_ugroup_id]]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [$this->support_ugroup_id]
        );
    }

    public function testProjectAdminReturnsDynamicUsersAndProjectMembersGroupWhenTheyAreGranted(): void
    {
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')
            ->andReturns([Tracker::PERMISSION_FULL => [$this->support_ugroup_id, ProjectUGroup::PROJECT_MEMBERS]]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [
                $this->support_ugroup_id,
                ProjectUGroup::PROJECT_MEMBERS
            ]
        );
    }

    public function testTrackerAdminReturnsProjectMemberWhenTheyAreGranted(): void
    {
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')
            ->andReturns([Tracker::PERMISSION_ADMIN => [ProjectUGroup::PROJECT_MEMBERS]]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [ProjectUGroup::PROJECT_MEMBERS]
        );
    }

    public function testTrackerAdminReturnsOneDynamicUserGroupWhenTheyAreGranted(): void
    {
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')
            ->andReturns([Tracker::PERMISSION_ADMIN => [$this->support_ugroup_id]]);

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [$this->support_ugroup_id]
        );
    }

    public function testTrackerAdminReturnsDynamicUsersAndProjectMembersGroupWhenTheyAreGranted(): void
    {
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')
            ->andReturns(
                [
                    Tracker::PERMISSION_ADMIN => [$this->support_ugroup_id, ProjectUGroup::PROJECT_MEMBERS]
                ]
            );

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact($this->user_not_project_member, [], []),
            [
                $this->support_ugroup_id,
                ProjectUGroup::PROJECT_MEMBERS
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
                $this->support_ugroup_id
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
                $this->support_ugroup_id
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
                $this->marketing_ugroup_id
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
                $this->support_ugroup_id
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
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')->andReturns(
            [Tracker::PERMISSION_SUBMITTER_ONLY => [$this->support_ugroup_id]]
        );

        $this->tracker->shouldReceive('userIsAdmin')->andReturns(true);

        $this->artifact = $this->anArtifact($this->marketing_ugroup_id, [], []);

        $this->assertSubmitterOnlyUGroupIdsEquals(
            $this->artifact,
            [$this->support_ugroup_literalize]
        );
    }

    public function testFieldPermissionReturnsArtifactFieldsPermissionsWithoutPermissionSubmit(): void
    {
        $this->tracker->shouldReceive('getFieldsAuthorizedUgroupsByPermissionType')->andReturns(
            [
                $this->summary_field_id =>
                    [
                        Tracker_FormElement_Field::PERMISSION_READ   => [$this->support_ugroup_id],
                        Tracker_FormElement_Field::PERMISSION_SUBMIT => [ProjectUGroup::PROJECT_ADMIN],
                        Tracker_FormElement_Field::PERMISSION_UPDATE => [ProjectUGroup::PROJECT_MEMBERS]
                    ]
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
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')->andReturns(
            [
                Tracker::PERMISSION_FULL           => [ProjectUGroup::PROJECT_ADMIN],
                Tracker::PERMISSION_ADMIN          => [ProjectUGroup::PROJECT_ADMIN],
                Tracker::PERMISSION_SUBMITTER      => [ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::PROJECT_MEMBERS],
                Tracker::PERMISSION_ASSIGNEE       => [ProjectUGroup::PROJECT_ADMIN, $this->support_ugroup_id],
                Tracker::PERMISSION_SUBMITTER_ONLY => [ProjectUGroup::PROJECT_ADMIN]
            ]
        );

        $this->assertTrackerUGroupsEquals(
            $this->tracker,
            [
                $this->project_admin_literalize,
                $this->project_members_literalize,
                $this->support_ugroup_literalize
            ]
        );
    }

    private function mockSubmitter(array $submitter): void
    {
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')
            ->andReturns([Tracker::PERMISSION_SUBMITTER => $submitter]);
    }

    private function mockAssignee(array $assignee): void
    {
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')->andReturns(
            [Tracker::PERMISSION_ASSIGNEE => $assignee]
        );
    }

    private function mockSubmitterAndAssignee(array $submitter, array $assignee): void
    {
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')->andReturns(
            [
                Tracker::PERMISSION_SUBMITTER => $submitter,
                Tracker::PERMISSION_ASSIGNEE  => $assignee,
            ]
        );
    }

    private function mockSeveralPermissions(array $submitter, array $assignee, array $full): void
    {
        $this->tracker->shouldReceive('getAuthorizedUgroupsByPermissionType')->andReturns(
            [
                Tracker::PERMISSION_FULL      => $full,
                Tracker::PERMISSION_SUBMITTER => $submitter,
                Tracker::PERMISSION_ASSIGNEE  => $assignee,
            ]
        );
    }
}
