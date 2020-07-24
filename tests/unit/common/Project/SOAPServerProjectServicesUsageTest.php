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
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class SOAPServerProjectServicesUsageTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectRegistrationUserPermissionChecker
     */
    private $permission_checker;

    /**
     * @var Project_SOAPServer
     */
    private $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group_id                   = 101;
        $this->project                    = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns($this->group_id)->getMock();
        $this->session_key                = 'abcde123';
        $this->project_manager            = \Mockery::spy(\ProjectManager::class)->shouldReceive('getProject')->andReturns($this->project)->getMock();
        $this->project_creator            = Mockery::mock(ProjectCreator::class);
        $this->user_manager               = Mockery::mock(UserManager::class);
        $this->generic_user_factory       = \Mockery::spy(\GenericUserFactory::class);
        $this->limitator                  = Mockery::mock(SOAP_RequestLimitator::class);
        $this->description_factory        = \Mockery::spy(\Project_CustomDescription_CustomDescriptionFactory::class);
        $this->description_manager        = \Mockery::spy(\Project_CustomDescription_CustomDescriptionValueManager::class);
        $this->description_value_factory  = \Mockery::spy(\Project_CustomDescription_CustomDescriptionValueFactory::class);
        $this->service_usage_factory      = \Mockery::spy(\Project_Service_ServiceUsageFactory::class);
        $this->service_usage_manager      = \Mockery::spy(\Project_Service_ServiceUsageManager::class);
        $this->forge_ugroup_perm_manager  = \Mockery::spy(\User_ForgeUserGroupPermissionsManager::class);
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

        $this->user       = \Mockery::spy(\PFUser::class)->shouldReceive('isLoggedIn')->andReturns(true)->getMock();
        $this->user_admin = \Mockery::spy(\PFUser::class)->shouldReceive('isLoggedIn')->andReturns(true)->getMock();
        $this->user_admin->shouldReceive('isMember')->with(101, 'A')->andReturns(true);
        $this->user->shouldReceive('isMember')->with(101)->andReturns(true);
        $this->user->shouldReceive('getUserName')->andReturns('User 01');
    }

    public function testItThrowsAnExceptionIfTheUserIsNotProjectAdmin(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->with($this->session_key)->andReturns($this->user);

        $this->expectException(SoapFault::class);
        $this->server->getProjectServicesUsage($this->session_key, $this->group_id);
    }

    public function testItThrowsAnExceptionIfProjectDoesNotExist(): void
    {
        $project_manager = \Mockery::spy(\ProjectManager::class)->shouldReceive('getProject')->andReturns(null)->getMock();
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

        $this->user_manager->shouldReceive('getCurrentUser')->with($this->session_key)->andReturns($this->user_admin);

        $this->expectException(SoapFault::class);
        $server->getProjectServicesUsage($this->session_key, $this->group_id);
    }

    public function testItReturnsTheServicesUsage(): void
    {
        $service_usage1 = \Mockery::spy(\Project_Service_ServiceUsage::class)->shouldReceive('getId')->andReturns(170)->getMock();
        $service_usage1->shouldReceive('getShortName')->andReturns('git');
        $service_usage1->shouldReceive('isUsed')->andReturns(true);

        $service_usage2 = \Mockery::spy(\Project_Service_ServiceUsage::class)->shouldReceive('getId')->andReturns(171)->getMock();
        $service_usage2->shouldReceive('getShortName')->andReturns('tracker');
        $service_usage2->shouldReceive('isUsed')->andReturns(false);

        $services_usages = [
            $service_usage1,
            $service_usage2
        ];

        $expected = [
            0 =>  [
                'id'         => 170,
                'short_name' => 'git',
                'is_used'    => 1
            ],
            1 =>  [
                'id'         => 171,
                'short_name' => 'tracker',
                'is_used'    => 0
            ]
        ];

        $this->service_usage_factory->shouldReceive('getAllServicesUsage')->with($this->project)->andReturns($services_usages);
        $this->user_manager->shouldReceive('getCurrentUser')->with($this->session_key)->andReturns($this->user_admin);

        $this->assertSame(
            $expected,
            $this->server->getProjectServicesUsage($this->session_key, $this->group_id)
        );
    }

    public function testItActivatesAService(): void
    {
        $service = \Mockery::spy(\Project_Service_ServiceUsage::class)->shouldReceive('getId')->andReturns(179)->getMock();

        $this->user_manager->shouldReceive('getCurrentUser')->with($this->session_key)->andReturns($this->user_admin);
        $this->service_usage_factory->shouldReceive('getServiceUsage')->with($this->project, 179)->andReturns($service);
        $this->service_usage_manager->shouldReceive('activateService')->with($this->project, $service)->andReturns(true);

        $this->assertTrue($this->server->activateService($this->session_key, $this->group_id, 179));
    }

    public function testItThrowsAnExceptionIfTheServiceDoesNotExistDuringActivation(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->with($this->session_key)->andReturns($this->user_admin);
        $this->service_usage_factory->shouldReceive('getServiceUsage')->with($this->project, 179)->andReturns(null);

        $this->expectException(SoapFault::class);
        $this->assertTrue($this->server->activateService($this->session_key, $this->group_id, 179));
    }

    public function testItDeactivatesAService(): void
    {
        $service = \Mockery::spy(\Project_Service_ServiceUsage::class)->shouldReceive('getId')->andReturns(179)->getMock();

        $this->user_manager->shouldReceive('getCurrentUser')->with($this->session_key)->andReturns($this->user_admin);
        $this->service_usage_factory->shouldReceive('getServiceUsage')->with($this->project, 179)->andReturns($service);
        $this->service_usage_manager->shouldReceive('deactivateService')->with($this->project, $service)->andReturns(true);

        $this->assertTrue($this->server->deactivateService($this->session_key, $this->group_id, 179));
    }

    public function testItThrowsAnExceptionIfTheServiceDoesNotExistDuringDeactivation(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->with($this->session_key)->andReturns($this->user_admin);
        $this->service_usage_factory->shouldReceive('getServiceUsage')->with($this->project, 179)->andReturns(null);

        $this->expectException(SoapFault::class);
        $this->assertTrue($this->server->deactivateService($this->session_key, $this->group_id, 179));
    }
}
