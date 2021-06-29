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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class SOAPServerGenericUserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Project_SOAPServer */
    private $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group_id    = 154;
        $this->session_key = '123';
        $this->password    = new \Tuleap\Cryptography\ConcealedString('pwd');

        $this->user = \Mockery::spy(\GenericUser::class);
        $this->user->shouldReceive('isLoggedIn')->andReturns(true);

        $this->admin = \Mockery::spy(\PFUser::class);
        $this->admin->shouldReceive('isLoggedIn')->andReturns(true);
        $this->admin->shouldReceive('isSuperUser')->andReturns(true);

        $user_manager = Mockery::mock(UserManager::class);

        $project = Mockery::mock(Project::class);

        $project_manager            = \Mockery::spy(\ProjectManager::class)->shouldReceive('getProject')->with($this->group_id)->andReturns($project)->getMock();
        $this->generic_user_factory = \Mockery::spy(\GenericUserFactory::class);
        $description_factory        = \Mockery::spy(\Project_CustomDescription_CustomDescriptionFactory::class);
        $description_manager        = \Mockery::spy(\Project_CustomDescription_CustomDescriptionValueManager::class);
        $description_value_factory  = \Mockery::spy(\Project_CustomDescription_CustomDescriptionValueFactory::class);
        $service_usage_factory      = \Mockery::spy(\Project_Service_ServiceUsageFactory::class);
        $service_usage_manager      = \Mockery::spy(\Project_Service_ServiceUsageManager::class);

        $this->server = \Mockery::mock(
            \Project_SOAPServer::class,
            [
                $project_manager,
                $user_manager,
                $this->generic_user_factory,
                $description_factory,
                $description_manager,
                $description_value_factory,
                $service_usage_factory,
                $service_usage_manager,
            ]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->server->shouldReceive('isRequesterAdmin')->with($this->session_key, $this->group_id)->andReturns(true);
        $this->user->shouldReceive('getUserName')->andReturns('User1');
        $user_manager->shouldReceive('getCurrentUser')->andReturns($this->admin);
    }

    public function testItCreatesANewGenericUser()
    {
        $this->generic_user_factory->shouldReceive('fetch')->with($this->group_id)->andReturns(null);

        $this->generic_user_factory->shouldReceive('create')
            ->once()
            ->with($this->group_id, Mockery::type(\Tuleap\Cryptography\ConcealedString::class))
            ->andReturns($this->user);

        $this->server->shouldReceive('addProjectMember')->once();

        $this->server->setProjectGenericUser($this->session_key, $this->group_id, $this->password);
    }

    public function testItDoesNotRecreateAGenericUserIfItAlreadyExists()
    {
        $this->generic_user_factory->shouldReceive('fetch')->with($this->group_id)->andReturns($this->user);

        $this->generic_user_factory->shouldReceive('create')->with($this->group_id, $this->password)->never();
        $this->server->shouldReceive('addProjectMember')->once();

        $this->server->setProjectGenericUser($this->session_key, $this->group_id, $this->password);
    }

    public function testItUnsetsGenericUser()
    {
        $this->generic_user_factory->shouldReceive('fetch')->with($this->group_id)->andReturns($this->user);

        $this->server->shouldReceive('removeProjectMember')->once();

        $this->server->unsetGenericUser($this->session_key, $this->group_id);
    }

    public function testItThrowsASoapFaultWhileUnsetingGenericUserIfItIsNotActivated()
    {
        $this->generic_user_factory->shouldReceive('fetch')->with($this->group_id)->andReturns(null);

        $this->expectException(SoapFault::class);

        $this->server->unsetGenericUser($this->session_key, $this->group_id);
    }
}
