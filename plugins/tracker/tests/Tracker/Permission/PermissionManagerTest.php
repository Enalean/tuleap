<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

abstract class Tracker_Permission_PermissionManager_BaseTest extends TuleapTestCase
{
    protected $minimal_ugroup_list;
    protected $permission_setter;
    protected $permission_manager;
    protected $permissions_manager;
    protected $tracker;
    protected $tracker_id;
    protected $project_id;
    protected $permissions;

    public function setUp()
    {
        parent::setUp();
        $this->minimal_ugroup_list = array(
            ProjectUGroup::ANONYMOUS,
            ProjectUGroup::REGISTERED,
            ProjectUGroup::PROJECT_MEMBERS,
            ProjectUGroup::PROJECT_ADMIN
        );

        $this->tracker_id  = 112;
        $this->project_id  = 34;
        $this->tracker = mock('Tracker');
        stub($this->tracker)->getId()->returns($this->tracker_id);
        stub($this->tracker)->getGroupId()->returns($this->project_id);
        $this->permissions = array(
            ProjectUGroup::ANONYMOUS => array(
                'ugroup'      => array('name' => 'whatever'),
                'permissions' => array()
            ),
            ProjectUGroup::REGISTERED => array(
                'ugroup'      => array('name' => 'whatever'),
                'permissions' => array()
            ),
            ProjectUGroup::PROJECT_MEMBERS => array(
                'ugroup'      => array('name' => 'whatever'),
                'permissions' => array()
            ),
            ProjectUGroup::PROJECT_ADMIN => array(
                'ugroup'      => array('name' => 'whatever'),
                'permissions' => array()
            ),
        );
        $this->permissions_manager = mock('PermissionsManager');
        $this->permission_setter    = new Tracker_Permission_PermissionSetter($this->tracker, $this->permissions, $this->permissions_manager);
        $this->permission_manager   = new Tracker_Permission_PermissionManager();
    }
}

class Tracker_Permission_PermissionManager_SubmitterOnlyTest extends Tracker_Permission_PermissionManager_BaseTest
{

    public function itDoesNothingTryingToGrantAnonymousSubmittedOnly()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY
        ));

        expect($this->permissions_manager)->addPermission()->never();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itGrantsRegisteredSubmittedOnly()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::REGISTERED => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY
        ));

        expect($this->permissions_manager)->addPermission(Tracker::PERMISSION_SUBMITTER_ONLY, $this->tracker_id, ProjectUGroup::REGISTERED)->once();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itCannotGrantRegisterSubmittedOnlyWhenAnonymousHasFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS  => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::REGISTERED => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY
        ));

        expect($this->permissions_manager)->addPermission(Tracker::PERMISSION_FULL, $this->tracker_id, ProjectUGroup::ANONYMOUS)->once();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itRaisesAWarningWhenTryingToGrantRegisteredSubmittedOnlyWithAnonymousHasFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS  => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::REGISTERED => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY
        ));
        $this->permissions[ProjectUGroup::ANONYMOUS]['permissions'] = array(
            Tracker::PERMISSION_FULL => 1
        );

        expect($GLOBALS['Response'])->addFeedback(Feedback::WARN, '*')->once();

        $permission_setter = new Tracker_Permission_PermissionSetter($this->tracker, $this->permissions, $this->permissions_manager);
        $this->permission_manager->save($request, $permission_setter);
    }

    public function itGrantsProjectMembersSubmittedOnly()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY
        ));

        expect($this->permissions_manager)->addPermission(Tracker::PERMISSION_SUBMITTER_ONLY, $this->tracker_id, ProjectUGroup::PROJECT_MEMBERS)->once();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itRevokesPreviousPermissionWhenGrantsProjectMembersSubmittedOnly()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY
        ));

        $this->permissions[ProjectUGroup::PROJECT_MEMBERS]['permissions'] = array(
            Tracker::PERMISSION_FULL => 1
        );

        expect($this->permissions_manager)->addPermission(Tracker::PERMISSION_SUBMITTER_ONLY, $this->tracker_id, ProjectUGroup::PROJECT_MEMBERS)->once();
        expect($this->permissions_manager)->revokePermissionForUGroup(Tracker::PERMISSION_FULL, $this->tracker_id, ProjectUGroup::PROJECT_MEMBERS)->once();

        $permission_setter = new Tracker_Permission_PermissionSetter($this->tracker, $this->permissions, $this->permissions_manager);
        $this->permission_manager->save($request, $permission_setter);
    }
}

class Tracker_Permission_PermissionManager_AnonymousWithFullAccessTest extends Tracker_Permission_PermissionManager_BaseTest
{

