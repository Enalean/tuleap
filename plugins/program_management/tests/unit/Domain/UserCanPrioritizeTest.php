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

namespace Tuleap\ProgramManagement\Domain;

use Tuleap\ProgramManagement\Domain\Program\Backlog\NotAllowedToPrioritizeException;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyPrioritizeFeaturesPermissionStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserCanPrioritizeTest extends TestCase
{
    private UserIdentifierStub $user_identifier;
    private ProgramIdentifier $program_identifier;

    protected function setUp(): void
    {
        $this->user_identifier    = UserIdentifierStub::buildGenericUser();
        $this->program_identifier = ProgramIdentifierBuilder::build();
    }

    public function testItBuildsFromUserCanPrioritize(): void
    {
        $user_can_prioritize = UserCanPrioritize::fromUser(
            VerifyPrioritizeFeaturesPermissionStub::canPrioritize(),
            $this->user_identifier,
            $this->program_identifier,
            null
        );
        self::assertSame($this->user_identifier->getId(), $user_can_prioritize->getId());
    }

    public function testItThrowsWhenUserCanNotPrioritize(): void
    {
        $this->expectException(NotAllowedToPrioritizeException::class);
        UserCanPrioritize::fromUser(
            VerifyPrioritizeFeaturesPermissionStub::cannotPrioritize(),
            $this->user_identifier,
            $this->program_identifier,
            null
        );
    }
}
