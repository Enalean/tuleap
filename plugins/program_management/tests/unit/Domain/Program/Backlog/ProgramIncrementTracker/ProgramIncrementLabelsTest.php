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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrementTracker;

use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProgramIncrementLabelsStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramIncrementLabelsTest extends TestCase
{
    public static function dataProviderLabels(): array
    {
        return [
            'both labels null'     => [null, null],
            'null label'           => [null, 'release'],
            'null sub-label'       => ['Releases', null],
            'both labels not null' => ['Releases', 'release'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderLabels')]
    public function testItBuildsLabelsFromProgramIncrementTracker(?string $label, ?string $sub_label): void
    {
        $program_increment_tracker = TrackerReferenceStub::withDefaults();
        $labels                    = ProgramIncrementLabels::fromProgramIncrementTracker(
            RetrieveProgramIncrementLabelsStub::buildLabels($label, $sub_label),
            $program_increment_tracker
        );
        self::assertSame($label, $labels->label);
        self::assertSame($sub_label, $labels->sub_label);
    }

    public function testItBuildNullLabelsWhenProgramIncrementTrackerIsNull(): void
    {
        $labels = ProgramIncrementLabels::fromProgramIncrementTracker(
            RetrieveProgramIncrementLabelsStub::buildLabels('PI', 'pi'),
            null
        );
        self::assertNull($labels->label);
        self::assertNull($labels->sub_label);
    }
}
