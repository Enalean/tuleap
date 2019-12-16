<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types = 1);

namespace Tuleap\common\Project\Admin\Service;

use EventManager;
use Mockery;
use PHPUnit\Framework\TestCase;
use ServiceDao;
use Tuleap\Project\Admin\Service\ProjectServiceActivator;
use Tuleap\Service\ServiceCreator;

final class ProjectServiceActivatorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var ProjectServiceActivator
     */
    private $service_activator;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ServiceDao
     */
    private $service_dao;
    /**
     * @var EventManager|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $event_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ServiceCreator
     */
    private $service_creator;

    protected function setUp(): void
    {
        $this->service_creator = Mockery::mock(ServiceCreator::class);
        $this->event_manager   = Mockery::mock(EventManager::class);
        $this->service_dao     = Mockery::mock(ServiceDao::class);

        $this->service_activator = new ProjectServiceActivator(
            $this->service_creator,
            $this->event_manager,
            $this->service_dao
        );
    }

    public function testServiceUsageIsInheritedFromData(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->once()->andReturn(101);

        $template = Mockery::mock(\Project::class);
        $template->shouldReceive('getID')->once()->andReturn(201);
        $template->shouldReceive('isSystem')->once()->andReturn(false);
        $template->shouldReceive('getUnixName')->once()->andReturn('test-name');

        $data = Mockery::mock(\ProjectCreationData::class);
        $data->shouldReceive('getServiceInfo')->andReturn(['is_used' => true]);

        $template_service = ['service_id' => 10, 'short_name' => 'document', 'is_used' => 0];
        $this->service_dao->shouldReceive('getServiceInfoQueryForNewProject')->andReturn(
            [
                $template_service
            ]
        );

        $this->service_creator->shouldReceive('createService')->withArgs(
            [
                $template_service,
                101,
                [
                    'system'  => false,
                    'name'    => 'test-name',
                    'id'      => 201,
                    'is_used' => 1,
                ]
            ]
        )->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->service_activator->activateServicesFromTemplate($project, $template, $data, []);
    }

    public function testServiceUsageIsInheritedFromTemplate(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->once()->andReturn(101);

        $template = Mockery::mock(\Project::class);
        $template->shouldReceive('getID')->once()->andReturn(201);
        $template->shouldReceive('isSystem')->once()->andReturn(false);
        $template->shouldReceive('getUnixName')->once()->andReturn('test-name');

        $data = Mockery::mock(\ProjectCreationData::class);
        $data->shouldReceive('getServiceInfo')->andReturn([]);

        $template_service = ['service_id' => 10, 'short_name' => 'document', 'is_used' => 1];
        $other_template_service = ['service_id' => 10, 'short_name' => 'document', 'is_used' => 1];
        $this->service_dao->shouldReceive('getServiceInfoQueryForNewProject')->andReturn(
            [
                $template_service,
                $other_template_service
            ]
        );

        $this->service_creator->shouldReceive('createService')->withArgs(
            [
                $template_service,
                101,
                [
                    'system'  => false,
                    'name'    => 'test-name',
                    'id'      => 201,
                    'is_used' => 1,
                ]
            ]
        )->once();
        $this->service_creator->shouldReceive('createService')->withArgs(
            [
                $other_template_service,
                101,
                [
                    'system'  => false,
                    'name'    => 'test-name',
                    'id'      => 201,
                    'is_used' => 1,
                ]
            ]
        )->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->service_activator->activateServicesFromTemplate($project, $template, $data, []);
    }

    public function testAdminServiceIsAlwaysActive(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->once()->andReturn(101);

        $template = \Mockery::mock(\Project::class);
        $template->shouldReceive('getID')->once()->andReturn(201);
        $template->shouldReceive('isSystem')->once()->andReturn(false);
        $template->shouldReceive('getUnixName')->once()->andReturn('test-name');

        $data = \Mockery::mock(\ProjectCreationData::class);
        $data->shouldReceive('getServiceInfo')->andReturn([]);

        $template_service = ['service_id' => 10, 'short_name' => 'admin', 'is_used' => 0];
        $this->service_dao->shouldReceive('getServiceInfoQueryForNewProject')->andReturn(
            [
                $template_service
            ]
        );

        $this->service_creator->shouldReceive('createService')->withArgs(
            [
                $template_service,
                101,
                [
                    'system'  => false,
                    'name'    => 'test-name',
                    'id'      => 201,
                    'is_used' => 1,
                ]
            ]
        )->once();

        $this->event_manager->shouldReceive('processEvent')->once();

        $this->service_activator->activateServicesFromTemplate($project, $template, $data, []);
    }
}
