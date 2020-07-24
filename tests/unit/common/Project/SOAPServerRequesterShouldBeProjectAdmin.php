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
class SOAPServerRequesterShouldBeProjectAdmin extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->requester = \Mockery::spy(\PFUser::class)->shouldReceive('isLoggedIn')->andReturns(true)->getMock();
        $this->admin     = \Mockery::spy(\PFUser::class)->shouldReceive('isLoggedIn')->andReturns(true)->getMock();
        $this->admin->shouldReceive('isSuperUser')->andReturns(true);

        $this->user_manager = \Mockery::spy(\UserManager::class);

        $this->project_manager           = \Mockery::spy(\ProjectManager::class);
        $this->project_creator           = \Mockery::spy(\ProjectCreator::class);
        $this->guf                       = \Mockery::spy(\GenericUserFactory::class);
        $this->limitator                 = \Mockery::spy(\SOAP_RequestLimitator::class);
        $this->description_factory       = \Mockery::spy(\Project_CustomDescription_CustomDescriptionFactory::class);
        $this->description_manager       = \Mockery::spy(\Project_CustomDescription_CustomDescriptionValueManager::class);
        $this->description_value_factory = \Mockery::spy(\Project_CustomDescription_CustomDescriptionValueFactory::class);
        $this->service_usage_factory     = \Mockery::spy(\Project_Service_ServiceUsageFactory::class);
        $this->service_usage_manager     = \Mockery::spy(\Project_Service_ServiceUsageManager::class);
        $this->forge_ugroup_perm_manager = \Mockery::spy(\User_ForgeUserGroupPermissionsManager::class);
        $this->permission_checker        = \Mockery::mock(ProjectRegistrationUserPermissionChecker::class);
        $this->permission_checker->shouldReceive('checkUserCreateAProject')->byDefault();

        $template = \Mockery::spy(\Project::class)->shouldReceive('isTemplate')->andReturns(true)->getMock();
        $template->shouldReceive('getServices')->andReturns([]);
        $this->project_manager->shouldReceive('getProject')->with($this->template_id)->andReturns($template);

        $this->project_creator->shouldReceive('create')->andReturns(\Mockery::spy(\Project::class));

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

    public function testItCallsCreateProjectWhileRequesterIsLoggedIn(): void
    {
        // see if it has project approval permissions
        $this->user_manager->shouldReceive('getCurrentUser')->with($this->requester_hash)
            ->andReturns($this->requester)
            ->times(2);
        // it is not the case, so check validity of site admin session hash
        $this->user_manager->shouldReceive('getCurrentUser')->with($this->admin_hash)
            ->once()
            ->andReturns($this->admin);
        // then set the current user to requester

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