    public function setUp()
    {
        parent::setUp();

        $permissions = array(
            ProjectUGroup::ANONYMOUS => array(
                'ugroup'      => array('name' => 'whatever'),
                'permissions' => array(
                    Tracker::PERMISSION_FULL => 1
                )
            ),
            ProjectUGroup::REGISTERED => array(
                'ugroup'      => array('name' => 'whatever'),
                'permissions' => array()
            ),
            ProjectUGroup::PROJECT_MEMBERS => array(
                'ugroup'      => array('name' => 'whatever'),
                'permissions' => array()
            ),
        );

        $this->permission_setter  = new Tracker_Permission_PermissionSetter($this->tracker, $permissions, $this->permissions_manager);
    }

    public function itWarnsWhenAnonymousHaveFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS  => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::REGISTERED => Tracker_Permission_Command::PERMISSION_FULL,
        ));

        expect($GLOBALS['Response'])->addFeedback(Feedback::WARN, '*')->once();

        $this->permission_manager->save($request, $this->permission_setter);
    }


    public function itWarnsTwiceWhenAnonymousHaveFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::REGISTERED      => Tracker_Permission_Command::PERMISSION_SUBMITTER,
            ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
        ));

        expect($GLOBALS['Response'])->addFeedback()->count(2);
        expect($GLOBALS['Response'])->addFeedback(Feedback::WARN, '*')->at(0);
        expect($GLOBALS['Response'])->addFeedback(Feedback::WARN, '*')->at(1);

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itDoesntGrantFullAccessToRegisteredWhenAnonymousHaveFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS  => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::REGISTERED => Tracker_Permission_Command::PERMISSION_FULL,
        ));

        expect($this->permissions_manager)->addPermission()->never();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itDoesntGrantSubmitterOnlyToRegisteredWhenAnonymousHaveFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
           ProjectUGroup::ANONYMOUS  => Tracker_Permission_Command::PERMISSION_FULL,
           ProjectUGroup::REGISTERED => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
        ));

        expect($this->permissions_manager)->addPermission()->never();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itDoesntGrantFullAccessToProjectMembersWhenAnonymousHaveFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_FULL,
        ));

        expect($this->permissions_manager)->addPermission()->never();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itDoesntGrantSubmitterToProjectMembersWhenAnonymousHaveFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_SUBMITTER,
        ));

        expect($this->permissions_manager)->addPermission()->never();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itDoesntGrantAssigneeToProjectMembersWhenAnonymousHaveFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_ASSIGNEE,
        ));

        expect($this->permissions_manager)->addPermission()->never();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itDoesntGrantAssigneeAndSubmitterToProjectMembersWhenAnonymousHaveFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_ASSIGNEE_AND_SUBMITTER,
        ));

        expect($this->permissions_manager)->addPermission()->never();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itDoesntGrantSubmitterOnlyToProjectMembersWhenAnonymousHaveFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
        ));

        expect($this->permissions_manager)->addPermission()->never();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itRevokesPreExistingPermission()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
        ));
        $this->permissions[ProjectUGroup::ANONYMOUS]['permissions'] = array(
            Tracker::PERMISSION_FULL => 1
        );
        $this->permissions[ProjectUGroup::PROJECT_MEMBERS]['permissions'] = array(
            Tracker::PERMISSION_SUBMITTER_ONLY => 1
        );

        expect($this->permissions_manager)->addPermission()->never();
        expect($this->permissions_manager)->revokePermissionForUGroup(Tracker::PERMISSION_SUBMITTER_ONLY, $this->tracker_id, ProjectUGroup::PROJECT_MEMBERS)->once();

        $permission_setter = new Tracker_Permission_PermissionSetter($this->tracker, $this->permissions, $this->permissions_manager);
        $this->permission_manager->save($request, $permission_setter);
    }

    public function itRevokesAdminPermission()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS     => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_NONE,
        ));
        $this->permissions[ProjectUGroup::ANONYMOUS]['permissions'] = array(
            Tracker::PERMISSION_FULL => 1
        );
        $this->permissions[ProjectUGroup::PROJECT_MEMBERS]['permissions'] = array(
            Tracker::PERMISSION_ADMIN => 1
        );

        expect($this->permissions_manager)->addPermission()->never();
        expect($this->permissions_manager)->revokePermissionForUGroup(Tracker::PERMISSION_ADMIN, $this->tracker_id, ProjectUGroup::PROJECT_MEMBERS)->once();

        $permission_setter = new Tracker_Permission_PermissionSetter($this->tracker, $this->permissions, $this->permissions_manager);
        $this->permission_manager->save($request, $permission_setter);
    }
}

class Tracker_Permission_PermissionManager_RegisteredWithFullAccessTest extends Tracker_Permission_PermissionManager_BaseTest
{
    private $some_ugroupid = 369;

