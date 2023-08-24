<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Service;

use PHPUnit\Framework\MockObject\Stub;
use Project;
use ServiceManager;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ServiceBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Test\Stubs\EventDispatcherStub;

final class ServicesPresenterBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private ServiceManager & Stub $service_manager;
    private Project $project;
    private EventDispatcherStub $event_manager;

    protected function setUp(): void
    {
        $this->project = $this->createStub(Project::class);
        $this->project->method('getMinimalRank');
        $this->project->method('getID');

        $this->service_manager = $this->createStub(ServiceManager::class);
        $this->event_manager   = EventDispatcherStub::withIdentityCallback();
    }

    private function buildPresenter(): ServicesPresenter
    {
        $builder = new ServicesPresenterBuilder($this->service_manager, $this->event_manager);

        $csrf_token = CSRFSynchronizerTokenStub::buildSelf();
        $user       = UserTestBuilder::aUser()->withoutSiteAdministrator()->build();

        return $builder->build($this->project, $csrf_token, $user);
    }

    public function testItBuildServiceWithoutAdminAndSummaryServices(): void
    {
        $admin_service   = ServiceBuilder::buildLegacyAdminService($this->project);
        $summary_service = ServiceBuilder::buildLegacySummaryService($this->project);
        $tracker_service = ServiceBuilder::aProjectDefinedService($this->project)
                                         ->withLabel('Tracker')
                                         ->build();

        $this->service_manager->expects(self::once())->method('getListOfAllowedServicesForProject')->willReturn([$admin_service, $summary_service, $tracker_service]);

        $service_presenter = $this->buildPresenter();
        self::assertCount(1, $service_presenter->services);
        self::assertSame('Tracker', $service_presenter->services[0]->label);
    }

    public function testItCanBeDisabledByPlugins(): void
    {
        $admin_service   = ServiceBuilder::buildLegacyAdminService($this->project);
        $summary_service = ServiceBuilder::buildLegacySummaryService($this->project);
        $tracker_service = ServiceBuilder::aProjectDefinedService($this->project)
                                         ->withLabel('Tracker')
                                         ->build();

        $this->service_manager->expects(self::once())->method('getListOfAllowedServicesForProject')->willReturn([$admin_service, $summary_service, $tracker_service]);

        $this->event_manager = EventDispatcherStub::withCallback(function (AddMissingService | HideServiceInUserInterfaceEvent | ServiceDisabledCollector $event) {
            if ($event instanceof ServiceDisabledCollector) {
                $event->setIsDisabled('Disabled by plugin');
            }
            return $event;
        });

        $service_presenter = $this->buildPresenter();
        self::assertCount(1, $service_presenter->services);
        self::assertStringContainsString('"is_disabled_reason":"Disabled by plugin"', $service_presenter->services[0]->service_json);
    }

    public function testPluginCanInjectMissingServices(): void
    {
        $tracker_service = ServiceBuilder::aProjectDefinedService($this->project)
                                         ->withLabel('Tracker')
                                         ->build();

        $this->service_manager->expects(self::once())->method('getListOfAllowedServicesForProject')->willReturn([$tracker_service]);

        $extra_service = ServiceBuilder::aProjectDefinedService($this->project)
                        ->withShortName('plugin_mediawiki_standalone')
                        ->build();

        $this->event_manager = EventDispatcherStub::withCallback(function (AddMissingService | HideServiceInUserInterfaceEvent | ServiceDisabledCollector $event) use ($extra_service) {
            if ($event instanceof AddMissingService) {
                $event->addService($extra_service);
            }
            return $event;
        });

        $service_presenter = $this->buildPresenter();
        self::assertCount(2, $service_presenter->services);
        self::assertSame('plugin_mediawiki_standalone', $service_presenter->services[1]->short_name);
    }

    public function testItBuildServiceWithoutHiddenService(): void
    {
        $tracker_service = ServiceBuilder::aProjectDefinedService($this->project)
                                         ->withLabel('Tracker')
                                         ->build();

        $hidden_service_short_name = 'kanban';
        $kanban_hidden_service     = ServiceBuilder::aProjectDefinedService($this->project)
                                         ->withShortName($hidden_service_short_name)
                                         ->build();

        $this->service_manager->expects(self::once())->method('getListOfAllowedServicesForProject')->willReturn([$tracker_service, $kanban_hidden_service]);

        $this->event_manager = EventDispatcherStub::withCallback(
            function (AddMissingService | HideServiceInUserInterfaceEvent | ServiceDisabledCollector $event) use ($hidden_service_short_name) {
                if ($event instanceof HideServiceInUserInterfaceEvent) {
                    if ($event->service->getShortName() === $hidden_service_short_name) {
                        $event->hideService();
                    }
                }
                return $event;
            }
        );

        $service_presenter = $this->buildPresenter();
        self::assertCount(1, $service_presenter->services);
        self::assertSame('Tracker', $service_presenter->services[0]->label);
    }
}
