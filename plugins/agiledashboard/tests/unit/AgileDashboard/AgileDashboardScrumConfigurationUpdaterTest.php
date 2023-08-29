<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\ExplicitBacklog\ConfigurationUpdater;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneDisabler;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneEnabler;
use Tuleap\Test\Builders\ProjectTestBuilder;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class AgileDashboardScrumConfigurationUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testBlockingAccessToScrumBlocksConfigurationChanges(): void
    {
        $event_dispatcher = new class implements \Psr\EventDispatcher\EventDispatcherInterface {
            public function dispatch(object $event)
            {
                if ($event instanceof \Tuleap\AgileDashboard\BlockScrumAccess) {
                    $event->disableScrumAccess();
                }
                return $event;
            }
        };
        $request          = Mockery::mock(Codendi_Request::class);
        $project          = ProjectTestBuilder::aProject()->build();
        $request->shouldReceive('get')->with('group_id')->andReturn($project->getID());
        $request->shouldReceive('getProject')->andReturn($project);

        $response = Mockery::mock(AgileDashboardConfigurationResponse::class);

        $configuration_updater = new AgileDashboardScrumConfigurationUpdater(
            $request,
            Mockery::mock(AgileDashboard_ConfigurationManager::class),
            $response,
            Mockery::mock(AgileDashboard_FirstScrumCreator::class),
            Mockery::mock(ScrumForMonoMilestoneEnabler::class),
            Mockery::mock(ScrumForMonoMilestoneDisabler::class),
            Mockery::mock(ScrumForMonoMilestoneChecker::class),
            Mockery::mock(ConfigurationUpdater::class),
            $event_dispatcher,
            new \Tuleap\Kanban\CheckSplitKanbanConfiguration(),
        );

        $response->shouldNotReceive('scrumConfigurationUpdated');

        $configuration_updater->updateConfiguration();
    }
}
