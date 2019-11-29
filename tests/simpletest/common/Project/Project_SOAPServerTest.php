<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Project\Registration\LimitedToSiteAdministratorsException;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;

Mock::generate('PFUser');
Mock::generate('UserManager');

Mock::generate('Project');
Mock::generate('ProjectManager');
Mock::generate('ProjectCreator');
Mock::generate('SOAP_RequestLimitator');

class Project_SOAPServerTest extends TuleapTestCase
{

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectRegistrationUserPermissionChecker
     */
    private $permission_checker;

    function testAddProjectShouldFailWhenRequesterIsNotProjectAdmin()
    {
        $server = $this->GivenASOAPServerWithBadTemplate();

        $sessionKey      = '123';
        $adminSessionKey = '456';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 101;

        // We don't care about the exception details
        $this->expectException();
        $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
    }

    /**
     *
     * @return Project_SOAPServer
     */
    private function GivenASOAPServerWithBadTemplate()
    {
        $server = $this->GivenASOAPServer();

        $this->user->setReturnValue('isMember', false);

        $template = new MockProject();
        $template->setReturnValue('isTemplate', false);

        $this->pm->setReturnValue('getProject', $template, array(101));

        return $server;
    }

    function testAddProjectWithoutAValidAdminSessionKeyShouldNotCreateProject()
    {
        $server = $this->GivenASOAPServerReadyToCreate();

        $sessionKey      = '123';
        $adminSessionKey = '789';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 100;

        $this->expectException('SoapFault');
        $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
    }

    function testAddProjectShouldFaitWhenPermissionIsNotGranted()
    {
        $server = $this->GivenASOAPServerReadyToCreate();

        $this->permission_checker->shouldReceive('checkUserCreateAProject')->andThrow(new LimitedToSiteAdministratorsException());

        $sessionKey      = '123';
        $adminSessionKey = '456';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 100;

        $this->expectException();

        $projectId = $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
        $this->assertEqual($projectId, 3459);
    }

    function testAddProjectShouldCreateAProject()
    {
        $server = $this->GivenASOAPServerReadyToCreate();

        $sessionKey      = '123';
        $adminSessionKey = '456';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 100;

        $projectId = $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
        $this->assertEqual($projectId, 3459);
    }

    function testAddProjectShouldFailIfQuotaExceeded()
    {
        $server = $this->GivenASOAPServerReadyToCreate();
        $this->limitator->throwOn('logCallTo', new SOAP_NbRequestsExceedLimit_Exception());

        $sessionKey      = '123';
        $adminSessionKey = '456';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 100;

        $this->expectException('SoapFault');
        $projectId = $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
        $this->assertEqual($projectId, 3459);
    }

    function testAddProjectShouldNotFailWhenRequesterIsNotProjectAdminAndHasPermission()
    {
        $server = $this->GivenASOAPServerReadyToCreate();

        stub($this->forge_ugroup_perm_manager)->doesUserHavePermission()->returns(true);

        $sessionKey      = '123';
        $adminSessionKey = null;
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 100;

        $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
    }

    function testAddProjectShouldFailWhenRequesterIsNotProjectAdminAndDoesNotHavePermission()
    {
        $server = $this->GivenASOAPServerReadyToCreate();

        stub($this->forge_ugroup_perm_manager)->doesUserHavePermission()->returns(false);

        $sessionKey      = '123';
        $adminSessionKey = '';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 100;

        $this->expectException('SoapFault');
        $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
    }

    /**
     *
     * @return Project_SOAPServer
     */
    private function GivenASOAPServerReadyToCreate()
    {
        $server = $this->GivenASOAPServer();

        $another_user = mock('PFUser');
        $another_user->setReturnValue('isLoggedIn', true);

        $this->um->setReturnValue('getCurrentUser', $another_user, array('789'));

        $template = new MockProject();
        stub($template)->getServices()->returns(array());
        $template->setReturnValue('isTemplate', true);
        $this->pm->setReturnValue('getProject', $template, array(100));

        $new_project = new MockProject();
        $new_project->setReturnValue('getID', 3459);
        $this->pc->setReturnValue('create', $new_project, array('toto', 'Mon Toto', '*', '*'));
        stub($this->pm)->activate(true)->returns($new_project);

        return $server;
    }

