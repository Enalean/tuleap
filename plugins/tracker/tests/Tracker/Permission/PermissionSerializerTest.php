<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once __DIR__.'/../../bootstrap.php';

class Tracker_Permission_PermissionSerializer_ArtifactBuilder
{
    private $artifact_builder;
    private $assignee_retriever;
    private $submitter;

    private $assignees                = array();
    private $artifact_ugroups         = array();
    private $use_artifact_permissions = true;

    public function __construct($artifact_builder, $assignee_retriever, $default_submitter)
    {
        $this->artifact_builder   = $artifact_builder;
        $this->assignee_retriever = $assignee_retriever;
        $this->submitter          = $default_submitter;
    }

    public function withSubmitter(PFUser $user)
    {
        $this->submitter = $user;
        return $this;
    }

    public function withAssignees(array $assignees)
    {
        $this->assignees = $assignees;
        return $this;
    }

    public function withArtifactAuthorizedUGroups(array $ugroups)
    {
        $this->artifact_ugroups = $ugroups;
        return $this;
    }

    public function withUseArtifactPermissions($use)
    {
        $this->use_artifact_permissions = $use;
        return $this;
    }

    public function build()
    {
        $artifact = $this->artifact_builder->withSubmitter($this->submitter)->build();
        $artifact->setAuthorizedUGroups($this->artifact_ugroups);
        stub($this->assignee_retriever)->getAssignees($artifact)->returns($this->assignees);
        return $artifact;
    }
}

abstract class Tracker_Permission_PermissionSerializer extends TuleapTestCase
{

    protected $project_id = 333;

    protected $marketing_ugroup_id = 115;
    protected $support_ugroup_id   = 114;

    protected $summary_field_id = 352;

    protected $support_ugroup_literalize  = '@ug_114';
    protected $project_members_literalize = '@_project_members';
    protected $project_admin_literalize   = '@_project_admin';

    protected $user_submitter;
    protected $current_user;

    protected $support_member_only;
    protected $support_and_project_member;
    protected $user_project_member;
    protected $user_not_project_member;
    protected $marketing_member_only;

    protected $tracker;
    protected $artifact;
    protected $project;
    protected $artifact_builder;
    protected $assignee_retriever;

    public function setUp()
    {
        parent::setUp();

        $this->setUpUsers();

        $this->project = stub('Project')->getId()->returns($this->project_id);
        $this->tracker = aMockTracker()
            ->withId(120)
            ->withProject($this->project)
            ->build();
        $this->artifact_builder   = anArtifact()->withTracker($this->tracker);

        $this->assignee_retriever = mock('Tracker_Permission_PermissionRetrieveAssignee');

        $this->serializer         = new Tracker_Permission_PermissionsSerializer($this->assignee_retriever);

        $this->user_submitter     = stub('PFUser')->getId()->returns('101');
        $this->current_user       = stub('PFUser')->getId()->returns('165');
    }

    private function setUpUsers()
    {
        $this->user_project_member        = $this->getUserWithGroups(array(ProjectUGroup::PROJECT_MEMBERS));
        $this->user_not_project_member    = $this->getUserWithGroups(array());
        $this->support_member_only        = $this->getUserWithGroups(array($this->support_ugroup_id));
        $this->marketing_member_only      = $this->getUserWithGroups(array($this->marketing_ugroup_id));
        $this->support_and_project_member = $this->getUserWithGroups(array(ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id));
    }

    private function getUserWithGroups(array $ugroup_ids)
    {
        return stub('PFUser')->getUgroups($this->project_id, '*')->returns($ugroup_ids);
    }

    protected function assertTrackerUGroupIdsWithoutAdminsEquals(Tracker_Artifact $artifact, array $expected_values)
    {
        $this->assertEqual(
            array_values($this->serializer->getUserGroupsThatCanViewTracker($artifact)),
            $expected_values
        );
    }

    protected function assertTrackerUGroupIdsEquals(Tracker_Artifact $artifact, array $expected_values)
    {
        $this->assertTrackerUGroupIdsWithoutAdminsEquals(
            $artifact,
            array_merge(array(ProjectUGroup::PROJECT_ADMIN), $expected_values)
        );
    }

    protected function assertArtifactUGroupIdsWithoutAdminsEquals(Tracker_Artifact $artifact, array $expected_values)
    {
        $this->assertEqual(
            array_values($this->serializer->getUserGroupsThatCanViewArtifact($artifact)),
            $expected_values
        );
    }

