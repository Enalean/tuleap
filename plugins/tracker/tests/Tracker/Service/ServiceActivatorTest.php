<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Service;
use trackerPlugin;
use TuleapTestCase;

require_once __DIR__ .'/../../bootstrap.php';

class ServiceActivatorTest extends TuleapTestCase
{
    /**
     * @var ServiceActivator
     */
    private $activator;
    private $tracker_core_service;
    private $tracker_plugin_service;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->tracker_v3      = \Mockery::spy(\TrackerV3::class);
        $this->service_manager = \Mockery::spy(\ServiceManager::class);
        $this->service_creator = \Mockery::spy(\Tuleap\Service\ServiceCreator::class);
        $this->activator       = new ServiceActivator($this->service_manager, $this->tracker_v3, $this->service_creator);

        $this->template = \Mockery::spy(\Project::class, ['getID' => 101, 'getUnixName' => false, 'isPublic' => false]);
        $this->data     = \Mockery::spy(\ProjectCreationData::class);

        $this->params = array(
            'template'              => $this->template,
            'project_creation_data' => $this->data
        );

        $this->tracker_core_service   = \Mockery::mock(Service::class, ['getId' => 101]);
        $this->tracker_plugin_service = \Mockery::mock(Service::class, ['getId' => 106]);

        $this->tracker_core_service->shouldReceive('getShortName')->andReturns('tracker');
        $this->tracker_plugin_service->shouldReceive('getShortName')->andReturns('plugin_tracker');
    }

    public function itActivatesPluginInsteadOfLegacyService()
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns(array($this->tracker_core_service, $this->tracker_plugin_service));

        $this->tracker_v3->shouldReceive('available')->andReturns(true);
        $this->tracker_core_service->shouldReceive('isUsed')->andReturn(true);
        $this->tracker_plugin_service->shouldReceive('isUsed')->andReturn(false);
        $this->data->shouldReceive('projectShouldInheritFromTemplate')->andReturns(true);

        $this->data->shouldReceive('unsetProjectServiceUsage')->with(101)->once();
        $this->data->shouldReceive('forceServiceUsage')->with(106)->once();

        $this->activator->unuseLegacyService($this->params);
    }

    public function itActivatesOnlyPluginWhenBothServicesAreActivatedIntoTemplate()
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns(array($this->tracker_core_service, $this->tracker_plugin_service));

        $this->tracker_v3->shouldReceive('available')->andReturns(true);
        $this->tracker_core_service->shouldReceive('isUsed')->andReturn(true);
        $this->tracker_plugin_service->shouldReceive('isUsed')->andReturn(true);
        $this->data->shouldReceive('projectShouldInheritFromTemplate')->andReturns(true);

        $this->data->shouldReceive('unsetProjectServiceUsage')->with(101)->once();
        $this->data->shouldReceive('forceServiceUsage')->with(106)->once();

        $this->activator->unuseLegacyService($this->params);
    }

    public function itDoesNotActivatePluginIfBothSVNServicesAreNotActivatedIntoTemplate()
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns(array($this->tracker_core_service, $this->tracker_plugin_service));

        $this->tracker_v3->shouldReceive('available')->andReturns(true);
        $this->tracker_core_service->shouldReceive('isUsed')->andReturn(false);
        $this->tracker_plugin_service->shouldReceive('isUsed')->andReturn(false);
        $this->data->shouldReceive('projectShouldInheritFromTemplate')->andReturns(true);

        $this->data->shouldReceive('unsetProjectServiceUsage')->with(101)->once();
        $this->data->shouldReceive('forceServiceUsage')->never();

        $this->activator->unuseLegacyService($this->params);
    }

    public function itDoesNothingIfServicesAreNotInheritedFromTemplate()
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns(array($this->tracker_core_service, $this->tracker_plugin_service));

        $this->tracker_v3->shouldReceive('available')->andReturns(true);
        $this->tracker_core_service->shouldReceive('isUsed')->andReturn(false);
        $this->tracker_plugin_service->shouldReceive('isUsed')->andReturn(false);
        $this->data->shouldReceive('projectShouldInheritFromTemplate')->andReturns(false);

        $this->data->shouldReceive('unsetProjectServiceUsage')->never();
        $this->data->shouldReceive('forceServiceUsage')->never();

        $this->activator->unuseLegacyService($this->params);
    }

    public function itDoesNothingIfTrackerV3AreNotAvailable()
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns(array($this->tracker_core_service, $this->tracker_plugin_service));

        $this->tracker_v3->shouldReceive('available')->andReturns(false);

        $this->data->shouldReceive('unsetProjectServiceUsage')->never();
        $this->data->shouldReceive('forceServiceUsage')->never();

        $this->activator->unuseLegacyService($this->params);
    }

    public function itCreatesThePluginServiceIfNotAvailableInTemplate()
    {
        $project = \Mockery::spy(\Project::class, ['getID' => 106, 'getUnixName' => false, 'isPublic' => false]);
        $legacy  = array(Service::TRACKERV3 => false);

        $this->tracker_core_service->shouldReceive('isUsed')->andReturn(false);
        $this->tracker_core_service->shouldReceive('isActive')->andReturn(false);
        $this->tracker_plugin_service->shouldReceive('isUsed')->andReturn(false);
        $this->tracker_plugin_service->shouldReceive('isActive')->andReturn(false);

        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns(array($this->tracker_core_service, $this->tracker_plugin_service));
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($project)->andReturns(array());

        $this->service_creator->shouldReceive('createService')->once();

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function itDoesNotCreateServiceIfPreviouslyCreated()
    {
        $project = \Mockery::spy(\Project::class, ['getID' => 106, 'getUnixName' => false, 'isPublic' => false]);
        $legacy  = array(Service::TRACKERV3 => false);

        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns(array($this->tracker_core_service, $this->tracker_plugin_service));
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($project)->andReturns(array($this->tracker_plugin_service));

        $this->service_creator->shouldReceive('createService')->never();

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function itDoesNotCreateServiceIfLegacyMustBeUsed()
    {
        $project = \Mockery::spy(\Project::class, ['getID' => 106, 'getUnixName' => false, 'isPublic' => false]);
        $legacy  = array(Service::TRACKERV3 => true);

        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns(array($this->tracker_core_service, $this->tracker_plugin_service));
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($project)->andReturns(array());

        $this->service_creator->shouldReceive('createService')->never();

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function itUnsetsLegacyServiceEvenIfItsTheOnlyTrackerServiceInTemplate()
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns(array($this->tracker_core_service));

        $this->tracker_v3->shouldReceive('available')->andReturns(true);
        $this->data->shouldReceive('projectShouldInheritFromTemplate')->andReturns(true);

        $this->data->shouldReceive('unsetProjectServiceUsage')->with(101)->once();
        $this->data->shouldReceive('forceServiceUsage')->never();

        $this->activator->unuseLegacyService($this->params);
    }

    public function itDoesNothingIfTrackerPluginIsTheOnlyTrackerServiceInTemplate()
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($this->template)->andReturns(array($this->tracker_plugin_service));

        $this->tracker_v3->shouldReceive('available')->andReturns(true);
        $this->data->shouldReceive('projectShouldInheritFromTemplate')->andReturns(true);

        $this->data->shouldReceive('unsetProjectServiceUsage')->never();
        $this->data->shouldReceive('forceServiceUsage')->never();

        $this->activator->unuseLegacyService($this->params);
    }
}
