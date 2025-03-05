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

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PlanProgramIncrementChangeTest extends TestCase
{
    public function testItBuildsAValidPlanProgramIncrementChange(): void
    {
        $tracker_id = 101;
        $label      = 'Releases';
        $sub_label  = 'release';
        $change     = new PlanProgramIncrementChange($tracker_id, $label, $sub_label);

        self::assertSame($tracker_id, $change->tracker_id);
        self::assertSame($label, $change->label);
        self::assertSame($sub_label, $change->sub_label);
    }

    public function testItAllowsNullLabels(): void
    {
        $change = new PlanProgramIncrementChange(
            101,
            null,
            null
        );
        self::assertNull($change->label);
        self::assertNull($change->sub_label);
    }
}
