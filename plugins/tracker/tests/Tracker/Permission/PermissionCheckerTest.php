<?php
/**
 * Copyright (c) Enalean, 2013 - 2014. All Rights Reserved.
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

class Tracker_Permission_PermissionCheckerTest extends TuleapTestCase {
    private $user_manager;
    private $permission_checker;

    private $user;
    private $assignee;
    private $u_ass;
    private $submitter;
    private $u_sub;
    private $other;

    private $tracker;

    public function setUp() {
        parent::setUp();

        $project = mock('Project');
        stub($project)->getID()->returns(120);
        stub($project)->isPublic()->returns(true);

        $this->user_manager       = mock('UserManager');
        $this->project_manager    = mock('ProjectManager');
        $this->project_manager->setReturnValue('checkRestrictedAccess', true);

        $this->permission_checker = new Tracker_Permission_PermissionChecker($this->user_manager, $this->project_manager);

        // $assignee and $u_ass are in the same ugroup (UgroupAss - ugroup_id=101)
        // $submitter and $u_sub are in the same ugroup (UgroupSub - ugroup_id=102)
        // $other and $u are neither in UgroupAss nor in UgroupSub

        $this->user = mock('PFUser');
        $this->user->setReturnValue('getId', 120);
        $this->user->setReturnValue('isMemberOfUgroup',false);
        $this->user->setReturnValue('isSuperUser', false);
        $this->user->setReturnValue('isMember', true, array(12));

        $this->assignee = mock('PFUser');
        $this->assignee->setReturnValue('getId', 121);
        $this->assignee->setReturnValue('isMemberOfUgroup', true,  array(101, 222));
        $this->assignee->setReturnValue('isMemberOfUgroup', false, array(102, 222));
        $this->assignee->setReturnValue('isSuperUser', false);
        $this->assignee->setReturnValue('isMember', true, array(12));

        $this->u_ass = mock('PFUser');
        $this->u_ass->setReturnValue('getId', 122);
        $this->u_ass->setReturnValue('isMemberOfUgroup', true,  array(101, 222));
        $this->u_ass->setReturnValue('isMemberOfUgroup', false, array(102, 222));
        $this->u_ass->setReturnValue('isSuperUser', false);
        $this->u_ass->setReturnValue('isMember', true, array(12));

        $this->submitter = mock('PFUser');
        $this->submitter->setReturnValue('getId', 123);
        $this->submitter->setReturnValue('isMemberOfUgroup', false, array(101, 222));
        $this->submitter->setReturnValue('isMemberOfUgroup', true,  array(102, 222));
        $this->submitter->setReturnValue('isSuperUser', false);
        $this->submitter->setReturnValue('isMember', true, array(12));

        $this->u_sub = mock('PFUser');
        $this->u_sub->setReturnValue('getId', 124);
        $this->u_sub->setReturnValue('isMemberOfUgroup', false, array(101, 222));
        $this->u_sub->setReturnValue('isMemberOfUgroup', true,  array(102, 222));
        $this->u_sub->setReturnValue('isSuperUser', false);
        $this->u_sub->setReturnValue('isMember', true, array(12));

        $this->other = mock('PFUser');
        $this->other->setReturnValue('getId', 125);
        $this->other->setReturnValue('isMemberOfUgroup', false);
        $this->other->setReturnValue('isSuperUser', false);
        $this->other->setReturnValue('isMember', true, array(12));

        $this->restricted = mock('PFUser');
        $this->restricted->setReturnValue('getId', 126);
        $this->restricted->setReturnValue('isMemberOfUgroup', true);
        $this->restricted->setReturnValue('isSuperUser', false);
        $this->restricted->setReturnValue('isRestricted', true);

        $this->user_manager->setReturnReference('getUserById', $this->user, array(120));
        $this->user_manager->setReturnReference('getUserById', $this->assignee, array(121));
        $this->user_manager->setReturnReference('getUserById', $this->u_ass, array(122));
        $this->user_manager->setReturnReference('getUserById', $this->submitter, array(123));
        $this->user_manager->setReturnReference('getUserById', $this->u_sub, array(124));
        $this->user_manager->setReturnReference('getUserById', $this->other, array(125));
        $this->user_manager->setReturnReference('getUserById', $this->restricted, array(126));

        $this->tracker = mock('Tracker');
        $this->tracker->setReturnValue('getId', 666);
        $this->tracker->setReturnValue('getGroupId', 222);
        $this->tracker->setReturnValue('getProject', $project);
    }

    public function testRestrictedUserCanSeeTrackerBecauseTrackerDoesNotCheckRestrictedAccess() {
        $project_manager = stub('ProjectManager')->checkRestrictedAccess()->returns(false);

        $permissions = array('PLUGIN_TRACKER_ACCESS_FULL' => array(0 => ProjectUGroup::REGISTERED));
        $this->tracker->setReturnReference('getAuthorizedUgroupsByPermissionType', $permissions);

        $artifact = mock('Tracker_Artifact');
        $artifact->setReturnReference('getTracker', $this->tracker);
        $artifact->setReturnValue('useArtifactPermissions', false);

        $permission_checker = new Tracker_Permission_PermissionChecker(
            $this->user_manager,
            $project_manager
        );

        $this->assertTrue($permission_checker->userCanView($this->restricted, $artifact));
    }

    function testUserCanViewTrackerAccessSubmitter() {
        $ugroup_ass = 101;
        $ugroup_sub = 102;

        // $artifact_submitter has been submitted by $submitter and assigned to $u
        // $submitter, $u_sub should have the right to see it.
        // $other, $assignee, $u_ass and $u should not have the right to see it


        $permissions = array("PLUGIN_TRACKER_ACCESS_SUBMITTER" => array(0 => $ugroup_sub));
        $this->tracker->setReturnReference('getAuthorizedUgroupsByPermissionType', $permissions);

        $artifact = mock('Tracker_Artifact');
        $artifact->setReturnReference('getTracker', $this->tracker);
        $artifact->setReturnValue('useArtifactPermissions', false);
        $artifact->setReturnValue('getSubmittedBy', 123);

        $this->assertTrue($this->permission_checker->userCanView($this->submitter, $artifact));
        $this->assertTrue($this->permission_checker->userCanView($this->u_sub, $artifact));
        $this->assertFalse($this->permission_checker->userCanView($this->other, $artifact));
        $this->assertFalse($this->permission_checker->userCanView($this->user, $artifact));
        $this->assertFalse($this->permission_checker->userCanView($this->assignee, $artifact));
        $this->assertFalse($this->permission_checker->userCanView($this->u_ass, $artifact));
    }

    function testUserCanViewTrackerAccessAssignee() {
        $ugroup_ass = 101;
        $ugroup_sub = 102;


        // $artifact_assignee has been submitted by $u and assigned to $assignee
        // $assignee and $u_ass should have the right to see it.
        // $other, $submitter, $u_sub and $u should not have the right to see it
        $permissions = array("PLUGIN_TRACKER_ACCESS_ASSIGNEE" => array(0 => $ugroup_ass));
        $this->tracker->setReturnReference('getAuthorizedUgroupsByPermissionType', $permissions);

        $contributor_field = aMockField()->build();
        $this->tracker->setReturnReference('getContributorField', $contributor_field);
        $artifact_assignee = mock('Tracker_Artifact');
        $artifact_assignee->setReturnReference('getTracker', $this->tracker);
        $artifact_assignee->setReturnValue('useArtifactPermissions', false);
        $artifact_assignee->setReturnValue('getSubmittedBy', 120);
        $user_changeset_value = mock('Tracker_Artifact_ChangesetValue');
        $contributors = array(121);
        $user_changeset_value->setReturnReference('getValue', $contributors);
        $artifact_assignee->setReturnReference('getValue', $user_changeset_value, array($contributor_field));

        $this->assertTrue ($this->permission_checker->userCanView($this->assignee, $artifact_assignee));
        $this->assertTrue ($this->permission_checker->userCanView($this->u_ass, $artifact_assignee));
        $this->assertFalse($this->permission_checker->userCanView($this->submitter, $artifact_assignee));
        $this->assertFalse($this->permission_checker->userCanView($this->u_sub, $artifact_assignee));
        $this->assertFalse($this->permission_checker->userCanView($this->other, $artifact_assignee));
        $this->assertFalse($this->permission_checker->userCanView($this->user, $artifact_assignee));

    }

    function testUserCanViewTrackerAccessSubmitterOrAssignee() {
        $ugroup_ass = 101;
        $ugroup_sub = 102;

        // $artifact_subass has been submitted by $submitter and assigned to $assignee
        // $assignee, $u_ass, $submitter, $u_sub should have the right to see it.
        // $other and $u should not have the right to see it
        $permissions = array("PLUGIN_TRACKER_ACCESS_ASSIGNEE"  => array(0 => $ugroup_ass),
                             "PLUGIN_TRACKER_ACCESS_SUBMITTER" => array(0 => $ugroup_sub)
                            );
        $this->tracker->setReturnReference('getAuthorizedUgroupsByPermissionType', $permissions);

        $contributor_field = aMockField()->build();
        $this->tracker->setReturnReference('getContributorField', $contributor_field);
        $artifact_subass = mock('Tracker_Artifact');
        $artifact_subass->setReturnReference('getTracker', $this->tracker);
        $artifact_subass->setReturnValue('useArtifactPermissions', false);
        $artifact_subass->setReturnValue('getSubmittedBy', 123);
        $user_changeset_value = new MockTracker_Artifact_ChangesetValue();
        $contributors = array(121);
        $user_changeset_value->setReturnReference('getValue', $contributors);
        $artifact_subass->setReturnReference('getValue', $user_changeset_value, array($contributor_field));

        $this->assertTrue($this->permission_checker->userCanView($this->submitter, $artifact_subass));
        $this->assertTrue($this->permission_checker->userCanView($this->u_sub, $artifact_subass));
        $this->assertTrue($this->permission_checker->userCanView($this->assignee, $artifact_subass));
        $this->assertTrue($this->permission_checker->userCanView($this->u_ass, $artifact_subass));
        $this->assertFalse($this->permission_checker->userCanView($this->other, $artifact_subass));
        $this->assertFalse($this->permission_checker->userCanView($this->user, $artifact_subass));
    }

    function testUserCanViewTrackerAccessFull() {
        $ugroup_ass = 101;
        $ugroup_sub = 102;
        $ugroup_ful = 103;

        // $assignee is in (UgroupAss - ugroup_id=101)
        // $submitter is in (UgroupSub - ugroup_id=102)
        // $u is in (UgroupFul - ugroup_id=103);
        // $other do not belong to any ugroup
        //
        $u = mock('PFUser');
        $u->setReturnValue('getId', 120);
        $u->setReturnValue('isMemberOfUgroup', true,  array(103, 222));
        $u->setReturnValue('isMemberOfUgroup', false, array(101, 222));
        $u->setReturnValue('isMemberOfUgroup', false, array(102, 222));
        $u->setReturnValue('isSuperUser', false);

        //
        $assignee = mock('PFUser');
        $assignee->setReturnValue('getId', 121);
        $assignee->setReturnValue('isMemberOfUgroup', true,  array(101, 222));
        $assignee->setReturnValue('isMemberOfUgroup', false, array(102, 222));
        $assignee->setReturnValue('isMemberOfUgroup', false, array(103, 222));
        $assignee->setReturnValue('isSuperUser', false);
        //
        $submitter = mock('PFUser');
        $submitter->setReturnValue('getId', 122);
        $submitter->setReturnValue('isMemberOfUgroup', false, array(101, 222));
        $submitter->setReturnValue('isMemberOfUgroup', true,  array(102, 222));
        $submitter->setReturnValue('isMemberOfUgroup', false,  array(103, 222));
        $submitter->setReturnValue('isSuperUser', false);
        //
        $other = mock('PFUser');
        $other->setReturnValue('getId', 123);
        $other->setReturnValue('isMemberOfUgroup', false);
        $other->setReturnValue('isSuperUser', false);

        $user_manager = mock('UserManager');
        $user_manager->setReturnReference('getUserById', $u, array(120));
        $user_manager->setReturnReference('getUserById', $assignee, array(121));
        $user_manager->setReturnReference('getUserById', $submitter, array(122));
        $user_manager->setReturnReference('getUserById', $other, array(123));

        $project_manager = mock('ProjectManager');

        // $artifact_subass has been submitted by $submitter and assigned to $assignee
        // $u should have the right to see it.
        // $other, $submitter and assigned should not have the right to see it
        $permissions = array("PLUGIN_TRACKER_ACCESS_FULL" => array(0 => $ugroup_ful));
        $this->tracker->setReturnReference('getAuthorizedUgroupsByPermissionType', $permissions);

        $contributor_field = aMockField()->build();
        $this->tracker->setReturnReference('getContributorField', $contributor_field);
        $artifact_subass = mock('Tracker_Artifact');
        $artifact_subass->setReturnReference('getTracker', $this->tracker);
        $artifact_subass->setReturnValue('useArtifactPermissions', false);
        $artifact_subass->setReturnValue('getSubmittedBy', 123);
        $user_changeset_value = new MockTracker_Artifact_ChangesetValue();
        $contributors = array(121);
        $user_changeset_value->setReturnReference('getValue', $contributors);
        $artifact_subass->setReturnReference('getValue', $user_changeset_value, array($contributor_field));


        $permission_checker = new Tracker_Permission_PermissionChecker($user_manager, $project_manager);
        $this->assertFalse($permission_checker->userCanView($submitter, $artifact_subass));
        $this->assertFalse($permission_checker->userCanView($assignee, $artifact_subass));
        $this->assertFalse($permission_checker->userCanView($other, $artifact_subass));
        $this->assertTrue($permission_checker->userCanView($u, $artifact_subass));
    }
}

abstract class Tracker_Permission_PermissionChecker_SubmitterOnlyBaseTest extends TuleapTestCase {
    protected $user_manager;
    protected $permission_checker;

    protected $tracker;

    protected $user;
    protected $submitter;
    protected $ugroup_id_submitter_only;
    protected $artifact;

    public function setUp() {
        parent::setUp();

        $project = mock('Project');
        stub($project)->getID()->returns(120);
        stub($project)->isPublic()->returns(true);

        $this->user_manager       = mock('UserManager');
        $this->project_manager    = mock('ProjectManager');
        $this->permission_checker = new Tracker_Permission_PermissionChecker($this->user_manager, $this->project_manager);

        $this->tracker = mock('Tracker');
        $this->tracker->setReturnValue('getId', 666);
        $this->tracker->setReturnValue('getGroupId', 222);
        $this->tracker->setReturnValue('getProject',$project);

        $this->ugroup_id_submitter_only = 112;

        $this->user = mock('PFUser');
        stub($this->user)->getId()->returns(120);
        $this->user->setReturnValue('isMember', true, array(12));


        $this->submitter = mock('PFUser');
        stub($this->submitter)->getId()->returns(250);
        stub($this->submitter)->isMemberOfUgroup($this->ugroup_id_submitter_only, 222)->returns(true);
        $this->submitter->setReturnValue('isMember', true, array(12));


        stub($this->user_manager)->getUserById(120)->returns($this->user);
        stub($this->user_manager)->getUserById(250)->returns($this->submitter);

        $this->artifact = mock('Tracker_Artifact');
        stub($this->artifact)->getTracker()->returns($this->tracker);
    }
}

class Tracker_Permission_PermissionChecker_SubmitterOnlyTest extends Tracker_Permission_PermissionChecker_SubmitterOnlyBaseTest {

    public function setUp() {
        parent::setUp();

        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_SUBMITTER_ONLY => array(
                    0 => $this->ugroup_id_submitter_only
                )
            )
        );


        stub($this->artifact)->getSubmittedBy()->returns(250);
    }

    public function itDoesntSeeArtifactSubmittedByOthers() {
        $this->assertFalse($this->permission_checker->userCanView($this->user, $this->artifact));
    }

    public function itSeesArtifactSubmittedByThemselves() {
        $this->assertTrue($this->permission_checker->userCanView($this->submitter, $this->artifact));
    }
}

class Tracker_Permission_PermissionChecker_SubmitterOnlyAndAdminTest extends Tracker_Permission_PermissionChecker_SubmitterOnlyBaseTest {
    protected $ugroup_id_maintainers  = 111;
    protected $ugroup_id_admin        = 4;
    protected $ugroup_private_project = 114;

    protected $maintainer;
    protected $tracker_admin;
    protected $project_admin;

    public function setUp() {
        parent::setUp();

        stub($this->tracker)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_SUBMITTER_ONLY => array(
                    $this->ugroup_id_submitter_only
                ),
                Tracker::PERMISSION_FULL => array(
                    $this->ugroup_id_maintainers
                ),
                Tracker::PERMISSION_ADMIN => array(
                    $this->ugroup_id_admin
                )
            )
        );

        $this->restricted_user = mock('PFUser');
        $this->restricted_user->setReturnValue('getId', 249);
        $this->restricted_user->setReturnValue('isMemberOfUgroup',true, array(114, 223));
        $this->restricted_user->setReturnValue('isSuperUser', false);
        $this->restricted_user->setReturnValue('isMember', true, array(223));
        $this->restricted_user->setReturnValue('isMember', false, array(222));
        $this->restricted_user->setReturnValue('isRestricted', true);

        $this->not_member = mock('PFUser');
        $this->not_member->setReturnValue('getId', 250);
        $this->not_member->setReturnValue('isMemberOfUgroup',false);
        $this->not_member->setReturnValue('isSuperUser', false);
        $this->not_member->setReturnValue('isMember', false);
        $this->not_member->setReturnValue('isRestricted', false);

        $this->maintainer = mock('PFUser');
        stub($this->maintainer)->getId()->returns(251);
        stub($this->maintainer)->isMemberOfUgroup($this->ugroup_id_maintainers, 222)->returns(true);

        $this->tracker_admin = mock('PFUser');
        stub($this->tracker)->userIsAdmin($this->tracker_admin)->returns(true);

        $this->project_admin = mock('PFUser');
        stub($this->project_admin)->getId()->returns(253);
        stub($this->project_admin)->isMember(222, 'A')->returns(true);

        stub($this->artifact)->getSubmittedBy()->returns(250);

        $private_project            = stub('Project')->isPublic()->returns(false);
        $tracker_in_private_project = stub('Tracker')->getProject()->returns($private_project);

        stub($private_project)->getID()->returns(223);
        stub($tracker_in_private_project)->getGroupId()->returns(223);
        stub($tracker_in_private_project)->getAuthorizedUgroupsByPermissionType()->returns(
            array(
                Tracker::PERMISSION_FULL => array(
                    $this->ugroup_private_project
                )
            )
        );

        $this->artifact2 = stub('Tracker_Artifact')->getTracker()->returns($tracker_in_private_project);
    }

    public function itDoesntSeeArtifactSubmittedByOthers() {
        $this->assertFalse($this->permission_checker->userCanView($this->user, $this->artifact));
    }

    public function itSeesArtifactSubmittedByThemselves() {
        $this->assertTrue($this->permission_checker->userCanView($this->submitter, $this->artifact));
    }

    public function itSeesArtifactBecauseHeIsGrantedFullAccess() {
        $this->assertTrue($this->permission_checker->userCanView($this->maintainer, $this->artifact));
    }

    public function itSeesArtifactBecauseHeIsTrackerAdmin() {
        $this->assertTrue($this->permission_checker->userCanView($this->tracker_admin, $this->artifact));
    }
    
    public function itSeesArtifactBecauseHeIsProjectAdmin() {
        $this->assertTrue($this->permission_checker->userCanView($this->project_admin, $this->artifact));
    }

    public function itDoesNotSeeArtifactBecauseHeIsRestricted() {
        $this->assertFalse($this->permission_checker->userCanView($this->restricted_user, $this->artifact));
    }

    public function itSeesTheArtifactBecauseHeIsRestrictedAndProjectMember() {
        $this->assertTrue($this->permission_checker->userCanView($this->restricted_user, $this->artifact2));
    }

    public function itDoesNotSeeArtifactBecauseHeIsNotProjectMemberOfAPrivateProject() {
        $this->assertFalse($this->permission_checker->userCanView($this->not_member, $this->artifact2));
    }
}
