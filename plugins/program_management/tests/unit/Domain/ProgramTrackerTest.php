<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain;

use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleIterationTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramTrackerTest extends TestCase
{
    private UserIdentifierStub $user_identifier;

    protected function setUp(): void
    {
        $this->user_identifier = UserIdentifierStub::buildGenericUser();
    }

    public function testItBuildsIterationTracker(): void
    {
        $tracker   = TrackerTestBuilder::aTracker()->withId(78)->build();
        $retriever = RetrieveVisibleIterationTrackerStub::withValidTracker($tracker);
        $program   = ProgramIdentifierBuilder::build();

        $iteration_tracker = ProgramTracker::buildIterationTrackerFromProgram($retriever, $program, $this->user_identifier);
        self::assertSame(78, $iteration_tracker->getId());
    }

    public function testItReturnsNullIfNoIterationTracker(): void
    {
        $retriever = RetrieveVisibleIterationTrackerStub::withNotVisibleIterationTracker();
        $program   = ProgramIdentifierBuilder::build();

        $iteration_tracker = ProgramTracker::buildIterationTrackerFromProgram($retriever, $program, $this->user_identifier);
        self::assertNull($iteration_tracker);
    }
}
