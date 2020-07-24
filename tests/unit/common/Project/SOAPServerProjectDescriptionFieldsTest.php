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
class SOAPServerProjectDescriptionFieldsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectRegistrationUserPermissionChecker
     */
    private $permission_checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project                    = \Mockery::spy(\Project::class)->shouldReceive('getId')->andReturns(101)->getMock();
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
        $this->forge_ugroup_perm_manager = \Mockery::spy(\User_ForgeUserGroupPermissionsManager::class);
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

    public function testItReturnsThePlatformProjectDescriptionFields(): void
    {
        $field1 = \Mockery::spy(\Project_CustomDescription_CustomDescription::class)->shouldReceive('getId')->andReturns(145)->getMock();
        $field1->shouldReceive('getName')->andReturns('champs 1');
        $field1->shouldReceive('isRequired')->andReturns(true);
        $field2 = \Mockery::spy(\Project_CustomDescription_CustomDescription::class)->shouldReceive('getId')->andReturns(255)->getMock();
        $field2->shouldReceive('getName')->andReturns('champs 2');
        $field2->shouldReceive('isRequired')->andReturns(false);

        $project_desc_fields = [
            $field1,
            $field2
        ];

        $expected = [
            0 => [
                'id' => 145,
                'name' => 'champs 1',
                'is_mandatory' => true
            ],

            1 => [
                'id' => 255,
                'name' => 'champs 2',
                'is_mandatory' => false
            ]
        ];

        $this->description_factory->shouldReceive('getCustomDescriptions')->andReturns($project_desc_fields);
        $this->user_manager->shouldReceive('getCurrentUser')->with($this->session_key)->andReturns($this->user);

        $this->assertEquals($expected, $this->server->getPlateformProjectDescriptionFields($this->session_key));
    }

    public function testItThrowsASOAPFaultIfNoDescriptionField(): void
    {
        $this->description_factory->shouldReceive('getCustomDescriptions')->andReturns([]);
        $this->user_manager->shouldReceive('getCurrentUser')->with($this->session_key)->andReturns($this->user);

        $this->expectException(SoapFault::class);
        $this->server->getPlateformProjectDescriptionFields($this->session_key);
    }

    public function testItUpdatesProjectDescriptionFields(): void
    {
        $field_id_to_update = 104;
        $field_value        = 'new_value_104';
        $group_id           = 101;

        $this->user_manager->shouldReceive('getCurrentUser')->with($this->session_key)->andReturns($this->user_admin);
        $this->description_factory->shouldReceive('getCustomDescription')->with(104)->andReturns(true);

        $this->description_manager->shouldReceive('setCustomDescription')->once();
        $this->server->setProjectDescriptionFieldValue($this->session_key, $group_id, $field_id_to_update, $field_value);
    }

    public function testItThrowsASOAPFaultIfUserIsNotAdmin(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->with($this->session_key)->andReturns($this->user);

        $field_id_to_update = 104;
        $field_value        = 'new_value_104';
        $group_id           = 101;

        $this->expectException(SoapFault::class);
        $this->server->setProjectDescriptionFieldValue($this->session_key, $group_id, $field_id_to_update, $field_value);
    }

    public function testItReturnsTheProjectDescriptionFieldsValue(): void
    {
        $this->user_manager->shouldReceive('getCurrentUser')->with($this->session_key)->andReturns($this->user);
        $this->user_manager->shouldReceive('getUserByUserName')->with('User 01')->andReturns($this->user);

        $group_id = 101;

        $expected = [
            0 => [
                'id' => 145,
                'value' => 'valeur 1',
            ],

            1 => [
                'id' => 255,
                'value' => 'valeur 2',
            ]
        ];

        $this->description_value_factory->shouldReceive('getDescriptionFieldsValue')->with($this->project)->andReturns($expected);

        $result = $this->server->getProjectDescriptionFieldsValue($this->session_key, $group_id);
        $this->assertEquals($expected, $result);
    }
}
