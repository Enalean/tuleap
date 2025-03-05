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

namespace Tuleap\ProgramManagement\Domain\Redirections;

use Tuleap\ProgramManagement\Tests\Stub\BuildRedirectFormActionEventStub;
use Tuleap\ProgramManagement\Tests\Stub\IterationRedirectionParametersStub;
use Tuleap\ProgramManagement\Tests\Stub\ProgramRedirectionParametersStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BuildRedirectFormActionHandlerTest extends TestCase
{
    private BuildRedirectFormActionEventStub $event;
    private ProgramRedirectionParameters $program_redirection_parameters;
    private IterationRedirectionParameters $iteration_redirection_parameters;

    protected function setUp(): void
    {
        $this->program_redirection_parameters   = ProgramRedirectionParametersStub::withOtherValue();
        $this->iteration_redirection_parameters = IterationRedirectionParametersStub::withValues('redirect', '100');
        $this->event                            = BuildRedirectFormActionEventStub::withCount();
    }

    public function testItDoesNotRedirect(): void
    {
        BuildRedirectFormActionHandler::injectParameters(
            $this->program_redirection_parameters,
            $this->iteration_redirection_parameters,
            $this->event
        );
        self::assertSame(0, $this->event->getCreateIterationCount());
        self::assertSame(0, $this->event->getUpdateIterationCount());
        self::assertSame(0, $this->event->getCreateProgramIncrementCount());
        self::assertSame(0, $this->event->getUpdateProgramIncrementCount());
    }

    public function testItRedirectsAfterAProgramIncrementUpdate(): void
    {
        $this->program_redirection_parameters = ProgramRedirectionParametersStub::withUpdate();
        BuildRedirectFormActionHandler::injectParameters(
            $this->program_redirection_parameters,
            $this->iteration_redirection_parameters,
            $this->event
        );
        self::assertSame(0, $this->event->getCreateIterationCount());
        self::assertSame(0, $this->event->getUpdateIterationCount());
        self::assertSame(0, $this->event->getCreateProgramIncrementCount());
        self::assertSame(1, $this->event->getUpdateProgramIncrementCount());
    }

    public function testItRedirectsAfterAnIterationCreation(): void
    {
        $this->iteration_redirection_parameters = IterationRedirectionParametersStub::withCreate();
        BuildRedirectFormActionHandler::injectParameters(
            $this->program_redirection_parameters,
            $this->iteration_redirection_parameters,
            $this->event
        );

        self::assertSame(1, $this->event->getCreateIterationCount());
        self::assertSame(0, $this->event->getUpdateIterationCount());
        self::assertSame(0, $this->event->getCreateProgramIncrementCount());
        self::assertSame(0, $this->event->getUpdateProgramIncrementCount());
    }

    public function testItRedirectsAfterAnIterationEdition(): void
    {
        $this->iteration_redirection_parameters = IterationRedirectionParametersStub::withUpdate();
        BuildRedirectFormActionHandler::injectParameters(
            $this->program_redirection_parameters,
            $this->iteration_redirection_parameters,
            $this->event
        );

        self::assertSame(0, $this->event->getCreateIterationCount());
        self::assertSame(1, $this->event->getUpdateIterationCount());
        self::assertSame(0, $this->event->getCreateProgramIncrementCount());
        self::assertSame(0, $this->event->getUpdateProgramIncrementCount());
    }

    public function testItRedirectsAfterAProgramIncrementCreation(): void
    {
        $this->program_redirection_parameters = ProgramRedirectionParametersStub::withCreate();
        BuildRedirectFormActionHandler::injectParameters(
            $this->program_redirection_parameters,
            $this->iteration_redirection_parameters,
            $this->event
        );
        self::assertSame(0, $this->event->getCreateIterationCount());
        self::assertSame(0, $this->event->getUpdateIterationCount());
        self::assertSame(1, $this->event->getCreateProgramIncrementCount());
        self::assertSame(0, $this->event->getUpdateProgramIncrementCount());
    }
}
