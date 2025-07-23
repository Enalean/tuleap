<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog;

use Tuleap\ProgramManagement\Domain\Events\ArtifactCreatedEvent;
use Tuleap\ProgramManagement\Tests\Stub\ArtifactCreatedEventStub;
use Tuleap\ProgramManagement\Tests\Stub\DispatchProgramIncrementCreationStub;
use Tuleap\ProgramManagement\Tests\Stub\RemovePlannedFeaturesFromTopBacklogStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsProgramIncrementTrackerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtifactCreatedHandlerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ArtifactCreatedEvent $event;
    private RemovePlannedFeaturesFromTopBacklogStub $feature_remover;
    private VerifyIsProgramIncrementTrackerStub $program_increment_verifier;
    private DispatchProgramIncrementCreationStub $creation_dispatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->event = ArtifactCreatedEventStub::withIds(1, 15, 1001, 21);

        $this->feature_remover            = RemovePlannedFeaturesFromTopBacklogStub::withCount();
        $this->program_increment_verifier = VerifyIsProgramIncrementTrackerStub::buildValidProgramIncrement();
        $this->creation_dispatcher        = DispatchProgramIncrementCreationStub::withCount();
    }

    private function getHandler(): ArtifactCreatedHandler
    {
        return new ArtifactCreatedHandler(
            $this->feature_remover,
            $this->program_increment_verifier,
            $this->creation_dispatcher
        );
    }

    public function testHandleCleansUpTopBacklogAndDispatchesProgramIncrementCreation(): void
    {
        $this->getHandler()->handle($this->event);

        self::assertSame(1, $this->feature_remover->getCallCount());
        self::assertSame(1, $this->creation_dispatcher->getCallCount());
    }

    public function testItOnlyCleansUpTopBacklogWhenArtifactIsNotAProgramIncrement(): void
    {
        $this->program_increment_verifier = VerifyIsProgramIncrementTrackerStub::buildNotProgramIncrement();

        $this->getHandler()->handle($this->event);

        self::assertSame(1, $this->feature_remover->getCallCount());
        self::assertSame(0, $this->creation_dispatcher->getCallCount());
    }
}
