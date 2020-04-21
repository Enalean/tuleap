<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\SVN\Service;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use ProjectCreationData;
use Service;

class ServiceActivatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var array
     */
    private $params;
    /**
     * @var \a|\Mockery\LegacyMockInterface|MockInterface
     */
    private $template;
    /**
     * @var \Mockery\LegacyMockInterface|MockInterface|\Tuleap\Service\ServiceCreator
     */
    private $service_creator;
    /**
     * @var \Mockery\LegacyMockInterface|MockInterface|\ServiceManager
     */
    private $service_manager;
    /**
     * @var ServiceActivator
     */
    private $activator;
    /**
     * @var MockInterface
     */
    private $svn_core_service;
    /**
     * @var MockInterface
     */
    private $svn_plugin_service;
    /**
     * @var ProjectCreationData
     */
    private $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service_manager = \Mockery::spy(\ServiceManager::class);
        $this->service_creator = \Mockery::spy(\Tuleap\Service\ServiceCreator::class);
        $this->activator       = new ServiceActivator($this->service_manager, $this->service_creator);

        $this->template = \Mockery::mock(\Project::class);
        $this->template->shouldReceive('getId')->andReturn(101);
        $this->data     = \Mockery::spy(\ProjectCreationData::class);

        $this->params = [
            'template'              => $this->template,
            'project_creation_data' => $this->data
        ];

        $this->svn_core_service   = \Mockery::mock(Service::class, ['getId' => 101]);
        $this->svn_plugin_service = \Mockery::mock(Service::class, ['getId' => 106]);

        $this->svn_core_service->shouldReceive('getShortName')->andReturn('svn');
        $this->svn_plugin_service->shouldReceive('getShortName')->andReturn('plugin_svn');
    }

    public function testItActivatesPluginInsteadOfLegacyService(): void
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->withArgs([$this->template])->andReturn(
            array($this->svn_core_service, $this->svn_plugin_service)
        );

        $this->svn_core_service->shouldReceive('isUsed')->andReturn(true);
        $this->svn_plugin_service->shouldReceive('isUsed')->andReturn(false);
        $this->data->shouldReceive('projectShouldInheritFromTemplate')->andReturn(true);

        $this->data->shouldReceive('unsetProjectServiceUsage')->withArgs([101])->once();
        $this->data->shouldReceive('forceServiceUsage')->withArgs([106])->once();

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItActivatesOnlyPluginWhenBothServicesAreActivatedIntoTemplate(): void
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->withArgs([$this->template])->andReturn(
            array($this->svn_core_service, $this->svn_plugin_service)
        );

        $this->svn_core_service->shouldReceive('isUsed')->andReturn(true);
        $this->svn_plugin_service->shouldReceive('isUsed')->andReturn(true);
        $this->data->shouldReceive('projectShouldInheritFromTemplate')->andReturn(true);

        $this->data->shouldReceive('unsetProjectServiceUsage')->withArgs([101])->once();
        $this->data->shouldReceive('forceServiceUsage')->withArgs([106])->once();

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItUnsetsSVNCoreEvenIfItsTheOnlySVNServiceInTemplate(): void
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->withArgs([$this->template])->andReturn(
            array($this->svn_core_service)
        );

        $this->data->shouldReceive('projectShouldInheritFromTemplate')->andReturn(true);

        $this->data->shouldReceive('unsetProjectServiceUsage')->withArgs([101])->once();
        $this->data->shouldReceive('forceServiceUsage')->never();

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItDoesNothingIfSVNPluginIsTheOnlySVNServiceInTemplate(): void
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->withArgs([$this->template])->andReturn(
            array($this->svn_plugin_service)
        );

        $this->data->shouldReceive('projectShouldInheritFromTemplate')->andReturn(true);

        $this->data->shouldReceive('unsetProjectServiceUsage')->never();
        $this->data->shouldReceive('forceServiceUsage')->never();

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItDoesNotActivatePluginIfBothSVNServicesAreNotActivatedIntoTemplate(): void
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->withArgs([$this->template])->andReturn(
            array($this->svn_core_service, $this->svn_plugin_service)
        );

        $this->svn_core_service->shouldReceive('isUsed')->andReturn(false);
        $this->svn_plugin_service->shouldReceive('isUsed')->andReturn(false);
        $this->data->shouldReceive('projectShouldInheritFromTemplate')->andReturn(true);

        $this->data->shouldReceive('unsetProjectServiceUsage')->withArgs([101])->once();
        $this->data->shouldReceive('forceServiceUsage')->never();

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItDoesNothingIfServicesAreNotInheritedFromTemplate(): void
    {
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->withArgs([$this->template])->andReturn(
            array($this->svn_core_service, $this->svn_plugin_service)
        );

        $this->svn_core_service->shouldReceive('isUsed')->andReturn(false);
        $this->svn_plugin_service->shouldReceive('isUsed')->andReturn(false);
        $this->data->shouldReceive('projectShouldInheritFromTemplate')->andReturn(false);

        $this->data->shouldReceive('unsetProjectServiceUsage')->never();
        $this->data->shouldReceive('forceServiceUsage')->never();

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItCreatesThePluginServiceIfPluginServiceDoesNotExistInTemplate(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(102);
        $legacy  = array(Service::SVN => false);

        $this->template->shouldReceive('isSystem')->andReturnFalse();
        $this->template->shouldReceive('getUnixName')->andReturn('unix-name');

        $this->svn_core_service->shouldReceive('isUsed')->andReturn(false);
        $this->svn_core_service->shouldReceive('isActive')->andReturn(false);

        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->withArgs([$this->template])->andReturn(
            array($this->svn_core_service)
        );
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->withArgs([$project])->andReturn(
            array()
        );

        $this->service_creator->shouldReceive('createService')->once();

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function testItCreatesThePluginServiceIfNotAvailableInTemplate(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(102);
        $legacy  = array(Service::SVN => false);

        $this->template->shouldReceive('isSystem')->andReturnFalse();
        $this->template->shouldReceive('getUnixName')->andReturn('unix-name');

        $this->svn_core_service->shouldReceive('isUsed')->andReturn(false);
        $this->svn_core_service->shouldReceive('isActive')->andReturn(false);
        $this->svn_plugin_service->shouldReceive('isUsed')->andReturn(false);
        $this->svn_plugin_service->shouldReceive('isActive')->andReturn(false);

        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->withArgs([$this->template])->andReturn(
            array($this->svn_core_service, $this->svn_plugin_service)
        );
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->withArgs([$project])->andReturn(
            array()
        );

        $this->service_creator->shouldReceive('createService')->once();

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function testItDoesNotCreateServiceIfPreviouslyCreated(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(102);
        $legacy  = array(Service::SVN => false);

        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->withArgs([$this->template])->andReturn(
            array($this->svn_core_service, $this->svn_plugin_service)
        );
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->withArgs([$project])->andReturn(
            array($this->svn_plugin_service)
        );

        $this->service_creator->shouldReceive('createService')->never();

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function testItDoesNotCreateServiceIfLegacyMustBeUsed(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(102);
        $legacy  = array(Service::SVN => true);

        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->withArgs([$this->template])->andReturn(
            array($this->svn_core_service, $this->svn_plugin_service)
        );
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->withArgs([$project])->andReturn(
            array($this->svn_plugin_service)
        );

        $this->service_creator->shouldReceive('createService')->never();

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function testItDoesNotCreateThePluginServiceIfNoSVNAvailableInTemplate(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(102);
        $legacy  = array(Service::SVN => false);

        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->withArgs([$this->template])->andReturn(
            array()
        );
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->withArgs([$project])->andReturn(
            array()
        );

        $this->service_creator->shouldReceive('createService')->never();

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }
}