    protected function assertArtifactUGroupIdsEquals(Tracker_Artifact $artifact, array $expected_values)
    {
        if ($expected_values) {
            $expected_values = array_merge(array(ProjectUGroup::PROJECT_ADMIN), $expected_values);
        }
        $this->assertArtifactUGroupIdsWithoutAdminsEquals(
            $artifact,
            $expected_values
        );
    }

    protected function assertSubmitterOnlyUGroupIdsEquals(Tracker_Artifact $artifact, $expected_value)
    {
        $this->assertEqual(
            $this->serializer->getLiteralizedUserGroupsSubmitterOnly($artifact),
            $expected_value
        );
    }

    protected function assertFieldsPermissionUGroupIdsEquals(Tracker_Artifact $artifact, array $expected_value)
    {
        $this->assertEqual(
            $this->serializer->getLiteralizedUserGroupsThatCanViewTrackerFields($artifact),
            $expected_value
        );
    }

    protected function assertTrackerUGroupsEquals(Tracker $tracker, $expected_value)
    {
        $this->assertEqual(
            $this->serializer->getLiteralizedAllUserGroupsThatCanViewTracker($tracker),
            $expected_value
        );
    }

    protected function anArtifact()
    {
        return new Tracker_Permission_PermissionSerializer_ArtifactBuilder($this->artifact_builder, $this->assignee_retriever, $this->user_not_project_member);
    }
}

class Tracker_Permission_PermissionSerializer_ProjectAdminAccessTest extends Tracker_Permission_PermissionSerializer
{

    public function itAlwaysReturnsProjectAdminWhenAllUsersHaveAccessToAllArtifacts()
    {
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array()
        );

        $this->assertTrackerUGroupIdsWithoutAdminsEquals(
            $this->anArtifact()
                ->build(),
            array(
                ProjectUGroup::PROJECT_ADMIN
            )
        );
    }
}

class Tracker_Permission_PermissionSerializer_FullAccessTest extends Tracker_Permission_PermissionSerializer
{

    public function itReturnsAnonymousWhenAllUsersHaveAccessToAllArtifacts()
    {
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_FULL => array(ProjectUGroup::ANONYMOUS)
            )
        );

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->build(),
            array(
                ProjectUGroup::ANONYMOUS
            )
        );
    }

    public function itReturnsRegisteredUsersWhenTheyAreGranted()
    {
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_FULL => array(ProjectUGroup::REGISTERED)
            )
        );

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->build(),
            array(
                ProjectUGroup::REGISTERED
            )
        );
    }

    public function itReturnsProjectMemberWhenTheyAreGranted()
    {
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_FULL => array(ProjectUGroup::PROJECT_MEMBERS)
            )
        );

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->build(),
            array(
                ProjectUGroup::PROJECT_MEMBERS
            )
        );
    }

    public function itReturnsOneDynamicUserGroupWhenTheyAreGranted()
    {
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_FULL => array($this->support_ugroup_id)
            )
        );

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->build(),
            array(
                $this->support_ugroup_id
            )
        );
    }

    public function itReturnsDynamicUsersAndProjectMembersGroupWhenTheyAreGranted()
    {
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_FULL => array($this->support_ugroup_id, ProjectUGroup::PROJECT_MEMBERS)
            )
        );

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->build(),
            array(
                $this->support_ugroup_id,
                ProjectUGroup::PROJECT_MEMBERS
            )
        );
    }
}

class Tracker_Permission_PermissionSerializer_TrackerAdminTest extends Tracker_Permission_PermissionSerializer
{

    public function itReturnsProjectMemberWhenTheyAreGranted()
    {
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_ADMIN => array(ProjectUGroup::PROJECT_MEMBERS)
            )
        );

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->build(),
            array(
                ProjectUGroup::PROJECT_MEMBERS
            )
        );
    }

    public function itReturnsOneDynamicUserGroupWhenTheyAreGranted()
    {
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_ADMIN => array($this->support_ugroup_id)
            )
        );

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->build(),
            array(
                $this->support_ugroup_id
            )
        );
    }

    public function itReturnsDynamicUsersAndProjectMembersGroupWhenTheyAreGranted()
    {
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_ADMIN => array($this->support_ugroup_id, ProjectUGroup::PROJECT_MEMBERS)
            )
        );

        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->build(),
            array(
                $this->support_ugroup_id,
                ProjectUGroup::PROJECT_MEMBERS
            )
        );
    }
}

class Tracker_Permission_PermissionSerializer_SubmittedBy_OneGroupOnlyTest extends Tracker_Permission_PermissionSerializer
{

