<?php
/**
 * Copyright (c) Enalean, 2016- Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Service;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Service;
use Tuleap\Project\ProjectCreationData;

final class ServiceActivatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    private ServiceActivator $activator;
    /**
     * @var \Project&\Mockery\MockInterface
     */
    private $template;
    private $tracker_core_service;
    private $tracker_plugin_service;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectCreationData
     */
    private $project_creation_data;
    private $tracker_v3;
    private $service_manager;
    private $service_creator;
    private array $params;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tracker_v3      = \Mockery::spy(\TrackerV3::class);
        $this->service_manager = \Mockery::spy(\ServiceManager::class);
        $this->service_creator = \Mockery::spy(\Tuleap\Service\ServiceCreator::class);
        $this->activator       = new ServiceActivator($this->service_manager, $this->tracker_v3, $this->service_creator);

        $this->template              = \Mockery::spy(\Project::class, ['getID' => 101, 'getUserName' => false, 'isPublic' => false]);
        $this->project_creation_data = \Mockery::spy(ProjectCreationData::class);

        $this->params = [
            'template'              => $this->template,
            'project_creation_data' => $this->project_creation_data,
        ];

        $this->tracker_core_service   = \Mockery::mock(Service::class, ['getId' => 101]);
        $this->tracker_plugin_service = \Mockery::mock(Service::class, ['getId' => 106]);

        $this->tracker_core_service->shouldReceive('getShortName')->andReturns('tracker');
        $this->tracker_plugin_service->shouldReceive('getShortName')->andReturns('plugin_tracker');
    }

    public function testItActivatesPluginInsteadOfLegacyService(): void
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns([$this->tracker_core_service, $this->tracker_plugin_service]);

        $this->tracker_v3->shouldReceive('available')->andReturns(true);
        $this->tracker_core_service->shouldReceive('isUsed')->andReturn(true);
        $this->tracker_plugin_service->shouldReceive('isUsed')->andReturn(false);
        $this->project_creation_data->shouldReceive('projectShouldInheritFromTemplate')->andReturns(true);

        $this->project_creation_data->shouldReceive('unsetProjectServiceUsage')->with(101)->once();
        $this->project_creation_data->shouldReceive('forceServiceUsage')->with(106)->once();

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItActivatesOnlyPluginWhenBothServicesAreActivatedIntoTemplate(): void
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns([$this->tracker_core_service, $this->tracker_plugin_service]);

        $this->tracker_v3->shouldReceive('available')->andReturns(true);
        $this->tracker_core_service->shouldReceive('isUsed')->andReturn(true);
        $this->tracker_plugin_service->shouldReceive('isUsed')->andReturn(true);
        $this->project_creation_data->shouldReceive('projectShouldInheritFromTemplate')->andReturns(true);

        $this->project_creation_data->shouldReceive('unsetProjectServiceUsage')->with(101)->once();
        $this->project_creation_data->shouldReceive('forceServiceUsage')->with(106)->once();

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItDoesNotActivatePluginIfBothSVNServicesAreNotActivatedIntoTemplate(): void
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns([$this->tracker_core_service, $this->tracker_plugin_service]);

        $this->tracker_v3->shouldReceive('available')->andReturns(true);
        $this->tracker_core_service->shouldReceive('isUsed')->andReturn(false);
        $this->tracker_plugin_service->shouldReceive('isUsed')->andReturn(false);
        $this->project_creation_data->shouldReceive('projectShouldInheritFromTemplate')->andReturns(true);

        $this->project_creation_data->shouldReceive('unsetProjectServiceUsage')->with(101)->once();
        $this->project_creation_data->shouldReceive('forceServiceUsage')->never();

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItDoesNothingIfServicesAreNotInheritedFromTemplate(): void
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns([$this->tracker_core_service, $this->tracker_plugin_service]);

        $this->tracker_v3->shouldReceive('available')->andReturns(true);
        $this->tracker_core_service->shouldReceive('isUsed')->andReturn(false);
        $this->tracker_plugin_service->shouldReceive('isUsed')->andReturn(false);
        $this->project_creation_data->shouldReceive('projectShouldInheritFromTemplate')->andReturns(false);

        $this->project_creation_data->shouldReceive('unsetProjectServiceUsage')->never();
        $this->project_creation_data->shouldReceive('forceServiceUsage')->never();

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItDoesNothingIfTrackerV3AreNotAvailable(): void
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns([$this->tracker_core_service, $this->tracker_plugin_service]);

        $this->tracker_v3->shouldReceive('available')->andReturns(false);

        $this->project_creation_data->shouldReceive('unsetProjectServiceUsage')->never();
        $this->project_creation_data->shouldReceive('forceServiceUsage')->never();

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItCreatesThePluginServiceIfNotAvailableInTemplate(): void
    {
        $project = \Mockery::spy(\Project::class, ['getID' => 106, 'getUserName' => false, 'isPublic' => false]);
        $legacy  = [Service::TRACKERV3 => false];

        $this->tracker_core_service->shouldReceive('isUsed')->andReturn(false);
        $this->tracker_core_service->shouldReceive('isActive')->andReturn(false);
        $this->tracker_plugin_service->shouldReceive('isUsed')->andReturn(false);
        $this->tracker_plugin_service->shouldReceive('isActive')->andReturn(false);

        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns([$this->tracker_core_service, $this->tracker_plugin_service]);
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($project)->andReturns([]);

        $this->service_creator->shouldReceive('createService')->once();

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function testItDoesNotCreateServiceIfPreviouslyCreated(): void
    {
        $project = \Mockery::spy(\Project::class, ['getID' => 106, 'getUserName' => false, 'isPublic' => false]);
        $legacy  = [Service::TRACKERV3 => false];

        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns([$this->tracker_core_service, $this->tracker_plugin_service]);
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($project)->andReturns([$this->tracker_plugin_service]);

        $this->service_creator->shouldReceive('createService')->never();

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function testItDoesNotCreateServiceIfLegacyMustBeUsed(): void
    {
        $project = \Mockery::spy(\Project::class, ['getID' => 106, 'getUserName' => false, 'isPublic' => false]);
        $legacy  = [Service::TRACKERV3 => true];

        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns([$this->tracker_core_service, $this->tracker_plugin_service]);
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($project)->andReturns([]);

        $this->service_creator->shouldReceive('createService')->never();

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function testItUnsetsLegacyServiceEvenIfItsTheOnlyTrackerServiceInTemplate(): void
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns([$this->tracker_core_service]);

        $this->tracker_v3->shouldReceive('available')->andReturns(true);
        $this->project_creation_data->shouldReceive('projectShouldInheritFromTemplate')->andReturns(true);

        $this->project_creation_data->shouldReceive('unsetProjectServiceUsage')->with(101)->once();
        $this->project_creation_data->shouldReceive('forceServiceUsage')->never();

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItDoesNothingIfTrackerPluginIsTheOnlyTrackerServiceInTemplate(): void
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns([$this->tracker_plugin_service]);

        $this->tracker_v3->shouldReceive('available')->andReturns(true);
        $this->project_creation_data->shouldReceive('projectShouldInheritFromTemplate')->andReturns(true);

        $this->project_creation_data->shouldReceive('unsetProjectServiceUsage')->never();
        $this->project_creation_data->shouldReceive('forceServiceUsage')->never();

        $this->activator->unuseLegacyService($this->params);
    }
}
