<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Project\Registration\LimitedToSiteAdministratorsException;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class SOAPServerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectRegistrationUserPermissionChecker
     */
    private $permission_checker;

    public function testAddProjectShouldFailWhenRequesterIsNotProjectAdmin()
    {
        $server = $this->givenASOAPServerWithBadTemplate();

        $sessionKey      = '123';
        $adminSessionKey = '456';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 101;

        // We don't care about the exception details
        $this->expectException(SoapFault::class);
        $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
    }

    /**
     *
     * @return Project_SOAPServer
     */
    private function givenASOAPServerWithBadTemplate()
    {
        $server = $this->givenASOAPServer();

        $this->user->shouldReceive('isMember')->andReturns(false);

        $template = Mockery::mock(Project::class);
        $template->shouldReceive('isTemplate')->andReturns(false);

        $this->pm->shouldReceive('getProject')->with(101)->andReturns($template);

        return $server;
    }

    public function testAddProjectWithoutAValidAdminSessionKeyShouldNotCreateProject()
    {
        $server = $this->givenASOAPServerReadyToCreate();

        $sessionKey      = '123';
        $adminSessionKey = '789';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 100;

        $this->expectException(\SoapFault::class);
        $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
    }

    public function testAddProjectShouldFaitWhenPermissionIsNotGranted()
    {
        $server = $this->givenASOAPServerReadyToCreate();

        $this->permission_checker->shouldReceive('checkUserCreateAProject')->andThrow(new LimitedToSiteAdministratorsException());

        $sessionKey      = '123';
        $adminSessionKey = '456';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 100;

        $this->expectException(SoapFault::class);

        $projectId = $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
        $this->assertEquals(3459, $projectId);
    }

    public function testAddProjectShouldCreateAProject()
    {
        $server = $this->givenASOAPServerReadyToCreate();

        $this->permission_checker->shouldReceive('checkUserCreateAProject')->andReturnTrue();

        $sessionKey      = '123';
        $adminSessionKey = '456';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 100;

        $this->limitator->shouldReceive('logCallTo')->once();

        $projectId = $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
        $this->assertEquals(3459, $projectId);
    }

    public function testAddProjectShouldFailIfQuotaExceeded()
    {
        $server = $this->givenASOAPServerReadyToCreate();
        $this->limitator->shouldReceive('logCallTo')->andThrows(new SOAP_NbRequestsExceedLimit_Exception());

        $sessionKey      = '123';
        $adminSessionKey = '456';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 100;

        $this->expectException(\SoapFault::class);
        $projectId = $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
        $this->assertEquals(3459, $projectId);
    }

    public function testAddProjectShouldNotFailWhenRequesterIsNotProjectAdminAndHasPermission()
    {
        $server = $this->givenASOAPServerReadyToCreate();

        $this->forge_ugroup_perm_manager->shouldReceive('doesUserHavePermission')->andReturns(true);

        $sessionKey      = '123';
        $adminSessionKey = null;
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 100;

        $this->limitator->shouldReceive('logCallTo')->once();

        $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
    }

    public function testAddProjectShouldFailWhenRequesterIsNotProjectAdminAndDoesNotHavePermission()
    {
        $server = $this->givenASOAPServerReadyToCreate();

        $this->forge_ugroup_perm_manager->shouldReceive('doesUserHavePermission')->andReturns(false);
        $this->um->shouldReceive('getCurrentUser')->with('')->andReturns($this->user);

        $sessionKey      = '123';
        $adminSessionKey = '';
        $shortName       = 'toto';
        $publicName      = 'Mon Toto';
        $privacy         = 'public';
        $templateId      = 100;

        $this->expectException(\SoapFault::class);
        $server->addProject($sessionKey, $adminSessionKey, $shortName, $publicName, $privacy, $templateId);
    }

    /**
     *
     * @return Project_SOAPServer
     */
    private function givenASOAPServerReadyToCreate()
    {
        $server = $this->givenASOAPServer();

        $another_user = \Mockery::spy(\PFUser::class);
        $another_user->shouldReceive('isLoggedIn')->andReturns(true);

        $this->um->shouldReceive('getCurrentUser')->with('789')->andReturns($another_user);

        $template = Mockery::mock(Project::class);
        $template->shouldReceive('getServices')->andReturns([]);
        $template->shouldReceive('isTemplate')->andReturns(true);
        $template->shouldReceive('isError')->andReturnFalse();
        $template->shouldReceive('isActive')->andReturnTrue();
        $this->pm->shouldReceive('getProject')->with(100)->andReturns($template);

        $new_project = Mockery::mock(Project::class);
        $new_project->shouldReceive('getID')->andReturns(3459);
        $new_project->shouldReceive('isError')->andReturnFalse();
        $new_project->shouldReceive('isActive')->andReturnTrue();

        $this->pc->shouldReceive('create')->with('toto', 'Mon Toto', Mockery::any(), Mockery::any())->andReturns($new_project);
        $this->pm->shouldReceive('activate')->with($new_project);

        return $server;
    }

    private function givenASOAPServer()
    {
        $this->user = \Mockery::spy(\PFUser::class);
        $this->user->shouldReceive('isLoggedIn')->andReturns(true);

        $admin  = \Mockery::spy(\PFUser::class);
        $admin->shouldReceive('isLoggedIn')->andReturns(true);
        $admin->shouldReceive('isSuperUser')->andReturns(true);

        $this->um = Mockery::mock(UserManager::class);
        $this->um->shouldReceive('getCurrentUser')->with('123')->andReturns($this->user);
        $this->um->shouldReceive('getCurrentUser')->with('456')->andReturns($admin);

        $this->pm                        = Mockery::mock(ProjectManager::class);
        $this->pc                        = Mockery::mock(ProjectCreator::class);
        $this->guf                       = \Mockery::spy(\GenericUserFactory::class);
        $this->limitator                 = Mockery::mock(SOAP_RequestLimitator::class);
        $this->description_factory       = \Mockery::spy(\Project_CustomDescription_CustomDescriptionFactory::class);
        $this->description_manager       = \Mockery::spy(\Project_CustomDescription_CustomDescriptionValueManager::class);
        $this->description_value_factory = \Mockery::spy(\Project_CustomDescription_CustomDescriptionValueFactory::class);
        $this->service_usage_factory     = \Mockery::spy(\Project_Service_ServiceUsageFactory::class);
        $this->service_usage_manager     = \Mockery::spy(\Project_Service_ServiceUsageManager::class);
        $this->forge_ugroup_perm_manager = \Mockery::spy(\User_ForgeUserGroupPermissionsManager::class);
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
