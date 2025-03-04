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

use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Service;
use ServiceManager;
use TrackerV3;
use Tuleap\Project\ProjectCreationData;
use Tuleap\Service\ServiceCreator;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ServiceActivatorTest extends TestCase
{
    private ServiceActivator $activator;
    private Project $template;
    private Service&MockObject $tracker_core_service;
    private Service&MockObject $tracker_plugin_service;

    private ProjectCreationData&MockObject $project_creation_data;
    private TrackerV3&MockObject $tracker_v3;
    private ServiceManager&MockObject $service_manager;
    private ServiceCreator&MockObject $service_creator;
    private array $params;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tracker_v3      = $this->createMock(TrackerV3::class);
        $this->service_manager = $this->createMock(ServiceManager::class);
        $this->service_creator = $this->createMock(ServiceCreator::class);
        $this->activator       = new ServiceActivator($this->service_manager, $this->tracker_v3, $this->service_creator);

        $this->template              = ProjectTestBuilder::aProject()->withId(101)->withAccessPrivate()->build();
        $this->project_creation_data = $this->createMock(ProjectCreationData::class);

        $this->params = [
            'template'              => $this->template,
            'project_creation_data' => $this->project_creation_data,
        ];

        $this->tracker_core_service   = $this->createMock(Service::class);
        $this->tracker_plugin_service = $this->createMock(Service::class);

        $this->tracker_core_service->method('getId')->willReturn(101);
        $this->tracker_plugin_service->method('getId')->willReturn(106);

        $this->tracker_core_service->method('getShortName')->willReturn('tracker');
        $this->tracker_plugin_service->method('getShortName')->willReturn('plugin_tracker');
    }

    public function testItActivatesPluginInsteadOfLegacyService(): void
    {
        $this->service_manager->method('getListOfAllowedServicesForProject')->with($this->template)->willReturn([$this->tracker_core_service, $this->tracker_plugin_service]);

        $this->tracker_v3->method('available')->willReturn(true);
        $this->tracker_core_service->method('isUsed')->willReturn(true);
        $this->tracker_plugin_service->method('isUsed')->willReturn(false);
        $this->project_creation_data->method('projectShouldInheritFromTemplate')->willReturn(true);

        $this->project_creation_data->expects(self::once())->method('unsetProjectServiceUsage')->with(101);
        $this->project_creation_data->expects(self::once())->method('forceServiceUsage')->with(106);

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItActivatesOnlyPluginWhenBothServicesAreActivatedIntoTemplate(): void
    {
        $this->service_manager->method('getListOfAllowedServicesForProject')->with($this->template)->willReturn([$this->tracker_core_service, $this->tracker_plugin_service]);

        $this->tracker_v3->method('available')->willReturn(true);
        $this->tracker_core_service->method('isUsed')->willReturn(true);
        $this->tracker_plugin_service->method('isUsed')->willReturn(true);
        $this->project_creation_data->method('projectShouldInheritFromTemplate')->willReturn(true);

        $this->project_creation_data->expects(self::once())->method('unsetProjectServiceUsage')->with(101);
        $this->project_creation_data->expects(self::once())->method('forceServiceUsage')->with(106);

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItDoesNotActivatePluginIfBothSVNServicesAreNotActivatedIntoTemplate(): void
    {
        $this->service_manager->method('getListOfAllowedServicesForProject')->with($this->template)->willReturn([$this->tracker_core_service, $this->tracker_plugin_service]);

        $this->tracker_v3->method('available')->willReturn(true);
        $this->tracker_core_service->method('isUsed')->willReturn(false);
        $this->tracker_plugin_service->method('isUsed')->willReturn(false);
        $this->project_creation_data->method('projectShouldInheritFromTemplate')->willReturn(true);

        $this->project_creation_data->expects(self::once())->method('unsetProjectServiceUsage')->with(101);
        $this->project_creation_data->expects(self::never())->method('forceServiceUsage');

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItDoesNothingIfServicesAreNotInheritedFromTemplate(): void
    {
        $this->service_manager->method('getListOfAllowedServicesForProject')->with($this->template)->willReturn([$this->tracker_core_service, $this->tracker_plugin_service]);

        $this->tracker_v3->method('available')->willReturn(true);
        $this->tracker_core_service->method('isUsed')->willReturn(false);
        $this->tracker_plugin_service->method('isUsed')->willReturn(false);
        $this->project_creation_data->method('projectShouldInheritFromTemplate')->willReturn(false);

        $this->project_creation_data->expects(self::never())->method('unsetProjectServiceUsage');
        $this->project_creation_data->expects(self::never())->method('forceServiceUsage');

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItDoesNothingIfTrackerV3AreNotAvailable(): void
    {
        $this->service_manager->method('getListOfAllowedServicesForProject')->with($this->template)->willReturn([$this->tracker_core_service, $this->tracker_plugin_service]);

        $this->tracker_v3->method('available')->willReturn(false);

        $this->project_creation_data->expects(self::never())->method('unsetProjectServiceUsage');
        $this->project_creation_data->expects(self::never())->method('forceServiceUsage');
        $this->project_creation_data->method('projectShouldInheritFromTemplate')->willReturn(false);

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItCreatesThePluginServiceIfNotAvailableInTemplate(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(106)->withAccessPrivate()->build();
        $legacy  = [Service::TRACKERV3 => false];

        $this->tracker_core_service->method('isUsed')->willReturn(false);
        $this->tracker_core_service->method('isActive')->willReturn(false);
        $this->tracker_plugin_service->method('isUsed')->willReturn(false);
        $this->tracker_plugin_service->method('isActive')->willReturn(false);

        $this->service_manager
            ->method('getListOfAllowedServicesForProject')
            ->willReturnCallback(fn (Project $project) => match ($project) {
                $this->template => [$this->tracker_core_service, $this->tracker_plugin_service],
                $project        => [],
            });

        $this->service_creator->expects(self::once())->method('createService');

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function testItDoesNotCreateServiceIfPreviouslyCreated(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(106)->withAccessPrivate()->build();
        $legacy  = [Service::TRACKERV3 => false];

        $this->service_manager
            ->method('getListOfAllowedServicesForProject')
            ->willReturnCallback(fn (Project $project) => match ($project) {
                $this->template => [$this->tracker_core_service, $this->tracker_plugin_service],
                $project        => [$this->tracker_plugin_service],
            });

        $this->service_creator->expects(self::never())->method('createService');

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function testItDoesNotCreateServiceIfLegacyMustBeUsed(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(106)->withAccessPrivate()->build();
        $legacy  = [Service::TRACKERV3 => true];

        $this->service_manager
            ->method('getListOfAllowedServicesForProject')
            ->willReturnCallback(fn (Project $project) => match ($project) {
                $this->template => [$this->tracker_core_service, $this->tracker_plugin_service],
                $project        => [],
            });
        $this->service_manager->method('getListOfAllowedServicesForProject')->with($project)->willReturn([]);

        $this->service_creator->expects(self::never())->method('createService');

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function testItUnsetsLegacyServiceEvenIfItsTheOnlyTrackerServiceInTemplate(): void
    {
        $this->service_manager->method('getListOfAllowedServicesForProject')->with($this->template)->willReturn([$this->tracker_core_service]);

        $this->tracker_v3->method('available')->willReturn(true);
        $this->project_creation_data->method('projectShouldInheritFromTemplate')->willReturn(true);

        $this->project_creation_data->expects(self::once())->method('unsetProjectServiceUsage')->with(101);
        $this->project_creation_data->expects(self::never())->method('forceServiceUsage');

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItDoesNothingIfTrackerPluginIsTheOnlyTrackerServiceInTemplate(): void
    {
        $this->service_manager->method('getListOfAllowedServicesForProject')->with($this->template)->willReturn([$this->tracker_plugin_service]);

        $this->tracker_v3->method('available')->willReturn(true);
        $this->project_creation_data->method('projectShouldInheritFromTemplate')->willReturn(true);

        $this->project_creation_data->expects(self::never())->method('unsetProjectServiceUsage');
        $this->project_creation_data->expects(self::never())->method('forceServiceUsage');

        $this->activator->unuseLegacyService($this->params);
    }
}
