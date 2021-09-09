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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tracker;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramTrackerBuilder;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class UserCanSubmitInTrackerVerifierTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\Stub|\UserManager $user_manager;
    private \PHPUnit\Framework\MockObject\Stub|\TrackerFactory $tracker_factory;
    private UserCanSubmitInTrackerVerifier $verifier;
    private UserIdentifier $user_identifier;
    private ProgramTracker $program_tracker;

    protected function setUp(): void
    {
        $this->user_manager    = $this->createStub(\UserManager::class);
        $this->tracker_factory = $this->createStub(\TrackerFactory::class);

        $this->verifier = new UserCanSubmitInTrackerVerifier($this->user_manager, $this->tracker_factory);

        $this->user_identifier = UserIdentifierStub::buildGenericUser();
        $this->program_tracker = ProgramTrackerBuilder::buildWithId(1);
    }

    public function testItReturnsFalseWhenTrackerIsNotFound(): void
    {
        $this->tracker_factory->method("getTrackerById")->willReturn(null);
        $this->user_manager->method("getUserById")->willReturn(null);

        self::assertFalse($this->verifier->canUserSubmitArtifact($this->user_identifier, $this->program_tracker));
    }

    public function testItReturnsFalseWhenUserIsNotFound(): void
    {
        $this->tracker_factory->method("getTrackerById")->willReturn(TrackerTestBuilder::aTracker()->build());
        $this->user_manager->method("getUserById")->willReturn(null);

        self::assertFalse($this->verifier->canUserSubmitArtifact($this->user_identifier, $this->program_tracker));
    }

    public function testItReturnsUserCanSubmitArtifact(): void
    {
        $tracker = $this->createStub(Tracker::class);
        $this->tracker_factory->method("getTrackerById")->willReturn($tracker);
        $this->user_manager->method("getUserById")->willReturn(UserTestBuilder::aUser()->build());

        $tracker->method("userCanSubmitArtifact")->willReturn(true);

        self::assertTrue($this->verifier->canUserSubmitArtifact($this->user_identifier, $this->program_tracker));
    }
}