    public function setUp()
    {
        parent::setUp();

        $permissions = array(
            ProjectUGroup::ANONYMOUS => array(
                'ugroup'      => array('name' => 'whatever'),
                'permissions' => array()
            ),
            ProjectUGroup::REGISTERED => array(
                'ugroup'      => array('name' => 'whatever'),
                'permissions' => array(
                    Tracker::PERMISSION_FULL => 1
                )
            ),
            ProjectUGroup::PROJECT_MEMBERS => array(
                'ugroup'      => array('name' => 'whatever'),
                'permissions' => array()
            ),
            ProjectUGroup::PROJECT_ADMIN => array(
                'ugroup'      => array('name' => 'whatever'),
                'permissions' => array()
            ),
            $this->some_ugroupid => array(
                'ugroup'      => array('name' => 'whatever'),
                'permissions' => array()
            ),
        );

        $this->permission_setter  = new Tracker_Permission_PermissionSetter($this->tracker, $permissions, $this->permissions_manager);
    }


    public function itWarnsWhenRegisteredHaveFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::REGISTERED      => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_FULL,
        ));

        expect($GLOBALS['Response'])->addFeedback(Feedback::WARN, '*')->once();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itWarnsTwiceWhenRegisteredHaveFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS       => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::REGISTERED      => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS => Tracker_Permission_Command::PERMISSION_FULL,
            $this->some_ugroupid    => Tracker_Permission_Command::PERMISSION_FULL,
        ));

        expect($GLOBALS['Response'])->addFeedback()->count(2);
        expect($GLOBALS['Response'])->addFeedback(Feedback::WARN, '*')->at(0);
        expect($GLOBALS['Response'])->addFeedback(Feedback::WARN, '*')->at(1);

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itDoesntGrantFullAccessToProjectMembersWhenAnonymousHaveFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS        => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::REGISTERED       => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS  => Tracker_Permission_Command::PERMISSION_FULL,
        ));

        expect($this->permissions_manager)->addPermission()->never();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itDoesntGrantSubmitterToProjectMembersWhenRegisteredHaveFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS        => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::REGISTERED       => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS  => Tracker_Permission_Command::PERMISSION_SUBMITTER,
        ));

        expect($this->permissions_manager)->addPermission()->never();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itDoesntGrantAssigneeToProjectMembersWhenRegisteredHaveFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS        => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::REGISTERED       => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS  => Tracker_Permission_Command::PERMISSION_ASSIGNEE,
        ));

        expect($this->permissions_manager)->addPermission()->never();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itDoesntGrantAssigneeAndSubmitterToProjectMembersWhenRegisteredHaveFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS        => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::REGISTERED       => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS  => Tracker_Permission_Command::PERMISSION_ASSIGNEE_AND_SUBMITTER,
        ));

        expect($this->permissions_manager)->addPermission()->never();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itDoesntGrantSubmitterOnlyToProjectMembersWhenRegisteredHaveFullAccess()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS        => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::REGISTERED       => Tracker_Permission_Command::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS  => Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY,
        ));

        expect($this->permissions_manager)->addPermission()->never();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }
}

class Tracker_Permission_PermissionManager_CheckRequestValidityTest extends Tracker_Permission_PermissionManager_BaseTest
{

    public function itDisplaysAFeedbackErrorIfAssignedToSemanticIsNotDefined()
    {
        stub($this->tracker)->getContributorField()->returns(null);
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS        => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::REGISTERED       => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::PROJECT_MEMBERS  => Tracker_Permission_Command::PERMISSION_ASSIGNEE,
        ));

        expect($this->permissions_manager)->addPermission()->never();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();
        expect($GLOBALS['Response'])->addFeedback(Feedback::ERROR, '*', '*')->once();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itDoesNotDisplayAFeedbackErrorIfAssignedToSemanticIsDefined()
    {
        stub($this->tracker)->getContributorField()->returns(aMockField()->build());
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS        => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::REGISTERED       => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::PROJECT_MEMBERS  => Tracker_Permission_Command::PERMISSION_ASSIGNEE,
        ));

        expect($this->permissions_manager)->addPermission()->once();
        expect($GLOBALS['Response'])->addFeedback(Feedback::INFO, '*')->once();

        $this->permission_manager->save($request, $this->permission_setter);
    }

    public function itDoesNotApplyPermissionsOnProjectAdmins()
    {
        $request = new Tracker_Permission_PermissionRequest(array(
            ProjectUGroup::ANONYMOUS        => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::REGISTERED       => Tracker_Permission_Command::PERMISSION_NONE,
            ProjectUGroup::PROJECT_ADMIN    => Tracker_Permission_Command::PERMISSION_FULL,
        ));

        expect($this->permissions_manager)->addPermission()->never();
        expect($this->permissions_manager)->revokePermissionForUGroup()->never();

        $this->permission_manager->save($request, $this->permission_setter);
    }
}
