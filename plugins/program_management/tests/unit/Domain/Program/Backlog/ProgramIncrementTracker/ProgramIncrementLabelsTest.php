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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker;

use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Stub\RetrieveProgramIncrementLabelsStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIncrementLabelsTest extends TestCase
{
    public function dataProviderLabels(): array
    {
        return [
            'both labels null'     => [null, null],
            'null label'           => [null, 'release'],
            'null sub-label'       => ['Releases', null],
            'both labels not null' => ['Releases', 'release'],
        ];
    }

    /**
     * @dataProvider dataProviderLabels
     */
    public function testItBuildsLabels(?string $label, ?string $sub_label): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(87)->build();
        $program_increment_tracker = new ProgramTracker($tracker);
        $labels                    = ProgramIncrementLabels::fromProgramIncrementTracker(
            RetrieveProgramIncrementLabelsStub::buildLabels($label, $sub_label),
            $program_increment_tracker
        );
        self::assertSame($label, $labels->label);
        self::assertSame($sub_label, $labels->sub_label);
    }
}