    private function GivenASOAPServer()
    {
        $this->user = mock('PFUser');
        $this->user->setReturnValue('isLoggedIn', true);

        $admin  = mock('PFUser');
        $admin->setReturnValue('isLoggedIn', true);
        $admin->setReturnValue('isSuperUser', true);

        $this->um = new MockUserManager();
        $this->um->setReturnValue('getCurrentUser', $this->user, array('123'));
        $this->um->setReturnValue('getCurrentUser', $admin, array('456'));

        $this->pm                        = new MockProjectManager();
        $this->pc                        = new MockProjectCreator();
        $this->guf                       = mock('GenericUserFactory');
        $this->limitator                 = new MockSOAP_RequestLimitator();
        $this->description_factory       = mock('Project_CustomDescription_CustomDescriptionFactory');
        $this->description_manager       = mock('Project_CustomDescription_CustomDescriptionValueManager');
        $this->description_value_factory = mock('Project_CustomDescription_CustomDescriptionValueFactory');
        $this->service_usage_factory     = mock('Project_Service_ServiceUsageFactory');
        $this->service_usage_manager     = mock('Project_Service_ServiceUsageManager');
        $this->forge_ugroup_perm_manager = mock('User_ForgeUserGroupPermissionsManager');
        $this->permission_checker        = \Mockery::mock(ProjectRegistrationUserPermissionChecker::class);
        $this->permission_checker->shouldReceive('checkUserCreateAProject')->byDefault();

        $server = new Project_SOAPServer(
            $this->pm,
            $this->pc,
            $this->um,
            $this->guf,
            $this->limitator,
            $this->description_factory,
            $this->description_manager,
            $this->description_value_factory,
            $this->service_usage_factory,
            $this->service_usage_manager,
            $this->forge_ugroup_perm_manager,
            $this->permission_checker
        );

        return $server;
    }
}

class Project_SOAPServer_6737_RequesterShouldBeProjectAdmin extends TuleapTestCase
{

    private $requester;
    private $requester_hash = '123';
    private $admin;
    private $admin_hash = '456';
    private $user_manager;
    private $server;
    private $project_manager;
    private $template_id = 100;
    private $project_creator;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectRegistrationUserPermissionChecker
     */
    private $permission_checker;

    public function setUp()
    {
        parent::setUp();

        $this->requester = stub('PFUser')->isLoggedIn()->returns(true);
        $this->admin     = stub('PFUser')->isLoggedIn()->returns(true);
        stub($this->admin)->isSuperUser()->returns(true);

        $this->user_manager = mock('UserManager');
        stub($this->user_manager)->getCurrentUser($this->requester_hash)->returns($this->requester);
        stub($this->user_manager)->getCurrentUser($this->admin_hash)->returns($this->admin);

        $this->project_manager           = mock('ProjectManager');
        $this->project_creator           = mock('ProjectCreator');
        $this->guf                       = mock('GenericUserFactory');
        $this->limitator                 = mock('SOAP_RequestLimitator');
        $this->description_factory       = mock('Project_CustomDescription_CustomDescriptionFactory');
        $this->description_manager       = mock('Project_CustomDescription_CustomDescriptionValueManager');
        $this->description_value_factory = mock('Project_CustomDescription_CustomDescriptionValueFactory');
        $this->service_usage_factory     = mock('Project_Service_ServiceUsageFactory');
        $this->service_usage_manager     = mock('Project_Service_ServiceUsageManager');
        $this->forge_ugroup_perm_manager = mock('User_ForgeUserGroupPermissionsManager');
        $this->permission_checker        = \Mockery::mock(ProjectRegistrationUserPermissionChecker::class);
        $this->permission_checker->shouldReceive('checkUserCreateAProject')->byDefault();

        $template = stub('Project')->isTemplate()->returns(true);
        stub($template)->getServices()->returns(array());
        stub($this->project_manager)->getProject($this->template_id)->returns($template);

        stub($this->project_creator)->create()->returns(mock('Project'));

        $this->server = new Project_SOAPServer(
            $this->project_manager,
            $this->project_creator,
            $this->user_manager,
            $this->guf,
            $this->limitator,
            $this->description_factory,
            $this->description_manager,
            $this->description_value_factory,
            $this->service_usage_factory,
            $this->service_usage_manager,
            $this->forge_ugroup_perm_manager,
            $this->permission_checker,
        );
    }

