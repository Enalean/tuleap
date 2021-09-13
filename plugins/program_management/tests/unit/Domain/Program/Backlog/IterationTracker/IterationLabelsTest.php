<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\IterationTracker;

use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Tests\Stub\ProgramTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveIterationLabelsStub;

final class IterationLabelsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ?ProgramTracker $program_tracker;

    protected function setUp(): void
    {
        $this->program_tracker = ProgramTrackerStub::withDefaults();
    }

    public function testLabelsAreNullWhenNoProgramTracker(): void
    {
        $labels = IterationLabels::fromIterationTracker(
            RetrieveIterationLabelsStub::buildLabels('Iterations', 'iteration'),
            null
        );

        self::assertNull($labels->label);
        self::assertNull($labels->sub_label);
    }

    public function testLabelsAreNullWhenNoSavedLabels(): void
    {
        $labels = IterationLabels::fromIterationTracker(
            RetrieveIterationLabelsStub::buildLabels(null, null),
            $this->program_tracker
        );

        self::assertNull($labels->label);
        self::assertNull($labels->sub_label);
    }

    public function testReturnLabelsWhenTheyExist(): void
    {
        $labels = IterationLabels::fromIterationTracker(
            RetrieveIterationLabelsStub::buildLabels('Iterations', 'iteration'),
            $this->program_tracker
        );

        self::assertSame('Iterations', $labels->label);
        self::assertSame('iteration', $labels->sub_label);
    }
}
