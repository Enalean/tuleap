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

use Service;
use Tuleap\Project\ProjectCreationData;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class ServiceActivatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private array $params;
    /**
     * @var \Project&\PHPUnit\Framework\MockObject\MockObject
     */
    private $template;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tuleap\Service\ServiceCreator
     */
    private $service_creator;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\ServiceManager
     */
    private $service_manager;
    private ServiceActivator $activator;
    /**
     * @var Service&\PHPUnit\Framework\MockObject\MockObject
     */
    private $svn_core_service;
    /**
     * @var Service&\PHPUnit\Framework\MockObject\MockObject
     */
    private $svn_plugin_service;
    /**
     * @var ProjectCreationData&\PHPUnit\Framework\MockObject\MockObject
     */
    private $data;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service_manager = $this->createMock(\ServiceManager::class);
        $this->service_creator = $this->createMock(\Tuleap\Service\ServiceCreator::class);
        $this->activator       = new ServiceActivator($this->service_manager, $this->service_creator);

        $this->template = $this->createMock(\Project::class);
        $this->template->method('getId')->willReturn(101);
        $this->data = $this->createMock(ProjectCreationData::class);

        $this->params = [
            'template'              => $this->template,
            'project_creation_data' => $this->data,
        ];

        $this->svn_core_service   = $this->createMock(Service::class);
        $this->svn_plugin_service = $this->createMock(Service::class);

        $this->svn_core_service->method('getId')->willReturn(101);
        $this->svn_plugin_service->method('getId')->willReturn(106);

        $this->svn_core_service->method('getShortName')->willReturn('svn');
        $this->svn_plugin_service->method('getShortName')->willReturn('plugin_svn');
    }

    public function testItActivatesPluginInsteadOfLegacyService(): void
    {
        $this->service_manager->method('getListOfAllowedServicesForProject')->with($this->template)->willReturn(
            [$this->svn_core_service, $this->svn_plugin_service]
        );

        $this->svn_core_service->method('isUsed')->willReturn(true);
        $this->svn_plugin_service->method('isUsed')->willReturn(false);
        $this->data->method('projectShouldInheritFromTemplate')->willReturn(true);

        $this->data->expects(self::once())->method('unsetProjectServiceUsage')->with(101);
        $this->data->expects(self::once())->method('forceServiceUsage')->with(106);

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItActivatesOnlyPluginWhenBothServicesAreActivatedIntoTemplate(): void
    {
        $this->service_manager->method('getListOfAllowedServicesForProject')->with($this->template)->willReturn(
            [$this->svn_core_service, $this->svn_plugin_service]
        );

        $this->svn_core_service->method('isUsed')->willReturn(true);
        $this->svn_plugin_service->method('isUsed')->willReturn(true);
        $this->data->method('projectShouldInheritFromTemplate')->willReturn(true);

        $this->data->expects(self::once())->method('unsetProjectServiceUsage')->with(101);
        $this->data->expects(self::once())->method('forceServiceUsage')->with(106);

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItUnsetsSVNCoreEvenIfItsTheOnlySVNServiceInTemplate(): void
    {
        $this->service_manager->method('getListOfAllowedServicesForProject')->with($this->template)->willReturn(
            [$this->svn_core_service]
        );

        $this->data->method('projectShouldInheritFromTemplate')->willReturn(true);

        $this->data->expects(self::once())->method('unsetProjectServiceUsage')->with(101);
        $this->data->expects(self::never())->method('forceServiceUsage');

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItDoesNothingIfSVNPluginIsTheOnlySVNServiceInTemplate(): void
    {
        $this->service_manager->method('getListOfAllowedServicesForProject')->with($this->template)->willReturn(
            [$this->svn_plugin_service]
        );

        $this->data->method('projectShouldInheritFromTemplate')->willReturn(true);

        $this->data->expects(self::never())->method('unsetProjectServiceUsage');
        $this->data->expects(self::never())->method('forceServiceUsage');

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItDoesNotActivatePluginIfBothSVNServicesAreNotActivatedIntoTemplate(): void
    {
        $this->service_manager->method('getListOfAllowedServicesForProject')->with($this->template)->willReturn(
            [$this->svn_core_service, $this->svn_plugin_service]
        );

        $this->svn_core_service->method('isUsed')->willReturn(false);
        $this->svn_plugin_service->method('isUsed')->willReturn(false);
        $this->data->method('projectShouldInheritFromTemplate')->willReturn(true);

        $this->data->expects(self::once())->method('unsetProjectServiceUsage')->with(101);
        $this->data->expects(self::never())->method('forceServiceUsage');

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItDoesNothingIfServicesAreNotInheritedFromTemplate(): void
    {
        $this->service_manager->method('getListOfAllowedServicesForProject')->with($this->template)->willReturn(
            [$this->svn_core_service, $this->svn_plugin_service]
        );

        $this->svn_core_service->method('isUsed')->willReturn(false);
        $this->svn_plugin_service->method('isUsed')->willReturn(false);
        $this->data->method('projectShouldInheritFromTemplate')->willReturn(false);

        $this->data->expects(self::never())->method('unsetProjectServiceUsage');
        $this->data->expects(self::never())->method('forceServiceUsage');

        $this->activator->unuseLegacyService($this->params);
    }

    public function testItCreatesThePluginServiceIfPluginServiceDoesNotExistInTemplate(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(102)->build();
        $legacy  = [Service::SVN => false];

        $this->template->method('isSystem')->willReturn(false);
        $this->template->method('getUnixName')->willReturn('unix-name');

        $this->svn_core_service->method('isUsed')->willReturn(false);
        $this->svn_core_service->method('isActive')->willReturn(false);

        $this->service_manager->method('getListOfAllowedServicesForProject')->willReturnMap([
            [$this->template, [$this->svn_core_service]],
            [$project, []],
        ]);

        $this->service_creator->expects(self::once())->method('createService');

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function testItCreatesThePluginServiceIfNotAvailableInTemplate(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(102)->build();
        $legacy  = [Service::SVN => false];

        $this->template->method('isSystem')->willReturn(false);
        $this->template->method('getUnixName')->willReturn('unix-name');

        $this->svn_core_service->method('isUsed')->willReturn(false);
        $this->svn_core_service->method('isActive')->willReturn(false);
        $this->svn_plugin_service->method('isUsed')->willReturn(false);
        $this->svn_plugin_service->method('isActive')->willReturn(false);

        $this->service_manager->method('getListOfAllowedServicesForProject')->willReturnMap([
            [$this->template, [$this->svn_core_service, $this->svn_plugin_service]],
            [$project, []],
        ]);

        $this->service_creator->expects(self::once())->method('createService');

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function testItDoesNotCreateServiceIfPreviouslyCreated(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(102)->build();
        $legacy  = [Service::SVN => false];

        $this->service_manager->method('getListOfAllowedServicesForProject')->willReturnMap([
            [$this->template, [$this->svn_core_service, $this->svn_plugin_service]],
            [$project, [$this->svn_plugin_service]],
        ]);

        $this->service_creator->expects(self::never())->method('createService');

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function testItDoesNotCreateServiceIfLegacyMustBeUsed(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(102)->build();
        $legacy  = [Service::SVN => true];

        $this->service_manager->method('getListOfAllowedServicesForProject')->willReturnMap([
            [$this->template, [$this->svn_core_service, $this->svn_plugin_service]],
            [$project, [$this->svn_plugin_service]],
        ]);

        $this->service_creator->expects(self::never())->method('createService');

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }

    public function testItDoesNotCreateThePluginServiceIfNoSVNAvailableInTemplate(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(102)->build();
        $legacy  = [Service::SVN => false];

        $this->service_manager->method('getListOfAllowedServicesForProject')->willReturnMap([
            [$this->template, []],
            [$project, []],
        ]);

        $this->service_creator->expects(self::never())->method('createService');

        $this->activator->forceUsageOfService($project, $this->template, $legacy);
    }
}