    public function itCallsCreateProjectWhileRequesterIsLoggedIn()
    {
        expect($this->user_manager)->getCurrentUser()->count(3);
        // see if it has project approval permissions
        expect($this->user_manager)->getCurrentUser($this->requester_hash)->at(0);
        // it is not the case, so check validity of site admin session hash
        expect($this->user_manager)->getCurrentUser($this->admin_hash)->at(1);
        // then set the current user to requester
        expect($this->user_manager)->getCurrentUser($this->requester_hash)->at(2);

        $this->server->addProject(
            $this->requester_hash,
            $this->admin_hash,
            'toto',
            'Mon Toto',
            'public',
            $this->template_id
        );
    }
}

class Project_SOAPServerObjectTest extends Project_SOAPServer
{
    public function isRequesterAdmin($sessionKey, $project_id)
    {
        parent::isRequesterAdmin($sessionKey, $project_id);
    }
}

class Project_SOAPServerGenericUserTest extends TuleapTestCase
{

    /** @var Project_SOAPServerObjectTest */
    private $server;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectRegistrationUserPermissionChecker
     */
    private $permission_checker;

    public function setUp()
    {
        parent::setUp();

        $this->group_id    = 154;
        $this->session_key = '123';
        $this->password    = 'pwd';

        $this->user = mock('GenericUser');
        $this->user->setReturnValue('isLoggedIn', true);

        $this->admin  = mock('PFUser');
        $this->admin->setReturnValue('isLoggedIn', true);
        $this->admin->setReturnValue('isSuperUser', true);

        $user_manager = new MockUserManager();

        $project = new MockProject();

        $project_manager            = stub('ProjectManager')->getProject($this->group_id)->returns($project);
        $project_creator            = new MockProjectCreator();
        $this->generic_user_factory = mock('GenericUserFactory');
        $limitator                  = new MockSOAP_RequestLimitator();
        $description_factory        = mock('Project_CustomDescription_CustomDescriptionFactory');
        $description_manager        = mock('Project_CustomDescription_CustomDescriptionValueManager');
        $description_value_factory  = mock('Project_CustomDescription_CustomDescriptionValueFactory');
        $service_usage_factory      = mock('Project_Service_ServiceUsageFactory');
        $service_usage_manager      = mock('Project_Service_ServiceUsageManager');
        $forge_ugroup_perm_manager  = mock('User_ForgeUserGroupPermissionsManager');
        $this->permission_checker        = \Mockery::mock(ProjectRegistrationUserPermissionChecker::class);
        $this->permission_checker->shouldReceive('checkUserCreateAProject')->byDefault();

        $this->server = partial_mock(
            'Project_SOAPServerObjectTest',
            array('isRequesterAdmin', 'addProjectMember', 'removeProjectMember'),
            array(
                    $project_manager,
                    $project_creator,
                    $user_manager,
                    $this->generic_user_factory,
                    $limitator,
                    $description_factory,
                    $description_manager,
                    $description_value_factory,
                    $service_usage_factory,
                    $service_usage_manager,
                    $forge_ugroup_perm_manager,
                    $this->permission_checker,
                )
        );

        stub($this->server)->isRequesterAdmin($this->session_key, $this->group_id)->returns(true);
        stub($this->generic_user_factory)->create($this->group_id, $this->password)->returns($this->user);
        stub($this->user)->getUserName()->returns('User1');
        stub($user_manager)->getCurrentUser()->returns($this->admin);
    }

    public function itCreatesANewGenericUser()
    {
        stub($this->generic_user_factory)->fetch($this->group_id)->returns(null);

        expect($this->generic_user_factory)->create($this->group_id, $this->password)->once();
        expect($this->server)->addProjectMember()->once();

        $this->server->setProjectGenericUser($this->session_key, $this->group_id, $this->password);
    }

