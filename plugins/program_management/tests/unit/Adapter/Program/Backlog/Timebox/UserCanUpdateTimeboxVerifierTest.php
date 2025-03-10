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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\Timebox;

use PHPUnit\Framework\MockObject\Stub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\TimeboxIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserCanUpdateTimeboxVerifierTest extends TestCase
{
    private Artifact&Stub $artifact;

    protected function setUp(): void
    {
        $this->artifact = $this->createStub(Artifact::class);
    }

    private function verify(): bool
    {
        $verifier = new UserCanUpdateTimeboxVerifier(
            RetrieveFullArtifactStub::withArtifact($this->artifact),
            RetrieveUserStub::withUser(UserTestBuilder::buildWithId(789))
        );
        return $verifier->canUserUpdate(TimeboxIdentifierStub::withId(1), UserIdentifierStub::withId(789));
    }

    public function testItReturnsTrue(): void
    {
        $this->artifact->method('userCanUpdate')->willReturn(true);

        self::assertTrue($this->verify());
    }

    public function testItReturnsFalse(): void
    {
        $this->artifact->method('userCanUpdate')->willReturn(false);

        self::assertFalse($this->verify());
    }
}
