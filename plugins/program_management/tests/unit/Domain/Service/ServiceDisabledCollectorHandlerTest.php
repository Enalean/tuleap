<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Service;

use Tuleap\AgileDashboard\Stub\RetrievePlanningStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Test\Builders\UserTestBuilder;

final class ServiceDisabledCollectorHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItDoesNothingForOtherPlugins(): void
    {
        $checker = VerifyIsTeamStub::withValidTeam();
        $handler = new ServiceDisabledCollectorHandler($checker, RetrievePlanningStub::stubNoPlannings());

        $event = new ServiceDisabledCollector(new \Project(['group_id' => 101]), \program_managementPlugin::SERVICE_SHORTNAME, UserTestBuilder::aUser()->build());
        $handler->handle($event, 'other_plugin');

        self::assertEmpty($event->getReason());
    }

    public function testItDoesNothingWhenServiceShouldNotBeDisabled(): void
    {
        $checker = VerifyIsTeamStub::withNotValidTeam();
        $handler = new ServiceDisabledCollectorHandler($checker, RetrievePlanningStub::stubNoPlannings());

        $event = new ServiceDisabledCollector(new \Project(['group_id' => 101]), \program_managementPlugin::SERVICE_SHORTNAME, UserTestBuilder::aUser()->build());
        $handler->handle($event, \program_managementPlugin::SERVICE_SHORTNAME);

        self::assertEmpty($event->getReason());
    }

    public function testItDisableServiceForTeam(): void
    {
        $checker = VerifyIsTeamStub::withValidTeam();
        $handler = new ServiceDisabledCollectorHandler($checker, RetrievePlanningStub::stubNoPlannings());

        $event = new ServiceDisabledCollector(new \Project(['group_id' => 101]), \program_managementPlugin::SERVICE_SHORTNAME, UserTestBuilder::aUser()->build());
        $handler->handle($event, \program_managementPlugin::SERVICE_SHORTNAME);

        self::assertNotEmpty($event->getReason());
    }

    public function testItDisableServiceWhenScrumIsEnabled(): void
    {
        $checker = VerifyIsTeamStub::withNotValidTeam();
        $handler = new ServiceDisabledCollectorHandler($checker, RetrievePlanningStub::stubAllPlannings());

        $event = new ServiceDisabledCollector(new \Project(['group_id' => 101]), \program_managementPlugin::SERVICE_SHORTNAME, UserTestBuilder::aUser()->build());
        $handler->handle($event, \program_managementPlugin::SERVICE_SHORTNAME);

        self::assertNotEmpty($event->getReason());
    }
}