    public function setUp()
    {
        parent::setUp();
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_SUBMITTER => array(ProjectUGroup::PROJECT_MEMBERS)
            )
        );
    }

    public function itReturnsProjectMembersWhenTheArtifactIsSubmittedByAMemberOfTheProject()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_project_member)
                ->build(),
            array(
                ProjectUGroup::PROJECT_MEMBERS
            )
        );
    }

    public function itReturnsEmptyArrayWhenArtifactIsSubmittedByNonProjectMember()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_not_project_member)
                ->build(),
            array()
        );
    }
}

class Tracker_Permission_PermissionSerializer_SubmittedBy_TwoGroupsTest extends Tracker_Permission_PermissionSerializer
{

    public function setUp()
    {
        parent::setUp();
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_SUBMITTER => array(ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id)
            )
        );
    }

    public function itReturnsProjectMembersWhenTheArtifactIsSubmittedByAMemberOfTheProject()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_project_member)
                ->build(),
            array(
                ProjectUGroup::PROJECT_MEMBERS
            )
        );
    }

    public function itReturnsSupportMembersWhenTheArtifactIsSubmittedByAMemberOfSupportTeam()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->support_member_only)
                ->build(),
            array(
                $this->support_ugroup_id
            )
        );
    }

    public function itReturnsSupportMembersAndProjectMembersWhenTheArtifactIsSubmittedByAMemberOfBothTeams()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->support_and_project_member)
                ->build(),
            array(
                ProjectUGroup::PROJECT_MEMBERS,
                $this->support_ugroup_id
            )
        );
    }
}

class Tracker_Permission_PermissionSerializer_AssignedTo_OneGroupOnlyTest extends Tracker_Permission_PermissionSerializer
{

    public function setUp()
    {
        parent::setUp();
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_ASSIGNEE => array(ProjectUGroup::PROJECT_MEMBERS)
            )
        );
    }

    public function itReturnsProjectMembersWhenTheArtifactIsAssignedToAMemberOfTheProject()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_not_project_member)
                ->withAssignees(array($this->user_project_member))
                ->build(),
            array(
                ProjectUGroup::PROJECT_MEMBERS
            )
        );
    }

    public function itReturnsEmptyArrayWhenTheArtifactIsAssignedToANonProjectMember()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_not_project_member)
                ->withAssignees(array($this->user_not_project_member))
                ->build(),
            array()
        );
    }

    public function itReturnsEmptyArrayWhenTheArtifactIsAssignedToAMemberOfAnotherUGroup()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_not_project_member)
                ->withAssignees(array($this->support_member_only))
                ->build(),
            array()
        );
    }
}

class Tracker_Permission_PermissionSerializer_AssignedTo_TwoGroupsTest extends Tracker_Permission_PermissionSerializer
{

    public function setUp()
    {
        parent::setUp();
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_ASSIGNEE => array(ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id)
            )
        );
    }


    public function itReturnsProjectMembersWhenTheArtifactIsAssignedToAMemberOfTheProject()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_not_project_member)
                ->withAssignees(array($this->user_project_member))
                ->build(),
            array(
                ProjectUGroup::PROJECT_MEMBERS
            )
        );
    }

    public function itReturnsSupportMembersWhenTheArtifactIsAssignedToAMemberOfSupportTeam()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_not_project_member)
                ->withAssignees(array($this->support_member_only))
                ->build(),
            array(
                $this->support_ugroup_id
            )
        );
    }

    public function itReturnsSupportMembersAndProjectMembersWhenTheArtifactIsAssignedToAMemberOfBothTeams()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_not_project_member)
                ->withAssignees(array($this->support_and_project_member))
                ->build(),
            array(
                ProjectUGroup::PROJECT_MEMBERS,
                $this->support_ugroup_id
            )
        );
    }
}

class Tracker_Permission_PermissionSerializer_AssignedTo_TwoPeopleTest extends Tracker_Permission_PermissionSerializer
{