    public function itDoesNotRecreateAGenericUserIfItAlreadyExists()
    {
        stub($this->generic_user_factory)->fetch($this->group_id)->returns($this->user);

        expect($this->generic_user_factory)->create($this->group_id, $this->password)->never();
        expect($this->server)->addProjectMember()->once();

        $this->server->setProjectGenericUser($this->session_key, $this->group_id, $this->password);
    }

    public function itUnsetsGenericUser()
    {
        stub($this->generic_user_factory)->fetch($this->group_id)->returns($this->user);

        expect($this->server)->removeProjectMember()->once();

        $this->server->unsetGenericUser($this->session_key, $this->group_id);
    }

    public function itThrowsASoapFaultWhileUnsetingGenericUserIfItIsNotActivated()
    {
        stub($this->generic_user_factory)->fetch($this->group_id)->returns(null);

        $this->expectException();

        $this->server->unsetGenericUser($this->session_key, $this->group_id);
    }
}

class Project_SOAPServerProjectDescriptionFieldsTest extends TuleapTestCase
{

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectRegistrationUserPermissionChecker
     */
    private $permission_checker;

    public function setUp()
    {
        parent::setUp();

        $this->project                    = stub('Project')->getId()->returns(101);
        $this->session_key                = 'abcde123';
        $this->project_manager            = stub('ProjectManager')->getProject()->returns($this->project);
        $this->project_creator            = new MockProjectCreator();
        $this->user_manager               = new MockUserManager();
        $this->generic_user_factory       = mock('GenericUserFactory');
        $this->limitator                  = new MockSOAP_RequestLimitator();
        $this->description_factory        = mock('Project_CustomDescription_CustomDescriptionFactory');
        $this->description_manager        = mock('Project_CustomDescription_CustomDescriptionValueManager');
        $this->description_value_factory  = mock('Project_CustomDescription_CustomDescriptionValueFactory');
        $this->service_usage_factory      = mock('Project_Service_ServiceUsageFactory');
        $this->service_usage_manager      = mock('Project_Service_ServiceUsageManager');
        $this->forge_ugroup_perm_manager = mock('User_ForgeUserGroupPermissionsManager');
        $this->permission_checker        = \Mockery::mock(ProjectRegistrationUserPermissionChecker::class);
        $this->permission_checker->shouldReceive('checkUserCreateAProject')->byDefault();

        $this->server = new Project_SOAPServer(
            $this->project_manager,
            $this->project_creator,
            $this->user_manager,
            $this->generic_user_factory,
            $this->limitator,
            $this->description_factory,
            $this->description_manager,
            $this->description_value_factory,
            $this->service_usage_factory,
            $this->service_usage_manager,
            $this->forge_ugroup_perm_manager,
            $this->permission_checker,
        );

        $this->user       = stub('PFUser')->isLoggedIn()->returns(true);
        $this->user_admin = stub('PFUser')->isLoggedIn()->returns(true);
        stub($this->user_admin)->isMember(101, 'A')->returns(true);
        stub($this->user)->isMember(101)->returns(true);
        stub($this->user)->getUserName()->returns('User 01');
    }

    public function itReturnsThePlatformProjectDescriptionFields()
    {
        $field1 = stub('Project_CustomDescription_CustomDescription')->getId()->returns(145);
        stub($field1)->getName()->returns('champs 1');
        stub($field1)->isRequired()->returns(true);
        $field2 = stub('Project_CustomDescription_CustomDescription')->getId()->returns(255);
        stub($field2)->getName()->returns('champs 2');
        stub($field2)->isRequired()->returns(false);

        $project_desc_fields = array(
            $field1,
            $field2
        );

        $expected = array(
            0 => array(
                'id' => 145,
                'name' => 'champs 1',
                'is_mandatory' => true
            ),

            1 => array(
                'id' => 255,
                'name' => 'champs 2',
                'is_mandatory' => false
            )
        );

        stub($this->description_factory)->getCustomDescriptions()->returns($project_desc_fields);
        stub($this->user_manager)->getCurrentUser($this->session_key)->returns($this->user);

        $this->assertEqual($expected, $this->server->getPlateformProjectDescriptionFields($this->session_key));
    }

