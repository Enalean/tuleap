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

use Tuleap\Kanban\Stubs\Legacy\LegacyKanbanRetrieverStub;
use Tuleap\Test\Builders\ProjectTestBuilder;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class AgileDashboard_ConfigurationManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testScrumCanBeInAEnabledState(): void
    {
        $config_dao = Mockery::mock(AgileDashboard_ConfigurationDao::class);
        $config_dao->shouldReceive('isScrumActivated')->andReturn(new \Tuleap\FakeDataAccessResult(['scrum' => '1']));
        $event_dispatcher = new class implements \Psr\EventDispatcher\EventDispatcherInterface {
            public function dispatch(object $event)
            {
                return $event;
            }
        };

        $configuration_manager = new AgileDashboard_ConfigurationManager(
            $config_dao,
            LegacyKanbanRetrieverStub::withoutActivatedKanban(),
            $event_dispatcher,
        );

        self::assertTrue($configuration_manager->scrumIsActivatedForProject(ProjectTestBuilder::aProject()->build()));
    }

    public function testScrumIsConsideredDisabledWhenItsAccessIsBlocked(): void
    {
        $config_dao       = Mockery::mock(AgileDashboard_ConfigurationDao::class);
        $event_dispatcher = new class implements \Psr\EventDispatcher\EventDispatcherInterface {
            public function dispatch(object $event)
            {
                if ($event instanceof \Tuleap\AgileDashboard\BlockScrumAccess) {
                    $event->disableScrumAccess();
                }
                return $event;
            }
        };

        $configuration_manager = new AgileDashboard_ConfigurationManager(
            $config_dao,
            LegacyKanbanRetrieverStub::withoutActivatedKanban(),
            $event_dispatcher,
        );

        self::assertFalse($configuration_manager->scrumIsActivatedForProject(ProjectTestBuilder::aProject()->build()));
    }
}
