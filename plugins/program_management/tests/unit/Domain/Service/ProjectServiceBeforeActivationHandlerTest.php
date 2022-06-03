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

use Tuleap\ProgramManagement\Adapter\Events\ProjectServiceBeforeActivationProxy;
use Tuleap\ProgramManagement\ProgramService;
use Tuleap\ProgramManagement\Tests\Stub\VerifyScrumBlocksServiceActivationStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProjectServiceBeforeActivationHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItDoesNothingForOtherPlugins(): void
    {
        $checker = VerifyIsTeamStub::withValidTeam();
        $handler = new ProjectServiceBeforeActivationHandler($checker, VerifyScrumBlocksServiceActivationStub::withScrum());

        $event = new ProjectServiceBeforeActivation(
            new \Project(['group_id' => 101, 'group_name' => 'A project', 'unix_group_name' => 'a_project', 'icon_codepoint' => '']),
            ProgramService::SERVICE_SHORTNAME,
            UserTestBuilder::aUser()->build()
        );
        $handler->handle(ProjectServiceBeforeActivationProxy::fromEvent($event), 'other_plugin');

        self::assertEmpty($event->getWarningMessage());
        self::assertFalse($event->doesPluginSetAValue());
    }

    public function testItDoesNothingWhenServiceShouldNotBeDisabled(): void
    {
        $checker = VerifyIsTeamStub::withNotValidTeam();
        $handler = new ProjectServiceBeforeActivationHandler($checker, VerifyScrumBlocksServiceActivationStub::withoutScrum());

        $event = new ProjectServiceBeforeActivation(
            new \Project(['group_id' => 101, 'group_name' => 'A project', 'unix_group_name' => 'a_project', 'icon_codepoint' => '']),
            ProgramService::SERVICE_SHORTNAME,
            UserTestBuilder::aUser()->build()
        );
        $handler->handle(ProjectServiceBeforeActivationProxy::fromEvent($event), ProgramService::SERVICE_SHORTNAME);

        self::assertEmpty($event->getWarningMessage());
        self::assertFalse($event->doesPluginSetAValue());
    }

    public function testItDisableServiceForTeams(): void
    {
        $checker = VerifyIsTeamStub::withValidTeam();
        $handler = new ProjectServiceBeforeActivationHandler($checker, VerifyScrumBlocksServiceActivationStub::withScrum());

        $event = new ProjectServiceBeforeActivation(
            new \Project(['group_id' => 101, 'group_name' => 'A project', 'unix_group_name' => 'a_project', 'icon_codepoint' => '']),
            ProgramService::SERVICE_SHORTNAME,
            UserTestBuilder::aUser()->build()
        );
        $handler->handle(ProjectServiceBeforeActivationProxy::fromEvent($event), ProgramService::SERVICE_SHORTNAME);

        self::assertNotEmpty($event->getWarningMessage());
        self::assertTrue($event->doesPluginSetAValue());
    }

    public function testItDisableServiceWhenScrumIsEnabled(): void
    {
        $checker = VerifyIsTeamStub::withNotValidTeam();
        $handler = new ProjectServiceBeforeActivationHandler($checker, VerifyScrumBlocksServiceActivationStub::withScrum());

        $event = new ProjectServiceBeforeActivation(
            new \Project(['group_id' => 101, 'group_name' => 'A project', 'unix_group_name' => 'a_project', 'icon_codepoint' => '']),
            ProgramService::SERVICE_SHORTNAME,
            UserTestBuilder::aUser()->build()
        );
        $handler->handle(ProjectServiceBeforeActivationProxy::fromEvent($event), ProgramService::SERVICE_SHORTNAME);

        self::assertNotEmpty($event->getWarningMessage());
        self::assertTrue($event->doesPluginSetAValue());
    }
}
