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

use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\VerifySubmissionPermissionStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserCanSubmitInTrackerVerifierTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\TrackerFactory
     */
    private $tracker_factory;
    private UserCanSubmitInTrackerVerifier $verifier;
    private UserIdentifier $user_identifier;
    private TrackerReference $tracker;

    protected function setUp(): void
    {
        $this->user_manager    = $this->createStub(\UserManager::class);
        $this->tracker_factory = $this->createStub(\TrackerFactory::class);
        $submission_verifier   = VerifySubmissionPermissionStub::withSubmitPermission();

        $this->verifier = new UserCanSubmitInTrackerVerifier($this->user_manager, $this->tracker_factory, $submission_verifier);

        $this->user_identifier = UserIdentifierStub::buildGenericUser();
        $this->tracker         = TrackerReferenceStub::withDefaults();
    }

    public function testItReturnsFalseWhenTrackerIsNotFound(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn(null);
        $this->user_manager->method('getUserById')->willReturn(null);

        self::assertFalse($this->verifier->canUserSubmitArtifact($this->user_identifier, $this->tracker));
    }

    public function testItReturnsFalseWhenUserIsNotFound(): void
    {
        $this->tracker_factory->method('getTrackerById')->willReturn(TrackerTestBuilder::aTracker()->build());
        $this->user_manager->method('getUserById')->willReturn(null);

        self::assertFalse($this->verifier->canUserSubmitArtifact($this->user_identifier, $this->tracker));
    }

    public function testItReturnsUserCanSubmitArtifact(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->withId(1)->build();
        $this->tracker_factory->method('getTrackerById')->willReturn($tracker);
        $this->user_manager->method('getUserById')->willReturn(UserTestBuilder::aUser()->build());

        self::assertTrue($this->verifier->canUserSubmitArtifact($this->user_identifier, $this->tracker));
    }
}