    public function itThrowsASOAPFaultIfNoDescriptionField()
    {
        stub($this->description_factory)->getCustomDescriptions()->returns(array());
        stub($this->user_manager)->getCurrentUser($this->session_key)->returns($this->user);

        $this->expectException();
        $this->server->getPlateformProjectDescriptionFields($this->session_key);
    }

    public function itUpdatesProjectDescriptionFields()
    {
        $field_id_to_update = 104;
        $field_value        = 'new_value_104';
        $group_id           = 101;

        stub($this->user_manager)->getCurrentUser($this->session_key)->returns($this->user_admin);
        stub($this->description_factory)->getCustomDescription(104)->returns(true);

        expect($this->description_manager)->setCustomDescription()->once();
        $this->server->setProjectDescriptionFieldValue($this->session_key, $group_id, $field_id_to_update, $field_value);
    }

    public function itThrowsASOAPFaultIfUserIsNotAdmin()
    {
        stub($this->user_manager)->getCurrentUser($this->session_key)->returns($this->user);

        $field_id_to_update = 104;
        $field_value        = 'new_value_104';
        $group_id           = 101;

        $this->expectException();
        $this->server->setProjectDescriptionFieldValue($this->session_key, $group_id, $field_id_to_update, $field_value);
    }

    public function itReturnsTheProjectDescriptionFieldsValue()
    {
        stub($this->user_manager)->getCurrentUser($this->session_key)->returns($this->user);
        stub($this->user_manager)->getUserByUserName('User 01')->returns($this->user);

        $group_id = 101;

        $expected = array(
            0 => array(
                'id' => 145,
                'value' => 'valeur 1',
            ),

            1 => array(
                'id' => 255,
                'value' => 'valeur 2',
            )
        );

        stub($this->description_value_factory)->getDescriptionFieldsValue($this->project)->returns($expected);

        $result = $this->server->getProjectDescriptionFieldsValue($this->session_key, $group_id);
        $this->assertEqual($result, $expected);
    }
}

class Project_SOAPServerProjectServicesUsageTest extends TuleapTestCase
{

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectRegistrationUserPermissionChecker
     */
    private $permission_checker;

    public function setUp()
    {
        parent::setUp();

        $this->group_id                   = 101;
        $this->project                    = stub('Project')->getId()->returns($this->group_id);
        $this->session_key                = 'abcde123';
        $this->project_manager            = stub('ProjectManager')->getProject()->returns($this->project);
        $this->project_creator            = new MockProjectCreator();
        $this->user_manager               = new MockUserManager();
        $this->generic_user_factory       = mock('GenericUserFactory');
        $this->limitator                  = new MockSOAP_RequestLimitator();
        $this->description_factory        = mock('Project_CustomDescription_CustomDescriptionFactory');
        $this->description_manager        = mock('Project_CustomDescription_CustomDescriptionValueManager');
        $this->description_value_factory  = mock('Project_CustomDescription_CustomDescriptionValueFactory');
        $this->service_usage_factory      = mock('Project_Service_ServiceUsageFactory');
        $this->service_usage_manager      = mock('Project_Service_ServiceUsageManager');
        $this->forge_ugroup_perm_manager  = mock('User_ForgeUserGroupPermissionsManager');
        $this->permission_checker        = \Mockery::mock(ProjectRegistrationUserPermissionChecker::class);
        $this->permission_checker->shouldReceive('checkUserCreateAProject')->byDefault();

        $this->server = new Project_SOAPServer(
            $this->project_manager,
            $this->project_creator,
            $this->user_manager,
            $this->generic_user_factory,
            $this->limitator,
            $this->description_factory,
            $this->description_manager,
            $this->description_value_factory,
            $this->service_usage_factory,
            $this->service_usage_manager,
            $this->forge_ugroup_perm_manager,
            $this->permission_checker,
        );

        $this->user       = stub('PFUser')->isLoggedIn()->returns(true);
        $this->user_admin = stub('PFUser')->isLoggedIn()->returns(true);
        stub($this->user_admin)->isMember(101, 'A')->returns(true);
        stub($this->user)->isMember(101)->returns(true);
        stub($this->user)->getUserName()->returns('User 01');
    }

