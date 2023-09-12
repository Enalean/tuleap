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
use Tuleap\ProgramManagement\Tests\Stub\BacklogBlocksProgramServiceIfNeededStub;
use Tuleap\ProgramManagement\Tests\Stub\ProgramBlocksBacklogServiceIfNeededStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\Project\Event\ProjectServiceBeforeActivation;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProjectServiceBeforeActivationHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProjectServiceBeforeActivation $event;
    private string $handled_shortname;
    private VerifyIsTeamStub $team_verifier;
    private BacklogBlocksProgramServiceIfNeededStub $program_blocker;
    private ProgramBlocksBacklogServiceIfNeededStub $backlog_blocker;

    protected function setUp(): void
    {
        $this->team_verifier   = VerifyIsTeamStub::withNotValidTeam();
        $this->program_blocker = BacklogBlocksProgramServiceIfNeededStub::withNotBlocked();
        $this->backlog_blocker = ProgramBlocksBacklogServiceIfNeededStub::withNotBlocked();

        $this->handled_shortname = ProgramService::SERVICE_SHORTNAME;
    }

    private function handle(): void
    {
        $this->event = new ProjectServiceBeforeActivation(
            ProjectTestBuilder::aProject()->build(),
            $this->handled_shortname,
            UserTestBuilder::buildWithDefaults()
        );

        $handler = new ProjectServiceBeforeActivationHandler(
            $this->team_verifier,
            $this->program_blocker,
            $this->backlog_blocker,
            ProgramService::SERVICE_SHORTNAME,
            \AgileDashboardPlugin::PLUGIN_SHORTNAME,
        );
        $handler->handle(ProjectServiceBeforeActivationProxy::fromEvent($this->event));
    }

    public function testItDoesNotBlockOtherServices(): void
    {
        $this->handled_shortname = 'other_plugin';
        $this->handle();
        self::assertEmpty($this->event->getWarningMessage());
        self::assertFalse($this->event->doesPluginSetAValue());
    }

    public function testItDoesNotBlockProgramService(): void
    {
        $this->handle();
        self::assertEmpty($this->event->getWarningMessage());
        self::assertFalse($this->event->doesPluginSetAValue());
    }

    public function testItDisablesProgramServiceForTeam(): void
    {
        $this->team_verifier = VerifyIsTeamStub::withValidTeam();
        $this->handle();
        self::assertNotEmpty($this->event->getWarningMessage());
        self::assertTrue($this->event->doesPluginSetAValue());
    }

    public function testItDisablesProgramServiceWhenBacklogTellsItTo(): void
    {
        $this->program_blocker = BacklogBlocksProgramServiceIfNeededStub::withBlocked();
        $this->handle();
        self::assertNotEmpty($this->event->getWarningMessage());
        self::assertTrue($this->event->doesPluginSetAValue());
    }

    public function testItDoesNotBlockBacklogService(): void
    {
        $this->handled_shortname = \AgileDashboardPlugin::PLUGIN_SHORTNAME;
        $this->handle();
        self::assertEmpty($this->event->getWarningMessage());
        self::assertFalse($this->event->doesPluginSetAValue());
    }

    public function testItDisablesBacklogServiceWhenProgramTellsItTo(): void
    {
        $this->handled_shortname = \AgileDashboardPlugin::PLUGIN_SHORTNAME;
        $this->backlog_blocker   = ProgramBlocksBacklogServiceIfNeededStub::withBlocked();
        $this->handle();
        self::assertNotEmpty($this->event->getWarningMessage());
        self::assertTrue($this->event->doesPluginSetAValue());
    }
}