    public function setUp()
    {
        parent::setUp();
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_ASSIGNEE => array($this->support_ugroup_id, $this->marketing_ugroup_id)
            )
        );
        $this->artifact = $this->artifact_builder->build();
    }

    public function itReturnsSupportAndMarketingTeamsWhenTheArtifactIsAssignedToOnePeopleOfEachGroup()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_not_project_member)
                ->withAssignees(array($this->support_member_only, $this->marketing_member_only))
                ->build(),
            array(
                $this->support_ugroup_id,
                $this->marketing_ugroup_id
            )
        );
    }

    public function itReturnsSupportTeamWhenTheArtifactIsAssignedToPeopleFromProjectAndSupport()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_not_project_member)
                ->withAssignees(array($this->user_project_member, $this->support_member_only))
                ->build(),
            array(
                $this->support_ugroup_id,
            )
        );
    }

    public function itReturnsNobodyWhenTheArtifactIsAssignedToPeopleFromOtherTeams()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_not_project_member)
                ->withAssignees(array($this->user_project_member, $this->user_not_project_member))
                ->build(),
            array()
        );
    }
}

class Tracker_Permission_PermissionSerializer_SubmittedByOrAssignedTo_OneGroupTest extends Tracker_Permission_PermissionSerializer
{

    public function setUp()
    {
        parent::setUp();
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_SUBMITTER => array(ProjectUGroup::PROJECT_MEMBERS),
                Tracker::PERMISSION_ASSIGNEE  => array(ProjectUGroup::PROJECT_MEMBERS),
            )
        );
    }

    public function itReturnsProjectMembersWhenAProjectMemberSubmittedTheArtifact()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_project_member)
                ->build(),
            array(
                ProjectUGroup::PROJECT_MEMBERS
            )
        );
    }

    public function itReturnsProjectMembersWhenAProjectMemberIsAssignedToTheArtifact()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->support_member_only)
                ->withAssignees(array($this->user_project_member))
                ->build(),
            array(
                ProjectUGroup::PROJECT_MEMBERS
            )
        );
    }

    public function itReturnsEmptyWhenNoProjectMembersAreAssignedNorSubmitterToTheArtifact()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->support_member_only)
                ->withAssignees(array($this->user_not_project_member))
                ->build(),
            array()
        );
    }
}

class Tracker_Permission_PermissionSerializer_SubmittedByOrAssignedTo_TwoGroupsTest extends Tracker_Permission_PermissionSerializer
{

    public function setUp()
    {
        parent::setUp();
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_SUBMITTER => array(ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id),
                Tracker::PERMISSION_ASSIGNEE  => array(ProjectUGroup::PROJECT_MEMBERS, $this->support_ugroup_id),
            )
        );
    }

    public function itReturnsSupportTeamWhenSubmittedBySupportMember()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->support_member_only)
                ->build(),
            array(
                $this->support_ugroup_id
            )
        );
    }

    public function itReturnsSupportTeamWhenAssignedToSupportTeam()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_not_project_member)
                ->withAssignees(array($this->support_member_only))
                ->build(),
            array(
                $this->support_ugroup_id
            )
        );
    }

    public function itReturnsBothWhenSubmittedByProjectMemberAndAssignedToSupportTeam()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_project_member)
                ->withAssignees(array($this->support_member_only))
                ->build(),
            array(
                ProjectUGroup::PROJECT_MEMBERS,
                $this->support_ugroup_id
            )
        );
    }

    public function itReturnsEmptyArrayWhenNeitherGroupsAreInvolved()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_not_project_member)
                ->withAssignees(array($this->marketing_member_only))
                ->build(),
            array()
        );
    }
}

class Tracker_Permission_PermissionSerializer_SeveralPermissions_Test extends Tracker_Permission_PermissionSerializer
{

    /**
     * Support team have full access
     * Project members can see artifacts submitted or assigned to groups
     * Marketing can see artifacts submitted by group
     */
    public function setUp()
    {
        parent::setUp();
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_FULL      => array($this->support_ugroup_id),
                Tracker::PERMISSION_SUBMITTER => array(ProjectUGroup::PROJECT_MEMBERS, $this->marketing_ugroup_id),
                Tracker::PERMISSION_ASSIGNEE  => array(ProjectUGroup::PROJECT_MEMBERS),
            )
        );
    }

    public function itHasAnExternaSubmitter()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_not_project_member)
                ->build(),
            array(
                $this->support_ugroup_id
            )
        );
    }

    public function itHasAnExternalSubmitterAndProjectMemberAssignee()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_not_project_member)
                ->withAssignees(array($this->user_project_member))
                ->build(),
            array(
                $this->support_ugroup_id,
                ProjectUGroup::PROJECT_MEMBERS
            )
        );
    }

    public function itHasAnExternalSubmitterAndMarketingAssignee()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->user_not_project_member)
                ->withAssignees(array($this->marketing_member_only))
                ->build(),
            array(
                $this->support_ugroup_id,
            )
        );
    }

    public function itHasAMarketingSubmitterAndProjectMemberAssignee()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->marketing_member_only)
                ->withAssignees(array($this->user_project_member))
                ->build(),
            array(
                $this->support_ugroup_id,
                $this->marketing_ugroup_id,
                ProjectUGroup::PROJECT_MEMBERS,
            )
        );
    }

    public function itHasAMarketingSubmitterAndMultiTeamAssignee()
    {
        $this->assertTrackerUGroupIdsEquals(
            $this->anArtifact()
                ->withSubmitter($this->marketing_member_only)
                ->withAssignees(array($this->support_and_project_member))
                ->build(),
            array(
                $this->support_ugroup_id,
                $this->marketing_ugroup_id,
                ProjectUGroup::PROJECT_MEMBERS,
            )
        );
    }
}