    public function itThrowsAnExceptionIfTheUserIsNotProjectAdmin()
    {
        stub($this->user_manager)->getCurrentUser($this->session_key)->returns($this->user);

        $this->expectException();
        $this->server->getProjectServicesUsage($this->session_key, $this->group_id);
    }

    public function itThrowsAnExceptionIfProjectDoesNotExist()
    {
        $project_manager = stub('ProjectManager')->getProject()->returns(null);
        $server          = new Project_SOAPServer(
            $project_manager,
            $this->project_creator,
            $this->user_manager,
            $this->generic_user_factory,
            $this->limitator,
            $this->description_factory,
            $this->description_manager,
            $this->description_value_factory,
            $this->service_usage_factory,
            $this->service_usage_manager,
            $this->forge_ugroup_perm_manager,
            $this->permission_checker,
        );

        stub($this->user_manager)->getCurrentUser($this->session_key)->returns($this->user_admin);

        $this->expectException();
        $server->getProjectServicesUsage($this->session_key, $this->group_id);
    }

    public function itReturnsTheServicesUsage()
    {
        $service_usage1 = stub('Project_Service_ServiceUsage')->getId()->returns(170);
        stub($service_usage1)->getShortName()->returns('git');
        stub($service_usage1)->isUsed()->returns(true);

        $service_usage2 = stub('Project_Service_ServiceUsage')->getId()->returns(171);
        stub($service_usage2)->getShortName()->returns('tracker');
        stub($service_usage2)->isUsed()->returns(false);

        $services_usages = array(
            $service_usage1,
            $service_usage2
        );

        $expected = array(
          0 => array (
              'id'         => 170,
              'short_name' => 'git',
              'is_used'    => 1
          ),
          1 => array (
              'id'         => 171,
              'short_name' => 'tracker',
              'is_used'    => 0
          )
        );

        stub($this->service_usage_factory)->getAllServicesUsage($this->project)->returns($services_usages);
        stub($this->user_manager)->getCurrentUser($this->session_key)->returns($this->user_admin);

        $this->assertIdentical($this->server->getProjectServicesUsage($this->session_key, $this->group_id), $expected);
    }

    public function itActivatesAService()
    {
        $service = stub('Project_Service_ServiceUsage')->getId()->returns(179);

        stub($this->user_manager)->getCurrentUser($this->session_key)->returns($this->user_admin);
        stub($this->service_usage_factory)->getServiceUsage($this->project, 179)->returns($service);
        stub($this->service_usage_manager)->activateService($this->project, $service)->returns(true);

        $this->assertTrue($this->server->activateService($this->session_key, $this->group_id, 179));
    }

    public function itThrowsAnExceptionIfTheServiceDoesNotExistDuringActivation()
    {
        stub($this->user_manager)->getCurrentUser($this->session_key)->returns($this->user_admin);
        stub($this->service_usage_factory)->getServiceUsage($this->project, 179)->returns(null);

        $this->expectException();
        $this->assertTrue($this->server->activateService($this->session_key, $this->group_id, 179));
    }

    public function itDeactivatesAService()
    {
        $service = stub('Project_Service_ServiceUsage')->getId()->returns(179);

        stub($this->user_manager)->getCurrentUser($this->session_key)->returns($this->user_admin);
        stub($this->service_usage_factory)->getServiceUsage($this->project, 179)->returns($service);
        stub($this->service_usage_manager)->deactivateService($this->project, $service)->returns(true);

        $this->assertTrue($this->server->deactivateService($this->session_key, $this->group_id, 179));
    }

    public function itThrowsAnExceptionIfTheServiceDoesNotExistDuringDeactivation()
    {
        stub($this->user_manager)->getCurrentUser($this->session_key)->returns($this->user_admin);
        stub($this->service_usage_factory)->getServiceUsage($this->project, 179)->returns(null);

        $this->expectException();
        $this->assertTrue($this->server->deactivateService($this->session_key, $this->group_id, 179));
    }
}
