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

use Tuleap\ProgramManagement\Adapter\Events\ServiceDisabledCollectorProxy;
use Tuleap\ProgramManagement\ProgramService;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\BacklogBlocksProgramServiceIfNeededStub;
use Tuleap\Project\Service\ServiceDisabledCollector;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ServiceDisabledCollectorHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ServiceDisabledCollector $event;
    private string $handled_shortname;
    private VerifyIsTeamStub $team_verifier;
    private BacklogBlocksProgramServiceIfNeededStub $backlog_blocker;

    protected function setUp(): void
    {
        $this->team_verifier   = VerifyIsTeamStub::withNotValidTeam();
        $this->backlog_blocker = BacklogBlocksProgramServiceIfNeededStub::withNotBlocked();

        $this->event             = new ServiceDisabledCollector(
            ProjectTestBuilder::aProject()->build(),
            ProgramService::SERVICE_SHORTNAME,
            UserTestBuilder::buildWithDefaults()
        );
        $this->handled_shortname = ProgramService::SERVICE_SHORTNAME;
    }

    private function handle(): void
    {
        $handler = new ServiceDisabledCollectorHandler($this->team_verifier, $this->backlog_blocker);
        $handler->handle(ServiceDisabledCollectorProxy::fromEvent($this->event), $this->handled_shortname);
    }

    public function testItDoesNotBlockOtherServices(): void
    {
        $this->handled_shortname = 'other_plugin';
        $this->handle();
        self::assertEmpty($this->event->getReason());
    }

    public function testItDoesNotBlockProgramService(): void
    {
        $this->handle();
        self::assertEmpty($this->event->getReason());
    }

    public function testItDisablesProgramServiceForTeam(): void
    {
        $this->team_verifier = VerifyIsTeamStub::withValidTeam();
        $this->handle();
        self::assertNotEmpty($this->event->getReason());
    }

    public function testItDisablesProgramServiceWhenBacklogTellsItTo(): void
    {
        $this->backlog_blocker = BacklogBlocksProgramServiceIfNeededStub::withBlocked();
        $this->handle();
        self::assertNotEmpty($this->event->getReason());
    }
}
