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

declare(strict_types=1);

namespace Tuleap\Layout;

use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\Service\CollectServicesAllowedForRestrictedEvent;
use Tuleap\Project\Service\UserCanAccessToServiceEvent;
use Tuleap\Sanitizer\URISanitizer;
use Tuleap\Test\Builders\ServiceBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\EventDispatcherStub;

final class ProjectSidebarToolsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    private \Project & \PHPUnit\Framework\MockObject\Stub $project;
    private EventDispatcherStub $event_dispatcher;
    private \PFUser $user;

    protected function setUp(): void
    {
        \ForgeConfig::set('sys_default_domain', 'example.com');

        $this->project = $this->createStub(\Project::class);
        $this->project->method('getID')->willReturn(101);

        $this->user             = UserTestBuilder::buildWithDefaults();
        $this->event_dispatcher = EventDispatcherStub::withIdentityCallback();
    }

    /**
     * @return SidebarServicePresenter[]
     */
    private function buildServices(): array
    {
        $uri_sanitizer = $this->createStub(URISanitizer::class);
        $uri_sanitizer->method('sanitizeForHTMLAttribute')->willReturn('/tracker');
        $project_manager = $this->createStub(\ProjectManager::class);

        $builder = new ProjectSidebarToolsBuilder(
            $this->event_dispatcher,
            $project_manager,
            $uri_sanitizer
        );

        $sidebar = $builder->getSidebarTools($this->user, 10, $this->project);
        return iterator_to_array($sidebar);
    }

    public function testItReturnServicesWithoutSummaryAndAdmin(): void
    {
        $tracker_label = 'Tracker';
        $this->project->method('getServices')->willReturn([
            ServiceBuilder::buildLegacyAdminService($this->project),
            ServiceBuilder::buildLegacySummaryService($this->project),
            ServiceBuilder::aProjectDefinedService($this->project)->withLabel($tracker_label)->build(),
        ]);

        $services = $this->buildServices();

        self::assertCount(1, $services);
        self::assertSame($tracker_label, $services[0]->label);
        self::assertSame('https://example.com/tracker', $services[0]->href);
    }

    public function testWhenUserIsRestrictedAndNotProjectMemberThenItKeepsOnlyAllowedServices(): void
    {
        $this->user = UserTestBuilder::aRestrictedUser()->withoutMemberOfProjects()->build();

        $allowed_service_shortname = 'git';
        $allowed_service_label     = 'Git';

        $this->project->method('getServices')->willReturn([
            ServiceBuilder::aSystemService($this->project)
                ->withShortName($allowed_service_shortname)
                ->withLabel($allowed_service_label)
                ->build(),
            ServiceBuilder::aSystemService($this->project)->build(),
        ]);

        $this->event_dispatcher = EventDispatcherStub::withCallback(
            function (UserCanAccessToServiceEvent|CollectServicesAllowedForRestrictedEvent $event) use (
                $allowed_service_shortname
            ) {
                if ($event instanceof CollectServicesAllowedForRestrictedEvent) {
                    $event->addServiceShortname($allowed_service_shortname);
                }
                return $event;
            }
        );

        $services = $this->buildServices();
        self::assertCount(1, $services);
        self::assertSame($allowed_service_label, $services[0]->label);
    }

    public function testItRemovesServiceUserCannotAccess(): void
    {
        $this->project->method('getServices')->willReturn([
            ServiceBuilder::aProjectDefinedService($this->project)->build(),
        ]);

        $this->event_dispatcher = EventDispatcherStub::withCallback(
            function (UserCanAccessToServiceEvent $event) {
                $event->forbidAccessToService();
                return $event;
            }
        );

        self::assertCount(0, $this->buildServices());
    }
}