class Tracker_Permission_PermissionSerializer_ArtifactPermissions_Test extends Tracker_Permission_PermissionSerializer
{

    public function setUp()
    {
        parent::setUp();
    }

    public function itReturnsArtifactPermissionsSelected()
    {
        $this->artifact = $this->anArtifact()->withArtifactAuthorizedUGroups(
            array(
                $this->support_ugroup_id
            )
        )->build();

        $this->assertArtifactUGroupIdsEquals(
            $this->artifact,
            array(
                $this->support_ugroup_id
            )
        );
    }

    public function itReturnsNothingIfArtifactPermissionsAreNotSelected()
    {
        $this->artifact = $this->anArtifact()->withArtifactAuthorizedUGroups(
            array()
        )->build();

        $this->assertArtifactUGroupIdsEquals(
            $this->artifact,
            array()
        );
    }
}

class Tracker_Permission_PermissionSerializer_SubmitterOnlyPermission_Test extends Tracker_Permission_PermissionSerializer
{

    public function setUp()
    {
        parent::setUp();
    }

    public function itReturnsSubmitterOnlyUGroupsIds()
    {
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(Tracker::PERMISSION_SUBMITTER_ONLY => array($this->support_ugroup_id))
        );

        stub($this->tracker)->userIsAdmin()->returns(
            true
        );

        $this->artifact = $this->anArtifact()
            ->build();

        $this->assertSubmitterOnlyUGroupIdsEquals(
            $this->artifact,
            array($this->support_ugroup_literalize)
        );
    }
}

class Tracker_Permission_PermissionSerializer_FieldPermission_Test extends Tracker_Permission_PermissionSerializer
{

    public function setUp()
    {
        parent::setUp();
    }

    public function itReturnsArtifactFieldsPermissionsWithoutPermissionSubmit()
    {
        stub($this->tracker)->getFieldsAuthorizedUgroupsByPermissionType()->returns(
            array($this->summary_field_id =>
                array(
                    Tracker_FormElement_Field::PERMISSION_READ   => array($this->support_ugroup_id),
                    Tracker_FormElement_Field::PERMISSION_SUBMIT => array(ProjectUGroup::PROJECT_ADMIN),
                    Tracker_FormElement_Field::PERMISSION_UPDATE => array(ProjectUGroup::PROJECT_MEMBERS)
                )
            )
        );

        $this->artifact = $this->anArtifact()
            ->build();

        $this->assertFieldsPermissionUGroupIdsEquals(
            $this->artifact,
            array($this->summary_field_id => array($this->support_ugroup_literalize, $this->project_members_literalize))
        );
    }
}

class Tracker_Permission_PermissionSerializer_GroupsPermissions_Test extends Tracker_Permission_PermissionSerializer
{

    public function setUp()
    {
        parent::setUp();
    }

    public function itReturnsAllGroupsCanViewTracker()
    {
        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_FULL           => array(ProjectUGroup::PROJECT_ADMIN),
                Tracker::PERMISSION_ADMIN          => array(ProjectUGroup::PROJECT_ADMIN),
                Tracker::PERMISSION_SUBMITTER      => array(ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::PROJECT_MEMBERS),
                Tracker::PERMISSION_ASSIGNEE       => array(ProjectUGroup::PROJECT_ADMIN, $this->support_ugroup_id),
                Tracker::PERMISSION_SUBMITTER_ONLY => array(ProjectUGroup::PROJECT_ADMIN)
            )
        );

        $this->assertTrackerUGroupsEquals(
            $this->tracker,
            array(
                $this->project_admin_literalize,
                $this->project_members_literalize,
                $this->support_ugroup_literalize
            )
        );
    }
}
