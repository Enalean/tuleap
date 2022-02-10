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

use EventManager;
use Service;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\Service\ProjectDefinedService;
use Tuleap\Sanitizer\URISanitizer;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProjectSidebarToolsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use ForgeConfigSandbox;

    private ProjectSidebarToolsBuilder $builder;
    private \PFUser $user;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|\Project
     */
    private $project;

    protected function setUp(): void
    {
        \ForgeConfig::set('sys_default_domain', 'example.com');

        $this->project = $this->createStub(\Project::class);
        $this->project->method('getID')->willReturn(101);

        $this->user = UserTestBuilder::aUser()->build();

        $event_manager = $this->createStub(EventManager::class);

        $project_manager = $this->createStub(\ProjectManager::class);
        $uri_sanitizer   = $this->createStub(URISanitizer::class);
        $uri_sanitizer->method('sanitizeForHTMLAttribute')->willReturn('/tracker');

        $this->builder = new ProjectSidebarToolsBuilder(
            $event_manager,
            $project_manager,
            $uri_sanitizer
        );
    }

    public function testItReturnServicesWithoutSummaryAndAdmin(): void
    {
        $admin_service   = new Service(
            $this->project,
            [
                'short_name' => 'admin',
                'service_id' => 10,
                'is_active' => true,
                'is_used' => true,
            ]
        );
        $summary_service = new Service(
            $this->project,
            [
                'short_name' => 'summary',
                'service_id' => 20,
                'is_active' => true,
                'is_used' => true,
            ]
        );
        $tracker_service = new ProjectDefinedService(
            $this->project,
            [
                'short_name' => 'tracker',
                'service_id' => 102,
                'is_active' => true,
                'label' => 'Tracker',
                'description' => 'description',
                'is_used' => true,
                'is_in_iframe' => true,
                'rank' => 200,
                'scope' => 'project',
                'group_id' => 101,
                'icon' => 'fa-list',
                'is_in_new_tab' => false,
            ]
        );

        $this->project->method('getServices')->willReturn([$admin_service, $summary_service, $tracker_service]);


        $sidebar = $this->builder->getSidebarTools(
            $this->user,
            10,
            $this->project
        );

        $services = iterator_to_array($sidebar);

        self::assertCount(1, $services);
        self::assertSame('Tracker', $services[0]->label);
        self::assertSame('https://example.com/tracker', $services[0]->href);
    }
}
